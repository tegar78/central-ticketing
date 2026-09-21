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

        // 1. Base query for customers with valid GPS coordinates
        $mapQuery = Customer::whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->whereNotNull('longitude')
            ->where('longitude', '!=', '')
            ->where('latitude', '!=', '0')
            ->where('longitude', '!=', '0');

        if ($status && $status !== 'all') {
            $mapQuery->where('status', $status);
        }

        if ($billingNodeId) {
            $mapQuery->where('billing_node_id', $billingNodeId);
        }

        if ($search) {
            $mapQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('no_services', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('odp_name', 'like', "%{$search}%");
            });
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

        // 2. Compute counts (scoped to selected billing_node_id if set)
        $totalCustomers = Customer::when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))->count();
        $markedCount = Customer::whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->whereNotNull('longitude')
            ->where('longitude', '!=', '')
            ->where('latitude', '!=', '0')
            ->where('longitude', '!=', '0')
            ->when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))
            ->count();
        $unmarkedCount = max(0, $totalCustomers - $markedCount);

        // Counts by status (scoped to billing_node_id if set)
        $statusCounts = [
            'all'      => $totalCustomers,
            'active'   => Customer::where('status', 'active')->when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))->count(),
            'isolated' => Customer::where('status', 'isolated')->when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))->count(),
            'inactive' => Customer::where('status', 'inactive')->when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))->count(),
            'free'     => Customer::where('status', 'free')->when($billingNodeId, fn($q) => $q->where('billing_node_id', $billingNodeId))->count(),
        ];

        // 3. Query for Unmarked Customers Table
        $unmarkedStatus = $request->query('unmarked_status');
        $unmarkedSearch = $request->query('unmarked_search');

        $unmarkedQuery = Customer::where(function ($q) {
            $q->whereNull('latitude')
              ->orWhere('latitude', '')
              ->orWhereNull('longitude')
              ->orWhere('longitude', '')
              ->orWhere('latitude', '0')
              ->orWhere('longitude', '0');
        });

        if ($billingNodeId) {
            $unmarkedQuery->where('billing_node_id', $billingNodeId);
        }

        if ($unmarkedStatus && $unmarkedStatus !== 'all') {
            $unmarkedQuery->where('status', $unmarkedStatus);
        }

        if ($unmarkedSearch) {
            $unmarkedQuery->where(function ($q) use ($unmarkedSearch) {
                $q->where('name', 'like', "%{$unmarkedSearch}%")
                  ->orWhere('no_services', 'like', "%{$unmarkedSearch}%")
                  ->orWhere('phone', 'like', "%{$unmarkedSearch}%")
                  ->orWhere('address', 'like', "%{$unmarkedSearch}%")
                  ->orWhere('odp_name', 'like', "%{$unmarkedSearch}%");
            });
        }

        $unmarkedCustomers = $unmarkedQuery->latest('updated_at')->paginate(15, ['*'], 'unmarked_page')->withQueryString();

        $billingInstances = BillingInstance::where('is_active', true)->get();
        $billingMap = $billingInstances->keyBy('id');

        return view('maps.index', compact(
            'user',
            'markedCustomers',
            'totalCustomers',
            'markedCount',
            'unmarkedCount',
            'statusCounts',
            'unmarkedCustomers',
            'billingInstances',
            'billingMap'
        ));
    }

    /**
     * Update customer GPS coordinates
     */
    public function updateCoordinates(Request $request, $id)
    {
        $request->validate([
            'latitude'  => 'required|string|max:50',
            'longitude' => 'required|string|max:50',
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update([
            'latitude'  => trim($request->latitude),
            'longitude' => trim($request->longitude),
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
