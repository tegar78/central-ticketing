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
use Symfony\Component\Process\Process;

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

        // Overall Stats via single aggregated query to eliminate multiple full table scans
        $stats = Customer::toBase()
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                COUNT(CASE WHEN status = 'isolated' THEN 1 END) as isolated,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive,
                COUNT(CASE WHEN status = 'free' THEN 1 END) as free
            ")
            ->first();

        $totalCustomers = (int) ($stats->total ?? 0);
        $activeCustomers = (int) ($stats->active ?? 0);
        $isolatedCustomers = (int) ($stats->isolated ?? 0);
        $inactiveCustomers = (int) ($stats->inactive ?? 0);
        $freeCustomers = (int) ($stats->free ?? 0);

        $billingInstances = BillingInstance::where('is_active', true)->get();
        $billingMap = $billingInstances->keyBy('id');
        $odpList = \Illuminate\Support\Facades\Cache::remember('customer_unique_odp_list', 300, function () {
            return Customer::whereNotNull('odp_name')->where('odp_name', '!=', '')->distinct()->pluck('odp_name')->all();
        });
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
     * Fast AJAX Live Search for Customer Autocomplete in Modals
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function liveSearch(Request $request)
    {
        $q = trim($request->get('q', ''));
        $billingId = $request->get('billing_id') ?: $request->get('billing_instance_id') ?: $request->get('billing_node_id');

        if (empty($q) && empty($billingId)) {
            return response()->json([
                'total' => 0,
                'customers' => []
            ]);
        }

        $query = Customer::with('billingNode:id,name,tenant_code');

        if (!empty($billingId) && $billingId !== 'all') {
            $query->where('billing_node_id', $billingId);
        }

        if (!empty($q)) {
            $query->where(function ($sq) use ($q) {
                $sq->where('no_services', 'like', "%{$q}%")
                   ->orWhere('name', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%")
                   ->orWhere('odp_name', 'like', "%{$q}%");
            });
        }

        $totalCount = (clone $query)->count();

        $customers = $query->limit(20)->get()->map(function ($cust) {
            return [
                'no_services'         => $cust->no_services,
                'customer_name'       => $cust->name,
                'customer_phone'      => $cust->phone ?? '-',
                'customer_address'    => trim($cust->address ?? ''),
                'billing_instance_id' => $cust->billing_node_id,
                'billing_tenant'      => $cust->billingNode->tenant_code ?? null,
                'billing_name'        => $cust->billingNode->name ?? null,
                'status'              => ucfirst($cust->status ?? 'Active'),
                'package_name'        => $cust->package_name ?? 'Regular',
                'odp_name'            => $cust->odp_name ?? '-',
                'latitude'            => $cust->latitude,
                'longitude'           => $cust->longitude,
            ];
        });

        return response()->json([
            'total' => $totalCount,
            'customers' => $customers
        ]);
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
                            'port_number'         => $cust->port_number,
                            'latitude'            => $cust->latitude,
                            'longitude'           => $cust->longitude,
                        ];
                    }
                    $source = 'local_synced_api';
                }

                // 2. If local database is empty, query CI3 RESTful API directly (/central/customers or /api/customers)
                if (empty($customers) && !empty($billingInstance->domain_url)) {
                    $domainUrl = rtrim($billingInstance->domain_url, '/');
                    foreach (["{$domainUrl}/central/customers", "{$domainUrl}/api/customers"] as $apiUrl) {
                        try {
                            $verifySsl = (bool) env('BILLING_VERIFY_SSL', false);
                            $client = $verifySsl ? Http::timeout(3) : Http::withoutVerifying()->timeout(3);
                            $response = $client->withHeaders([
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
            \App\Jobs\SyncBillingCustomersJob::dispatch(null, true);
            return back()->with('success', "Perintah sinkronisasi seluruh server billing telah dijadwalkan di antrean sistem background.");
        }

        $tenantCode = $tenantCode ?: 'BILL-001';
        $instance = BillingInstance::where('tenant_code', $tenantCode)->first();

        \App\Jobs\SyncBillingCustomersJob::dispatch($tenantCode, false);

        return back()->with('success', "Sinkronisasi server billing {$tenantCode} sedang diproses di background.");
    }

    /**
     * Run safe diagnostic ping against customer IP address
     * Protected against Command Injection (CWE-78) using strict IP validation & Process arguments
     */
    public function ping(Request $request, Customer $customer)
    {
        $rawIp = $request->filled('ip_address') ? $request->input('ip_address') : null;
        $liveSession = null;

        // Auto-discover live session and IP from MikroTik active connection / queue
        $billing = $customer->billingNode;
        if ($billing && !empty($billing->domain_url)) {
            try {
                $domainUrl = rtrim($billing->domain_url, '/');
                $verifySsl = (bool) env('BILLING_VERIFY_SSL', false);
                $client = $verifySsl ? Http::timeout(4) : Http::withoutVerifying()->timeout(4);
                $resp = $client->withHeaders([
                        'X-API-Key' => $billing->api_key,
                        'Accept'    => 'application/json',
                    ])
                    ->get("{$domainUrl}/central/customer_network/{$customer->no_services}");

                if ($resp->successful() && $resp->json('status')) {
                    $netData = $resp->json('data');
                    $liveSession = $netData;

                    if (!empty($netData['ip_address']) && filter_var($netData['ip_address'], FILTER_VALIDATE_IP)) {
                        $rawIp = $rawIp ?: $netData['ip_address'];
                        // Persist auto-discovered live IP and PPPoE
                        $customer->update([
                            'ip_address' => $netData['ip_address'],
                            'pppoe_user' => $netData['pppoe_user'] ?? $customer->pppoe_user,
                        ]);
                    } elseif (isset($netData['is_online']) && !$netData['is_online']) {
                        return response()->json([
                            'success' => false,
                            'status'  => 'offline_mikrotik',
                            'message' => "Pelanggan {$customer->name} saat ini tidak memiliki sesi aktif di router MikroTik (" . ($netData['router_alias'] ?? 'Router') . "). Sesi PPPoE / Hotspot sedang offline.",
                            'customer' => [
                                'id'          => $customer->id,
                                'no_services' => $customer->no_services,
                                'name'        => $customer->name,
                                'ip_address'  => null,
                                'pppoe_user'  => $netData['pppoe_user'] ?? $customer->pppoe_user,
                            ],
                            'session' => $liveSession,
                        ], 200);
                    }
                }
            } catch (\Exception $e) {
                Log::info("MikroTik live query failed: " . $e->getMessage());
            }
        }

        if (empty($rawIp)) {
            $rawIp = $customer->ip_address;
        }

        if (empty($rawIp)) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Alamat IP pelanggan tidak ditemukan dari sesi aktif MikroTik maupun database.',
            ], 422);
        }

        $ip = trim((string) $rawIp);

        // Strict IP validation (IPv4 or IPv6) - blocks any injection characters
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return response()->json([
                'success' => false,
                'status'  => 'invalid_ip',
                'message' => 'Format alamat IP tidak valid. Pastikan format IPv4 atau IPv6 benar (misal: 192.168.1.1).',
            ], 422);
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmd = $isWindows 
            ? ['ping', '-n', '3', '-w', '1000', $ip] 
            : ['ping', '-c', '3', '-W', '1', $ip];

        $startTime = microtime(true);
        $process = new Process($cmd);
        $process->setTimeout(6);

        try {
            $process->run();
            $durationMs = round((microtime(true) - $startTime) * 1000, 1);
            $output = $process->getOutput() . $process->getErrorOutput();
            $exitCode = $process->getExitCode();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Gagal menjalankan proses ping: ' . $e->getMessage(),
            ], 500);
        }

        // Parse Packet Loss
        $packetLoss = null;
        if (preg_match('/(\d+)%\s*(?:packet\s*)?loss/i', $output, $lossMatch)) {
            $packetLoss = (int) $lossMatch[1];
        } else {
            $packetLoss = ($exitCode === 0) ? 0 : 100;
        }

        // Parse Latency
        $avgLatency = null;
        $minLatency = null;
        $maxLatency = null;

        // Windows: Minimum = 1ms, Maximum = 3ms, Average = 2ms
        if (preg_match('/(?:Minimum|min)[ =:\/]+([\d\.]+)(?:ms)?[,\s]+(?:Maximum|max)[ =:\/]+([\d\.]+)(?:ms)?[,\s]+(?:Average|avg)[ =:\/]+([\d\.]+)(?:ms)?/i', $output, $statMatches)) {
            $minLatency = round((float) $statMatches[1], 1);
            $maxLatency = round((float) $statMatches[2], 1);
            $avgLatency = round((float) $statMatches[3], 1);
        }
        // Linux: rtt min/avg/max/mdev = 0.038/0.052/0.071/0.014 ms
        elseif (preg_match('/rtt\s+min\/avg\/max\/mdev\s*=\s*([\d\.]+)\/([\d\.]+)\/([\d\.]+)/i', $output, $linuxMatches)) {
            $minLatency = round((float) $linuxMatches[1], 1);
            $avgLatency = round((float) $linuxMatches[2], 1);
            $maxLatency = round((float) $linuxMatches[3], 1);
        }
        // Fallback individual line matches: Reply from ... time=2ms or time<1ms
        elseif (preg_match_all('/time[=<]([\d\.]+)\s*ms/i', $output, $timeMatches) && !empty($timeMatches[1])) {
            $latencies = array_map('floatval', $timeMatches[1]);
            $minLatency = round(min($latencies), 1);
            $maxLatency = round(max($latencies), 1);
            $avgLatency = round(array_sum($latencies) / count($latencies), 1);
        }

        // Determine Status
        if ($packetLoss === 0 && ($avgLatency !== null || $exitCode === 0)) {
            $status = 'online';
        } elseif ($packetLoss > 0 && $packetLoss < 100) {
            $status = 'unstable';
        } else {
            $status = 'offline';
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'id'          => $customer->id,
                'no_services' => $customer->no_services,
                'name'        => $customer->name,
                'ip_address'  => $ip,
                'pppoe_user'  => $customer->pppoe_user,
            ],
            'session'  => $liveSession,
            'result'   => [
                'status'          => $status,
                'is_online'       => $status !== 'offline',
                'packet_loss_pct' => $packetLoss,
                'latency_ms'      => $avgLatency,
                'min_latency_ms'  => $minLatency,
                'max_latency_ms'  => $maxLatency,
                'duration_ms'     => $durationMs,
                'raw_output'      => trim($output),
                'timestamp'       => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Update customer IP Address & PPPoE User manually from Central UI
     */
    public function updateNetworkInfo(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'ip_address' => 'nullable|ip|max:45',
            'pppoe_user' => 'nullable|string|max:128',
        ], [
            'ip_address.ip' => 'Format IP Address tidak valid (gunakan format IPv4 atau IPv6 yang sah).',
        ]);

        $customer->update([
            'ip_address' => $validated['ip_address'] ?? null,
            'pppoe_user' => $validated['pppoe_user'] ?? null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Data jaringan pelanggan {$customer->name} ({$customer->no_services}) berhasil diperbarui.",
                'customer' => [
                    'id'          => $customer->id,
                    'no_services' => $customer->no_services,
                    'name'        => $customer->name,
                    'ip_address'  => $customer->ip_address,
                    'pppoe_user'  => $customer->pppoe_user,
                ]
            ]);
        }

        return back()->with('success', "Data IP/PPPoE pelanggan {$customer->name} ({$customer->no_services}) berhasil diperbarui.");
    }
}
