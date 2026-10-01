<?php

namespace App\Http\Controllers;

use App\Models\BillingInstance;
use App\Models\Customer;
use App\Models\Odp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OdpController extends Controller
{
    /**
     * Display the interactive ODP map and directory
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status');
        $billingNodeId = $request->query('billing_node_id');
        $search = $request->query('search');

        // 1. Base query for ODPs with valid GPS coordinates to plot on Leaflet map
        $mapQuery = Odp::hasGpsCoordinates()
            ->with(['billingNode:id,name,tenant_code'])
            ->withCount('customers');

        if ($status && $status !== 'all') {
            $mapQuery->where('status', $status);
        }

        if ($billingNodeId) {
            $mapQuery->where(function ($q) use ($billingNodeId) {
                $q->where('billing_node_id', $billingNodeId)
                  ->orWhereHas('customers', fn($c) => $c->where('billing_node_id', $billingNodeId));
            });
        }

        if ($search) {
            $mapQuery->search($search);
        }

        $markedOdps = $mapQuery->get();

        // 2. Aggregate stats
        $baseStatQuery = Odp::when($billingNodeId, function ($q) use ($billingNodeId) {
            $q->where(function ($sq) use ($billingNodeId) {
                $sq->where('billing_node_id', $billingNodeId)
                   ->orWhereHas('customers', fn($c) => $c->where('billing_node_id', $billingNodeId));
            });
        });

        $totalOdps = (clone $baseStatQuery)->count();
        $markedCount = (clone $baseStatQuery)->hasGpsCoordinates()->count();
        $unmarkedCount = max(0, $totalOdps - $markedCount);

        $statusCountsGroup = (clone $baseStatQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusCounts = [
            'all'         => $totalOdps,
            'active'      => $statusCountsGroup['active'] ?? 0,
            'full'        => $statusCountsGroup['full'] ?? 0,
            'maintenance' => $statusCountsGroup['maintenance'] ?? 0,
            'damaged'     => $statusCountsGroup['damaged'] ?? 0,
        ];

        // Total capacity & used port aggregates
        $portStats = (clone $baseStatQuery)
            ->selectRaw('COALESCE(SUM(total_ports), 0) as total_capacity, COALESCE(SUM(used_ports), 0) as total_used')
            ->first();

        $totalCapacity = (int) ($portStats->total_capacity ?? 0);
        $totalUsedPorts = (int) ($portStats->total_used ?? 0);
        $overallPortOccupancy = $totalCapacity > 0 ? round(($totalUsedPorts / $totalCapacity) * 100) : 0;

        // 3. Paginated ODP Table List
        $tableQuery = Odp::with(['billingNode:id,name,tenant_code', 'creator:id,name'])
            ->withCount('customers')
            ->when($billingNodeId, function ($q) use ($billingNodeId) {
                $q->where(function ($sq) use ($billingNodeId) {
                    $sq->where('billing_node_id', $billingNodeId)
                       ->orWhereHas('customers', fn($c) => $c->where('billing_node_id', $billingNodeId));
                });
            });

        if ($status && $status !== 'all') {
            $tableQuery->where('status', $status);
        }

        if ($search) {
            $tableQuery->search($search);
        }

        // 3. Sorting & Pagination (Ascending & Descending by Nama / Kode ODP)
        $sortBy = $request->query('sort_by', 'name');
        $sortDir = strtolower($request->query('sort_dir', 'asc'));

        // Support combined sort param if provided, e.g. sort=name_asc or sort=name_desc
        if ($request->filled('sort')) {
            $parts = explode('_', $request->query('sort'));
            $dir = array_pop($parts);
            if (in_array($dir, ['asc', 'desc'])) {
                $sortDir = $dir;
                $sortBy = implode('_', $parts);
            }
        }

        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        $allowedSorts = ['name', 'code_odp', 'status', 'total_ports', 'used_ports', 'updated_at', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
        }

        if ($sortBy === 'name') {
            // Natural alphanumeric sort on ODP name (e.g. ODP A1, ODP A2 ... ODP A10)
            if ($sortDir === 'desc') {
                $tableQuery->orderByRaw("REGEXP_SUBSTR(COALESCE(NULLIF(name, ''), code_odp), '^[A-Za-z -]+') DESC, CAST(REGEXP_SUBSTR(COALESCE(NULLIF(name, ''), code_odp), '[0-9]+') AS UNSIGNED) DESC, name DESC");
            } else {
                $tableQuery->orderByRaw("REGEXP_SUBSTR(COALESCE(NULLIF(name, ''), code_odp), '^[A-Za-z -]+') ASC, CAST(REGEXP_SUBSTR(COALESCE(NULLIF(name, ''), code_odp), '[0-9]+') AS UNSIGNED) ASC, name ASC");
            }
        } elseif ($sortBy === 'code_odp') {
            // Natural alphanumeric sort on code_odp (e.g. ODP-A1, ODP-A2 ... ODP-A10)
            if ($sortDir === 'desc') {
                $tableQuery->orderByRaw("REGEXP_SUBSTR(code_odp, '^[A-Za-z-]+') DESC, CAST(REGEXP_SUBSTR(code_odp, '[0-9]+') AS UNSIGNED) DESC, code_odp DESC");
            } else {
                $tableQuery->orderByRaw("REGEXP_SUBSTR(code_odp, '^[A-Za-z-]+') ASC, CAST(REGEXP_SUBSTR(code_odp, '[0-9]+') AS UNSIGNED) ASC, code_odp ASC");
            }
        } else {
            $tableQuery->orderBy($sortBy, $sortDir);
        }

        $odpList = $tableQuery->paginate(15)
            ->withQueryString();

        // 4. Safe billing instance list
        $billingInstances = BillingInstance::where('is_active', true)
            ->select(['id', 'name', 'tenant_code'])
            ->get();
        $billingMap = $billingInstances->keyBy('id');

        return view('maps.odp', compact(
            'user',
            'markedOdps',
            'totalOdps',
            'markedCount',
            'unmarkedCount',
            'statusCounts',
            'totalCapacity',
            'totalUsedPorts',
            'overallPortOccupancy',
            'odpList',
            'billingInstances',
            'billingMap',
            'sortBy',
            'sortDir'
        ));
    }

    /**
     * Store a newly created ODP in MariaDB
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role === 'technician') {
            abort(403, 'Akses ditolak. Teknisi tidak memiliki hak untuk menambah data ODP baru.');
        }

        $validated = $request->validate([
            'billing_node_id' => ['nullable', 'exists:billing_instances,id'],
            'code_odp'        => ['required', 'string', 'max:100'],
            'name'            => ['nullable', 'string', 'max:255'],
            'latitude'        => ['required', 'numeric', 'between:-90,90'],
            'longitude'       => ['required', 'numeric', 'between:-180,180'],
            'total_ports'     => ['required', 'integer', 'min:1', 'max:256'],
            'used_ports'      => ['nullable', 'integer', 'min:0'],
            'status'          => ['required', 'in:active,full,maintenance,damaged'],
            'photo'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'address'         => ['nullable', 'string', 'max:500'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $rawCode = trim($validated['code_odp']);
        $clean = strtoupper(trim((string)preg_replace('/^ODP-/i', '', $rawCode)));
        $clean = strtoupper(trim((string)preg_replace('/-C[0-9]+$/i', '', $clean)));
        $normalizedCode = 'ODP-' . $clean;

        // Check unique code across all Central Master ODPs
        $exists = Odp::where('code_odp', $normalizedCode)->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Kode ODP [' . $normalizedCode . '] sudah terdaftar di Master ODP Central.');
        }

        $photoPath = null;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $file = $request->file('photo');
            $cleanCode = Str::slug($normalizedCode, '_');
            $fileName = $cleanCode . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('odps', $fileName, 'public');
        }

        $odp = Odp::create([
            'billing_node_id' => $validated['billing_node_id'] ?? null,
            'code_odp'        => $normalizedCode,
            'name'            => !empty($validated['name']) ? trim($validated['name']) : ('ODP ' . $clean),
            'latitude'        => (string) $validated['latitude'],
            'longitude'       => (string) $validated['longitude'],
            'total_ports'     => (int) $validated['total_ports'],
            'used_ports'      => (int) ($validated['used_ports'] ?? 0),
            'status'          => $validated['status'],
            'photo_path'      => $photoPath,
            'address'         => $validated['address'] ?? null,
            'notes'           => $validated['notes'] ?? null,
            'created_by'      => $user->id,
        ]);

        // Auto-broadcast dan sinkronkan ODP baru ke semua billing instances
        $this->broadcastOdpToBillingInstances($odp);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data ODP [' . $odp->code_odp . '] berhasil ditambahkan dan disinkronkan ke semua billing.',
                'odp'     => $odp,
            ]);
        }

        return redirect()->route('odp.index')->with('success', 'ODP [' . $odp->code_odp . '] berhasil disimpan dan disinkronkan ke seluruh billing instance.');
    }

    /**
     * Get single ODP detail with connected customers
     */
    public function show(string|int $id)
    {
        $odp = Odp::with(['billingNode:id,name,tenant_code', 'creator:id,name'])
            ->withCount('customers')
            ->findOrFail($id);

        $connectedCustomers = Customer::when($odp->billing_node_id, fn($q) => $q->where('billing_node_id', $odp->billing_node_id))
            ->where('odp_name', $odp->code_odp)
            ->select(['id', 'no_services', 'name', 'phone', 'address', 'status', 'package_name'])
            ->get();

        return response()->json([
            'success'   => true,
            'odp'       => $odp,
            'customers' => $connectedCustomers,
        ]);
    }

    /**
     * Update ODP details
     */
    public function update(Request $request, string|int $id)
    {
        $user = Auth::user();
        if ($user->role === 'technician') {
            abort(403, 'Akses ditolak. Teknisi hanya diizinkan untuk mengunggah atau mengganti foto ODP.');
        }

        $odp = Odp::findOrFail($id);

        $validated = $request->validate([
            'billing_node_id' => ['nullable', 'exists:billing_instances,id'],
            'code_odp'        => ['required', 'string', 'max:100'],
            'name'            => ['nullable', 'string', 'max:255'],
            'latitude'        => ['required', 'numeric', 'between:-90,90'],
            'longitude'       => ['required', 'numeric', 'between:-180,180'],
            'total_ports'     => ['required', 'integer', 'min:1', 'max:256'],
            'used_ports'      => ['nullable', 'integer', 'min:0'],
            'status'          => ['required', 'in:active,full,maintenance,damaged'],
            'photo'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'address'         => ['nullable', 'string', 'max:500'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $photoPath = $odp->photo_path;

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            // Delete existing photo if present
            if ($odp->photo_path && Storage::disk('public')->exists($odp->photo_path)) {
                Storage::disk('public')->delete($odp->photo_path);
            }

            $file = $request->file('photo');
            $cleanCode = Str::slug($validated['code_odp'], '_');
            $fileName = $cleanCode . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('odps', $fileName, 'public');
        }

        $odp->update([
            'billing_node_id' => $validated['billing_node_id'] ?? $odp->billing_node_id,
            'code_odp'        => trim($validated['code_odp']),
            'name'            => $validated['name'] ?? null,
            'latitude'        => (string) $validated['latitude'],
            'longitude'       => (string) $validated['longitude'],
            'total_ports'     => (int) $validated['total_ports'],
            'used_ports'      => (int) ($validated['used_ports'] ?? 0),
            'status'          => $validated['status'],
            'photo_path'      => $photoPath,
            'address'         => $validated['address'] ?? null,
            'notes'           => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ODP [' . $odp->code_odp . '] berhasil diperbarui.',
                'odp'     => $odp,
            ]);
        }

        return redirect()->route('odp.index')->with('success', 'Perubahan data ODP [' . $odp->code_odp . '] berhasil disimpan.');
    }

    /**
     * Dedicated quick photo upload endpoint (accessible by Admin, Operator & Technician)
     */
    public function uploadPhoto(Request $request, string|int $id)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $odp = Odp::findOrFail($id);

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            // Clean old photo
            if ($odp->photo_path && Storage::disk('public')->exists($odp->photo_path)) {
                Storage::disk('public')->delete($odp->photo_path);
            }

            $file = $request->file('photo');
            $cleanCode = Str::slug($odp->code_odp, '_');
            $fileName = $cleanCode . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('odps', $fileName, 'public');

            $odp->update(['photo_path' => $photoPath]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success'   => true,
                    'message'   => 'Foto ODP [' . $odp->code_odp . '] berhasil diunggah.',
                    'photo_url' => asset('storage/' . $photoPath),
                ]);
            }

            return back()->with('success', 'Foto ODP [' . $odp->code_odp . '] berhasil diperbarui.');
        }

        return back()->with('error', 'Gagal memproses unggahan foto ODP.');
    }

    /**
     * Delete ODP record and photo file (Admin only)
     */
    public function destroy(string|int $id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            abort(403, 'Akses ditolak. Hanya Administrator yang dapat menghapus data ODP.');
        }

        $odp = Odp::findOrFail($id);

        // Delete photo file if present
        if ($odp->photo_path && Storage::disk('public')->exists($odp->photo_path)) {
            Storage::disk('public')->delete($odp->photo_path);
        }

        $code = $odp->code_odp;
        $odp->delete();

        return redirect()->route('odp.index')->with('success', 'ODP [' . $code . '] berhasil dihapus dari database.');
    }

    /**
     * Get port allocation matrix and connected customers for an ODP
     */
    public function ports(int $id)
    {
        $user = Auth::user();
        $odp = Odp::with(['billingNode:id,name,tenant_code'])->findOrFail($id);
        $matrix = $odp->getPortMatrix();

        return response()->json([
            'success'   => true,
            'odp'       => [
                'id'           => $odp->id,
                'code_odp'     => $odp->code_odp,
                'name'         => $odp->name,
                'total_ports'  => $odp->total_ports,
                'used_ports'   => $matrix['used_ports_count'],
                'status'       => $odp->status,
                'billing_node' => $odp->billingNode,
            ],
            'ports'      => $matrix['ports'],
            'unassigned' => $matrix['unassigned'],
            'can_edit'   => in_array($user->role, ['admin', 'operator']),
            'user_role'  => $user->role,
        ]);
    }

    /**
     * Live search customers from the same billing node to assign to a port
     */
    public function searchAvailableCustomers(Request $request, int $id)
    {
        $odp = Odp::findOrFail($id);
        $q = trim($request->query('q', ''));

        $query = Customer::query();

        if ($odp->billing_node_id) {
            $query->where('billing_node_id', $odp->billing_node_id);
        }

        if (!empty($q)) {
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('no_services', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%")
                   ->orWhere('address', 'like', "%{$q}%");
            });
        }

        $customers = $query->select([
            'id', 'billing_node_id', 'remote_customer_id', 'no_services', 'name', 'phone', 'address', 'status', 'package_name', 'odp_name', 'port_number'
        ])
        ->orderByRaw("CASE WHEN odp_name = ? THEN 0 ELSE 1 END", [$odp->code_odp])
        ->latest('updated_at')
        ->limit(25)
        ->get();

        return response()->json([
            'success'   => true,
            'customers' => $customers,
        ]);
    }

    /**
     * Assign a customer to a specific ODP port
     */
    public function assignPort(Request $request, int $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'operator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Role teknisi hanya memiliki hak baca (read-only).'
            ], 403);
        }

        $odp = Odp::findOrFail($id);

        $validated = $request->validate([
            'port_number' => ['required', 'integer', 'min:1', 'max:' . max(1, $odp->total_ports)],
            'customer_id' => ['required', 'exists:customers,id'],
        ]);

        $portNumber = (int) $validated['port_number'];
        $customer = Customer::findOrFail($validated['customer_id']);

        if ($odp->billing_node_id && $customer->billing_node_id != $odp->billing_node_id) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan harus berasal dari Server Billing yang sama dengan ODP ini.'
            ], 422);
        }

        // If another customer was previously on this exact port in this ODP, detach them
        Customer::where('odp_name', $odp->code_odp)
            ->when($odp->billing_node_id, fn($q) => $q->where('billing_node_id', $odp->billing_node_id))
            ->where('port_number', $portNumber)
            ->where('id', '!=', $customer->id)
            ->update([
                'port_number' => null
            ]);

        // Assign customer to this ODP and port
        $customer->update([
            'odp_name'    => $odp->code_odp,
            'port_number' => $portNumber,
        ]);

        // Sync to remote billing node if connected
        $this->syncCustomerToBillingNode($odp, $customer, $portNumber);

        // Recalculate used_ports and status
        $usedCount = Customer::where('odp_name', $odp->code_odp)
            ->when($odp->billing_node_id, fn($q) => $q->where('billing_node_id', $odp->billing_node_id))
            ->count();

        $newStatus = ($usedCount >= $odp->total_ports && $odp->status === 'active') ? 'full' : $odp->status;
        $odp->update([
            'used_ports' => $usedCount,
            'status'     => $newStatus,
        ]);

        $matrix = $odp->getPortMatrix();

        return response()->json([
            'success' => true,
            'message' => "Pelanggan [{$customer->name}] berhasil dipasang ke Port {$portNumber} ODP {$odp->code_odp}.",
            'ports'   => $matrix['ports'],
            'odp'     => [
                'id'         => $odp->id,
                'used_ports' => $usedCount,
                'status'     => $odp->status,
            ],
        ]);
    }

    /**
     * Detach a customer from an ODP port
     */
    public function detachPort(Request $request, int $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'operator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Role teknisi hanya memiliki hak baca (read-only).'
            ], 403);
        }

        $odp = Odp::findOrFail($id);

        $validated = $request->validate([
            'port_number' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'exists:customers,id'],
        ]);

        $query = Customer::where('odp_name', $odp->code_odp)
            ->when($odp->billing_node_id, fn($q) => $q->where('billing_node_id', $odp->billing_node_id));

        if (!empty($validated['customer_id'])) {
            $customer = $query->where('id', $validated['customer_id'])->first();
        } elseif (!empty($validated['port_number'])) {
            $customer = $query->where('port_number', $validated['port_number'])->first();
        } else {
            return response()->json(['success' => false, 'message' => 'Customer atau Port tidak ditentukan.'], 422);
        }

        if ($customer) {
            $customerName = $customer->name;
            $oldPort = $customer->port_number;

            $customer->update([
                'odp_name'    => null,
                'port_number' => null,
            ]);

            // Sync detach to billing node
            $this->syncCustomerToBillingNode($odp, $customer, null);

            // Recalculate used_ports and status
            $usedCount = Customer::where('odp_name', $odp->code_odp)
                ->when($odp->billing_node_id, fn($q) => $q->where('billing_node_id', $odp->billing_node_id))
                ->count();

            $newStatus = ($odp->status === 'full' && $usedCount < $odp->total_ports) ? 'active' : $odp->status;
            $odp->update([
                'used_ports' => $usedCount,
                'status'     => $newStatus,
            ]);

            $matrix = $odp->getPortMatrix();

            return response()->json([
                'success' => true,
                'message' => "Pelanggan [{$customerName}] berhasil dilepas dari Port {$oldPort}.",
                'ports'   => $matrix['ports'],
                'odp'     => [
                    'id'         => $odp->id,
                    'used_ports' => $usedCount,
                    'status'     => $odp->status,
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Data pelanggan pada port ini tidak ditemukan.'], 404);
    }

    /**
     * Sync customer ODP & port changes back to the connected billing node database
     */
    protected function syncCustomerToBillingNode(Odp $odp, Customer $customer, ?int $portNumber): void
    {
        if (empty($customer->remote_customer_id)) {
            return;
        }

        $billing = $odp->billingNode ?: ($odp->billing_node_id ? BillingInstance::find($odp->billing_node_id) : null);
        if (!$billing) {
            return;
        }

        try {
            $conn = $billing->getDatabaseConnection() ?: ($billing->tenant_code === 'BILL-001' ? DB::connection('billing_ci3') : null);
            if ($conn) {
                $remoteOdpId = null;
                if ($portNumber !== null) {
                    $cleanCode = preg_replace('/^ODP-/', '', $odp->code_odp);
                    $remoteOdp = $conn->table('m_odp')
                        ->where('code_odp', $odp->code_odp)
                        ->orWhere('code_odp', $cleanCode)
                        ->orWhere('code_odp', 'like', "{$cleanCode}-%")
                        ->orWhere('id_odp', $odp->id)
                        ->first();
                    $remoteOdpId = $remoteOdp?->id_odp;
                }

                $conn->table('customer')
                    ->where('customer_id', $customer->remote_customer_id)
                    ->update([
                        'id_odp'      => $remoteOdpId,
                        'no_port_odp' => $portNumber,
                    ]);

                \Illuminate\Support\Facades\Log::info("Synced customer {$customer->no_services} to billing DB [{$billing->tenant_code}]: ODP ID={$remoteOdpId}, Port={$portNumber}");
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to sync customer {$customer->no_services} to billing DB: " . $e->getMessage());
        }
    }

    /**
     * Broadcast newly created or updated ODP to all active billing instances
     */
    protected function broadcastOdpToBillingInstances(Odp $odp): void
    {
        $tenants = BillingInstance::where('is_active', true)->get();
        $cleanCode = preg_replace('/^ODP-/i', '', $odp->code_odp);

        foreach ($tenants as $tenant) {
            // 1. Direct DB insertion into m_odp if direct DB is accessible
            try {
                $conn = $tenant->getDatabaseConnection() ?: ($tenant->tenant_code === 'BILL-001' ? DB::connection('billing_ci3') : null);
                if ($conn) {
                    $exists = $conn->table('m_odp')
                        ->where('code_odp', $odp->code_odp)
                        ->orWhere('code_odp', $cleanCode)
                        ->exists();

                    if (!$exists) {
                        $conn->table('m_odp')->insert([
                            'code_odp'   => $cleanCode,
                            'latitude'   => $odp->latitude,
                            'longitude'  => $odp->longitude,
                            'total_port' => $odp->total_ports,
                            'remark'     => $odp->notes ?? $odp->name,
                            'created'    => time(),
                            'create_by'  => 0,
                        ]);
                        \Illuminate\Support\Facades\Log::info("Auto-added ODP {$cleanCode} to billing DB [{$tenant->tenant_code}]");
                    } else {
                        $conn->table('m_odp')
                            ->where('code_odp', $odp->code_odp)
                            ->orWhere('code_odp', $cleanCode)
                            ->update([
                                'latitude'   => $odp->latitude,
                                'longitude'  => $odp->longitude,
                                'total_port' => $odp->total_ports,
                                'remark'     => $odp->notes ?? $odp->name,
                            ]);
                    }
                }
            } catch (\Throwable $e) {
                // Direct DB skipped if connection not available
            }

            // 2. HTTP push notification if billing instance has domain_url
            if (!empty($tenant->domain_url)) {
                try {
                    \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->timeout(3)
                        ->withHeaders([
                            'X-API-Key' => $tenant->api_key,
                            'Accept'    => 'application/json',
                        ])
                        ->post(rtrim($tenant->domain_url, '/') . '/central/odp_sync', [
                            'code_odp'    => $odp->code_odp,
                            'clean_code'  => $cleanCode,
                            'name'        => $odp->name,
                            'latitude'    => $odp->latitude,
                            'longitude'   => $odp->longitude,
                            'total_ports' => $odp->total_ports,
                        ]);
                } catch (\Throwable $e) {
                    // Silently ignore if webhook not supported by this billing instance
                }
            }
        }
    }
}
