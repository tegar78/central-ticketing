<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\BillingInstance;

class MapController extends Controller
{
    /**
     * Display interactive customer GPS map & unmarked customers list
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status');
        $billingNodeId = $request->query('billing_node_id');
        $search = $request->query('search');

        // 1. Base query for customers with valid GPS coordinates using reusable scope
        $mapQuery = Customer::hasGpsCoordinates();

        if ($status && $status !== 'all') {
            $mapQuery->where('status', $status);
        }

        if ($billingNodeId) {
            $mapQuery->where('billing_node_id', $billingNodeId);
        }

        if ($search) {
            $mapQuery->search($search);
        }

        // Fetch marked customers for map plotting
        $markedCustomers = $mapQuery->select([
            'id',
            'no_services',
            'name',
            'phone',
            'address',
            'odp_name',
            'package_name',
            'status',
            'latitude',
            'longitude',
            'billing_node_id',
        ])->get();

        // 2. Compute counts with efficient grouped aggregation (scoped to billing_node_id if set)
        $totalCustomers = Customer::when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))->count();
        $markedCount = Customer::hasGpsCoordinates()
            ->when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))
            ->count();
        $unmarkedCount = max(0, $totalCustomers - $markedCount);

        // Counts by status aggregated in a single query
        $statusCountsGroup = Customer::when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusCounts = [
            'all'      => $totalCustomers,
            'active'   => $statusCountsGroup['active'] ?? 0,
            'isolated' => $statusCountsGroup['isolated'] ?? 0,
            'inactive' => $statusCountsGroup['inactive'] ?? 0,
            'free'     => $statusCountsGroup['free'] ?? 0,
        ];

        // 3. Query for Unmarked Customers Table using reusable scope
        $unmarkedStatus = $request->query('unmarked_status');
        $unmarkedSearch = $request->query('unmarked_search');

        $baseUnmarkedQuery = Customer::withoutGpsCoordinates()
            ->when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId));

        $unmarkedCountsGroup = (clone $baseUnmarkedQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $unmarkedTotal = array_sum($unmarkedCountsGroup);

        $unmarkedStatusCounts = [
            'all'      => $unmarkedTotal,
            'active'   => $unmarkedCountsGroup['active'] ?? 0,
            'isolated' => $unmarkedCountsGroup['isolated'] ?? 0,
            'inactive' => $unmarkedCountsGroup['inactive'] ?? 0,
            'free'     => $unmarkedCountsGroup['free'] ?? 0,
        ];

        $unmarkedQuery = clone $baseUnmarkedQuery;

        if ($unmarkedStatus && $unmarkedStatus !== 'all') {
            $unmarkedQuery->where('status', $unmarkedStatus);
        }

        if ($unmarkedSearch) {
            $unmarkedQuery->search($unmarkedSearch);
        }

        $unmarkedCustomers = $unmarkedQuery->latest('updated_at')
            ->paginate(15, ['*'], 'unmarked_page')
            ->withQueryString();

        // 4. Safely query billing instances without exposing sensitive credentials (api_key, db_password, etc.)
        $billingInstances = BillingInstance::where('is_active', true)
            ->select(['id', 'name', 'tenant_code'])
            ->get();
        $billingMap = $billingInstances->keyBy('id');

        return view('maps.index', compact(
            'user',
            'markedCustomers',
            'totalCustomers',
            'markedCount',
            'unmarkedCount',
            'statusCounts',
            'unmarkedStatusCounts',
            'unmarkedCustomers',
            'billingInstances',
            'billingMap'
        ));
    }

    /**
     * Update customer GPS coordinates
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateCoordinates(Request $request, string|int $id)
    {
        $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update([
            'latitude'  => (string) $request->latitude,
            'longitude' => (string) $request->longitude,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Koordinat pelanggan berhasil diperbarui.',
                'customer' => $customer,
            ]);
        }

        return back()->with('success', 'Koordinat GPS untuk pelanggan ' . $customer->name . ' (' . $customer->no_services . ') berhasil disimpan.');
    }
}
