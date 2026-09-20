<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\Customer;
use App\Models\BillingInstance;
use App\Models\Ticket;

class CustomerController extends Controller
{
    /**
     * Display centralized customer directory with global search & multi-filters
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Auto-sync any existing ticket customers into Customer table to ensure 100% data coverage
        $ticketCustomers = Ticket::select(
                'billing_instance_id',
                'no_services',
                DB::raw('MAX(id) as remote_customer_id'),
                DB::raw('MAX(customer_name) as name'),
                DB::raw('MAX(customer_phone) as phone'),
                DB::raw('MAX(customer_address) as address')
            )
            ->whereNotNull('no_services')
            ->where('no_services', '!=', '')
            ->groupBy('no_services', 'billing_instance_id')
            ->get();

        foreach ($ticketCustomers as $tc) {
            Customer::firstOrCreate(
                [
                    'billing_node_id' => $tc->billing_instance_id,
                    'no_services' => $tc->no_services,
                ],
                [
                    'remote_customer_id' => $tc->remote_customer_id ?? rand(1000, 9999),
                    'name' => $tc->name ?? 'Pelanggan Billing',
                    'phone' => $tc->phone,
                    'address' => $tc->address,
                    'status' => 'active'
                ]
            );
        }

        // Base query with relationships
        $query = Customer::with(['billingNode', 'tickets' => function ($q) {
            $q->latest();
        }]);

        // Global Search across 60 billing servers by no_services, name, phone, address, or odp_name
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_services', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('odp_name', 'like', "%{$search}%");
            });
        }

        // Filter by Billing Origin Node
        if ($request->filled('billing_node_id')) {
            $query->where('billing_node_id', $request->billing_node_id);
        }

        // Filter by Subscription Status (active, isolated, inactive)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by ODP Name
        if ($request->filled('odp_name')) {
            $query->where('odp_name', $request->odp_name);
        }

        $customers = $query->latest('updated_at')->paginate(25)->withQueryString();

        // Overall Stats
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $isolatedCustomers = Customer::where('status', 'isolated')->count();
        $inactiveCustomers = Customer::where('status', 'inactive')->count();
        $freeCustomers = Customer::where('status', 'free')->count();

        $billingInstances = BillingInstance::where('is_active', true)->get();
        $billingMap = $billingInstances->keyBy('id');
        $odpList = Customer::whereNotNull('odp_name')->where('odp_name', '!=', '')->distinct()->pluck('odp_name');
        $tenants = $billingInstances;

        return view('customers.index', compact(
            'user',
            'customers',
            'billingMap',
            'totalCustomers',
            'activeCustomers',
            'isolatedCustomers',
            'inactiveCustomers',
            'freeCustomers',
            'tenants',
            'odpList'
        ));
    }

    /**
     * Get customers for modal dropdown from remote Billing Instance API or local database fallback
     */
    public function getBillingCustomers($id)
    {
        $customers = [];
        $source = 'local_db';
        $billingInstanceData = null;

        if ($id !== 'all') {
            $billingInstance = BillingInstance::find($id);

            if ($billingInstance) {
                $billingInstanceData = [
                    'id' => $billingInstance->id,
                    'name' => $billingInstance->name,
                    'tenant_code' => $billingInstance->tenant_code,
                ];

                // For BILL-001 (bill-gyh.gayuh.net.id): Direct database connection to CI3
                if ($billingInstance->tenant_code === 'BILL-001') {
                    try {
                        $ci3Customers = DB::connection('billing_ci3')
                            ->table('customer')
                            ->leftJoin('m_odp', 'customer.id_odp', '=', 'm_odp.id_odp')
                            ->select([
                                'customer.customer_id',
                                'customer.name',
                                'customer.no_services',
                                'customer.address',
                                'customer.no_wa',
                                'customer.c_status',
                                'customer.user_profile',
                                'customer.cust_amount',
                                'm_odp.code_odp as odp_code',
                            ])
                            ->get();

                        foreach ($ci3Customers as $cust) {
                            $customers[] = [
                                'no_services'      => $cust->no_services,
                                'customer_name'     => $cust->name,
                                'customer_phone'    => $cust->no_wa,
                                'customer_address'  => trim($cust->address ?? ''),
                                'billing_instance_id' => $billingInstance->id,
                                'status'            => ucfirst($cust->c_status ?? 'Aktif'),
                                'package_name'      => $cust->user_profile ?? 'Regular',
                                'odp_name'          => $cust->odp_code,
                            ];
                        }
                        $source = 'direct_db_ci3';
                    } catch (\Exception $e) {
                        Log::warning("Direct DB connection to billing_ci3 failed: " . $e->getMessage());
                    }
                }

                // Fallback: Try remote API if direct DB didn't work
                if (empty($customers) && !empty($billingInstance->domain_url)) {
                    try {
                        $apiUrl = rtrim($billingInstance->domain_url, '/') . '/api/customers';
                        $response = Http::timeout(2)
                            ->withHeaders([
                                'X-API-Key' => $billingInstance->api_key,
                                'Accept' => 'application/json',
                            ])
                            ->get($apiUrl);

                        if ($response->successful() && isset($response->json()['data'])) {
                            $customers = $response->json()['data'];
                            $source = 'remote_api';
                        }
                    } catch (\Exception $e) {
                        Log::info("Remote billing customer fetch timeout/failed for {$billingInstance->name}: " . $e->getMessage());
                    }
                }
            }
        }

        // Fallback: fetch customers from local Customer and Ticket database for this billing instance
        if (empty($customers)) {
            $dbCustomers = Customer::where(function ($q) use ($id) {
                if ($id !== 'all') {
                    $q->where('billing_node_id', $id);
                }
            })->latest('updated_at')->get();

            $ticketCustomers = Ticket::select(
                    'no_services',
                    DB::raw('MAX(customer_name) as customer_name'),
                    DB::raw('MAX(customer_phone) as customer_phone'),
                    DB::raw('MAX(customer_address) as customer_address'),
                    DB::raw('MAX(billing_instance_id) as billing_instance_id')
                )
                ->whereNotNull('no_services')
                ->where('no_services', '!=', '')
                ->when($id !== 'all', function ($q) use ($id) {
                    $q->where('billing_instance_id', $id);
                })
                ->groupBy('no_services')
                ->get();

            $customerMap = [];

            foreach ($ticketCustomers as $tc) {
                $customerMap[$tc->no_services] = [
                    'no_services' => $tc->no_services,
                    'customer_name' => $tc->customer_name,
                    'customer_phone' => $tc->customer_phone,
                    'customer_address' => $tc->customer_address,
                    'billing_instance_id' => $tc->billing_instance_id,
                    'status' => 'Aktif',
                    'package_name' => 'Regular',
                    'odp_name' => null
                ];
            }

            foreach ($dbCustomers as $dc) {
                $customerMap[$dc->no_services] = [
                    'no_services' => $dc->no_services,
                    'customer_name' => $dc->name,
                    'customer_phone' => $dc->phone,
                    'customer_address' => $dc->address,
                    'billing_instance_id' => $dc->billing_node_id,
                    'status' => ucfirst($dc->status),
                    'package_name' => $dc->package_name ?? 'Regular',
                    'odp_name' => $dc->odp_name
                ];
            }

            $customers = array_values($customerMap);
        }

        return response()->json([
            'success' => true,
            'source' => $source,
            'billing_instance' => $billingInstanceData,
            'customers' => $customers
        ]);
    }

    /**
     * Trigger manual sync of billing instance customer data (e.g. bill-gyh.gayuh.net.id)
     */
    public function syncBilling(Request $request)
    {
        $tenantCode = $request->input('tenant_code', 'BILL-001');

        \Illuminate\Support\Facades\Artisan::call('sync:billing-customers', [
            'tenant_code' => $tenantCode
        ]);

        return back()->with('success', 'Sinkronisasi data pelanggan dari server billing ' . $tenantCode . ' (bill-gyh.gayuh.net.id) berhasil dilaksanakan.');
    }
}
