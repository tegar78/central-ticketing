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
            $mapQuery->where('billing_node_id', $billingNodeId);
        }

        if ($search) {
            $mapQuery->search($search);
        }

        $markedOdps = $mapQuery->get();

        // 2. Aggregate stats
        $baseStatQuery = Odp::when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId));

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
            ->when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId));

        if ($status && $status !== 'all') {
            $tableQuery->where('status', $status);
        }

        if ($search) {
            $tableQuery->search($search);
        }

        $odpList = $tableQuery->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        // 4. Safe billing instance list
        $billingInstances = BillingInstance::where('is_active', true)
            ->select(['id', 'name', 'tenant_code'])
            ->get();
        $billingMap = $billingInstances->keyBy('id');

        // Check if there are customers with odp_name that are not yet in odps table
        $unregisteredOdpCount = 0;
        if (in_array($user->role, ['admin', 'operator'])) {
            $existingOdpCodes = Odp::pluck('code_odp')->toArray();
            $unregisteredOdpCount = Customer::whereNotNull('odp_name')
                ->where('odp_name', '!=', '')
                ->where('odp_name', '!=', '-')
                ->whereNotIn('odp_name', $existingOdpCodes)
                ->distinct('odp_name')
                ->count('odp_name');
        }

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
            'unregisteredOdpCount'
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

        // Check unique code per billing node
        $exists = Odp::where('code_odp', trim($validated['code_odp']))
            ->when($validated['billing_node_id'] ?? null, fn($q, $b) => $q->where('billing_node_id', $b))
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Kode ODP [' . $validated['code_odp'] . '] sudah terdaftar pada server billing yang dipilih.');
        }

        $photoPath = null;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $file = $request->file('photo');
            $cleanCode = Str::slug($validated['code_odp'], '_');
            $fileName = $cleanCode . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('odps', $fileName, 'public');
        }

        $odp = Odp::create([
            'billing_node_id' => $validated['billing_node_id'] ?? null,
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
            'created_by'      => $user->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data ODP [' . $odp->code_odp . '] berhasil ditambahkan.',
                'odp'     => $odp,
            ]);
        }

        return redirect()->route('odp.index')->with('success', 'ODP [' . $odp->code_odp . '] berhasil disimpan ke MariaDB.');
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
     * Auto-sync ODP records from existing customer data
     */
    public function syncFromCustomers(Request $request)
    {
        $user = Auth::user();
        if (in_array($user->role, ['technician'])) {
            abort(403, 'Akses ditolak.');
        }

        $billingNodeId = $request->input('billing_node_id');

        // Query distinct odp_name with average coordinates from customers table
        $query = Customer::whereNotNull('odp_name')
            ->where('odp_name', '!=', '')
            ->where('odp_name', '!=', '-');

        if ($billingNodeId) {
            $query->where('billing_node_id', $billingNodeId);
        }

        $customerOdps = $query->select([
            'billing_node_id',
            'odp_name',
            DB::raw('COUNT(id) as total_customers'),
            DB::raw('AVG(CASE WHEN latitude IS NOT NULL AND latitude != "" AND latitude != "0" THEN CAST(latitude AS DECIMAL(10,7)) END) as avg_lat'),
            DB::raw('AVG(CASE WHEN longitude IS NOT NULL AND longitude != "" AND longitude != "0" THEN CAST(longitude AS DECIMAL(10,7)) END) as avg_lng'),
        ])
        ->groupBy('billing_node_id', 'odp_name')
        ->get();

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($customerOdps as $item) {
            $existing = Odp::where('code_odp', $item->odp_name)
                ->when($item->billing_node_id, fn($q, $b) => $q->where('billing_node_id', $b))
                ->first();

            $lat = !empty($item->avg_lat) ? (string) round($item->avg_lat, 6) : null;
            $lng = !empty($item->avg_lng) ? (string) round($item->avg_lng, 6) : null;

            if (!$existing) {
                Odp::create([
                    'billing_node_id' => $item->billing_node_id,
                    'code_odp'        => $item->odp_name,
                    'name'            => 'ODP ' . $item->odp_name,
                    'latitude'        => $lat ?? '-6.175392',
                    'longitude'       => $lng ?? '106.827153',
                    'total_ports'     => max(8, (int) $item->total_customers),
                    'used_ports'      => (int) $item->total_customers,
                    'status'          => ((int) $item->total_customers >= 8) ? 'full' : 'active',
                    'created_by'      => $user->id,
                    'notes'           => 'Otomatis di-sinkronisasi dari data sebaran pelanggan',
                ]);
                $createdCount++;
            } else {
                // Update used_ports if different
                if ($existing->used_ports != (int) $item->total_customers) {
                    $existing->update([
                        'used_ports' => (int) $item->total_customers,
                    ]);
                    $updatedCount++;
                }
            }
        }

        return redirect()->route('odp.index')->with('success', "Sinkronisasi ODP selesai: {$createdCount} ODP baru didaftarkan, {$updatedCount} data ODP diperbarui.");
    }
}
