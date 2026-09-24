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

        // Base query with billingNode relationship
        $query = Customer::with('billingNode');

        // Global Search across 60 billing servers by no_services, name, phone, address, or odp_name
        if ($request->filled('search')) {
            $query->search($request->search);
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

        // Sorting by no_services or name (asc / desc)
        $sortBy = $request->get('sort_by');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sortBy, ['no_services', 'name'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('updated_at');
        }

        $customers = $query->paginate(25)->withQueryString();

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
     *
     * @param string|int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBillingCustomers(string|int $id)
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

                // 1. Prioritize local database (populated via RESTful API sync) for blazing fast response
                $localCustomers = Customer::where('billing_node_id', $billingInstance->id)->get();

                if ($localCustomers->isNotEmpty()) {
                    foreach ($localCustomers as $cust) {
                        $customers[] = [
                            'no_services'         => $cust->no_services,
                            'customer_name'       => $cust->name,
                            'customer_phone'      => $cust->phone,
                            'customer_address'    => trim($cust->address ?? ''),
                            'billing_instance_id' => $billingInstance->id,
                            'status'              => ucfirst($cust->status ?? 'Active'),
                            'package_name'        => $cust->package_name ?? 'Regular',
                            'odp_name'            => $cust->odp_name,
                        ];
                    }
                    $source = 'local_synced_api';
                }

                // 2. If local database is empty, query CI3 RESTful API directly (/central/customers or /api/customers)
                if (empty($customers) && !empty($billingInstance->domain_url)) {
                    $domainUrl = rtrim($billingInstance->domain_url, '/');
                    foreach (["{$domainUrl}/central/customers", "{$domainUrl}/api/customers"] as $apiUrl) {
                        try {
                            $response = Http::withoutVerifying()
                                ->timeout(3)
                                ->withHeaders([
                                    'X-API-Key' => $billingInstance->api_key,
                                    'Accept'    => 'application/json',
                                ])
                                ->get($apiUrl);

                            if ($response->successful() && isset($response->json()['data'])) {
                                $customers = $response->json()['data'];
                                $source = 'remote_rest_api';
                                break;
                            }
                        } catch (\Exception $e) {
                            Log::info("Remote billing REST API customer fetch timeout/failed ({$apiUrl}): " . $e->getMessage());
                        }
                    }
                }

                // 3. Fallback: Direct DB connection if configured
                if (empty($customers)) {
                    $conn = $billingInstance->getDatabaseConnection() 
                        ?: ($billingInstance->tenant_code === 'BILL-001' ? DB::connection('billing_ci3') : null);

                    if ($conn) {
                        try {
                            $ci3Customers = $conn->table('customer')
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
                                    'no_services'         => $cust->no_services,
                                    'customer_name'       => $cust->name,
                                    'customer_phone'      => $cust->no_wa,
                                    'customer_address'    => trim($cust->address ?? ''),
                                    'billing_instance_id' => $billingInstance->id,
                                    'status'              => ucfirst($cust->c_status ?? 'Aktif'),
                                    'package_name'        => $cust->user_profile ?? 'Regular',
                                    'odp_name'            => $cust->odp_code,
                                ];
                            }
                            $source = 'direct_db_ci3';
                        } catch (\Exception $e) {
                            Log::warning("Direct DB connection to {$billingInstance->name} failed: " . $e->getMessage());
                        }
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
     * Trigger manual sync of billing instance customer data
     */
    public function syncBilling(Request $request)
    {
        $tenantCode = $request->input('tenant_code');

        if ($tenantCode === 'all') {
            \Illuminate\Support\Facades\Artisan::call('sync:billing-customers', [
                '--all' => true
            ]);
            $totalCount = Customer::count();
            return back()->with('success', "Sinkronisasi data pelanggan dari semua server billing selesai. Total data saat ini: {$totalCount} pelanggan.");
        }

        $tenantCode = $tenantCode ?: 'BILL-001';
        $instance = BillingInstance::where('tenant_code', $tenantCode)->first();

        \Illuminate\Support\Facades\Artisan::call('sync:billing-customers', [
            'tenant_code' => $tenantCode
        ]);

        $syncedCount = $instance ? Customer::where('billing_node_id', $instance->id)->count() : 0;

        if ($syncedCount > 0) {
            return back()->with('success', "Sinkronisasi berhasil! Ditemukan {$syncedCount} pelanggan dari server billing {$tenantCode}.");
        }

        $domain = $instance?->domain_url ?? 'belum diatur';
        return back()->with('warning', "Sinkronisasi {$tenantCode} selesai tetapi 0 data tersimpan. Periksa URL server billing ({$domain}) dan API Key.");
    }
}
