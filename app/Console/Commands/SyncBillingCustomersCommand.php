<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BillingInstance;
use App\Models\Customer;
use App\Models\Odp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SyncBillingCustomersCommand extends Command
{
    protected $signature = 'sync:billing-customers {tenant_code? : Tenant code (e.g. BILL-001)} {--all : Sync all active billing instances} {--fresh : Delete existing customers before syncing}';

    protected $description = 'Sinkronisasi data pelanggan dari server billing CodeIgniter 3 via RESTful API ke Central Ticket System';

    public function handle()
    {
        $tenantCode = $this->argument('tenant_code');

        if (!$tenantCode && !$this->option('all')) {
            $tenantCode = 'BILL-001'; // Default: BILL-001
        }

        $query = BillingInstance::where('is_active', true);
        if ($tenantCode) {
            $query->where('tenant_code', $tenantCode);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->error("Tidak ditemukan Billing Instance aktif" . ($tenantCode ? " dengan tenant_code [{$tenantCode}]" : ""));
            return 1;
        }

        $totalSynced = 0;

        foreach ($tenants as $tenant) {
            $this->info("");
            $this->info("══════════════════════════════════════════════════");
            $this->info("  Billing Instance: [{$tenant->tenant_code}] {$tenant->name}");
            $this->info("  Domain URL: {$tenant->domain_url}");
            $this->info("══════════════════════════════════════════════════");

            $syncedCount = $this->syncViaRestApi($tenant);

            // If REST API didn't return data and direct DB is configured, fallback
            if ($syncedCount === 0 && !empty($tenant->db_database)) {
                $this->warn("  ⚠ REST API tidak merespon, mencoba fallback direct database...");
                $syncedCount = $this->syncFromDirectDatabase($tenant);
            }

            // Always enrich port numbers and ODP associations if direct DB is accessible
            $this->enrichPortsFromDirectDatabase($tenant);

            // Sync master ODPs if /central/odps exists on CI3
            $this->syncOdpsViaRestApi($tenant);

            // Auto-discover any missing ODPs referenced by customers
            $this->autoDiscoverMissingOdps($tenant);

            // Automatically recalculate ODP capacity and full/active status
            $this->recalculateOdpCapacity($tenant);

            $totalSynced += $syncedCount;
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("  SINKRONISASI SELESAI: Total {$totalSynced} pelanggan tersinkronkan");
        $this->info("═══════════════════════════════════════════════════════");

        return 0;
    }

    /**
     * Sync customers via RESTful API (HTTP GET/POST)
     */
    private function syncViaRestApi(BillingInstance $tenant): int
    {
        if (empty($tenant->domain_url)) {
            $this->warn("  ⚠ Domain URL belum diisi untuk [{$tenant->tenant_code}].");
            return 0;
        }

        $domainUrl = rtrim($tenant->domain_url, '/');
        $this->info("  → Menghubungi REST API server billing [{$domainUrl}]...");

        // 1. Endpoint Utama: CI3 Controller Central.php (/central/customers)
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'X-API-Key' => $tenant->api_key,
                    'Accept'    => 'application/json',
                ])
                ->get("{$domainUrl}/central/customers");

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? (is_array($data) && array_is_list($data) ? $data : null);

                if (!empty($items) && is_array($items)) {
                    $this->info("  ✓ Ditemukan " . count($items) . " pelanggan dari REST API /central/customers");
                    return $this->ingestCustomerArray($tenant, $items);
                }
            }
        } catch (\Exception $e) {
            $this->line("  ℹ Info: /central/customers tidak merespon: " . $e->getMessage());
        }

        // 2. Endpoint CI3 /api/customers (Format REST Server)
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'X-API-Key' => $tenant->api_key,
                    'Accept'    => 'application/json',
                ])
                ->get("{$domainUrl}/api/customers");

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? (is_array($data) && array_is_list($data) ? $data : null);

                if (!empty($items) && is_array($items)) {
                    $this->info("  ✓ Ditemukan " . count($items) . " pelanggan dari REST API /api/customers");
                    return $this->ingestCustomerArray($tenant, $items);
                }
            }
        } catch (\Exception $e) {
            $this->line("  ℹ Info: /api/customers tidak dapat dihubungi: " . $e->getMessage());
        }

        // 3. Endpoint CI3 /sync_central/customers (CI3 push batch ke Central Hub)
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'X-API-Key' => $tenant->api_key,
                    'Accept'    => 'application/json',
                ])
                ->get("{$domainUrl}/sync_central/customers");

            if ($response->successful()) {
                $json = $response->json();
                $processed = $json['total_processed'] ?? $json['processed_count'] ?? 0;
                if ($processed > 0) {
                    $this->info("  ✓ REST API Sync sukses: {$processed} pelanggan disinkronkan via /sync_central/customers");
                    return (int) $processed;
                }
            }
        } catch (\Exception $e) {
            $this->line("  ℹ Info: /sync_central/customers tidak dapat dihubungi: " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Ingest customer array received via REST API
     */
    private function ingestCustomerArray(BillingInstance $tenant, array $items): int
    {
        $now = Carbon::now();
        $upsertData = [];

        // Preload ALL Master ODPs across Central to normalize codes globally
        $allOdps = Odp::all()->keyBy('code_odp');
        $cleanOdpMap = [];
        foreach ($allOdps as $code => $odpObj) {
            $clean = strtoupper(trim((string)preg_replace('/^ODP-/i', '', $code)));
            $cleanOdpMap[$clean] = $code;
        }

        foreach ($items as $cust) {
            $remoteId = $cust['remote_customer_id'] ?? $cust['customer_id'] ?? $cust['id'] ?? null;
            $noServices = $cust['no_services'] ?? $cust['no_layanan'] ?? null;
            $name = $cust['name'] ?? $cust['nama'] ?? null;

            if (!$remoteId || !$noServices || !$name) {
                continue;
            }

            $isIsolated = (!empty($cust['connection']) && (int)$cust['connection'] === 1)
                || (!empty($cust['is_isolir']) && (int)$cust['is_isolir'] === 1);

            $status = $isIsolated ? 'isolated' : $this->mapStatus($cust['status'] ?? $cust['c_status'] ?? 'active');

            // Standardize and normalize ODP code
            $rawOdp = trim((string)($cust['odp_name'] ?? $cust['odp_code'] ?? ''));
            $finalOdpName = null;
            if (!empty($rawOdp)) {
                $clean = strtoupper(trim((string)preg_replace('/^ODP-/i', '', $rawOdp)));
                // Also clean any cluster suffix like -C1, -C2
                $clean = strtoupper(trim((string)preg_replace('/-C[0-9]+$/i', '', $clean)));
                $normalizedCode = 'ODP-' . $clean;

                $finalOdpName = $allOdps->get($normalizedCode)?->code_odp
                    ?? ($cleanOdpMap[$clean] ?? null)
                    ?? $normalizedCode;
            }

            $portNumber = !empty($cust['no_port_odp']) 
                ? (int) $cust['no_port_odp'] 
                : (!empty($cust['port_number']) ? (int) $cust['port_number'] : null);

            $ipAddress = !empty($cust['ip_address']) ? (string) $cust['ip_address'] : (!empty($cust['ip']) ? (string) $cust['ip'] : (!empty($cust['ip_local']) ? (string) $cust['ip_local'] : null));
            $pppoeUser = !empty($cust['pppoe_user']) ? (string) $cust['pppoe_user'] : (!empty($cust['user_mikrotik']) ? (string) $cust['user_mikrotik'] : (!empty($cust['username']) ? (string) $cust['username'] : null));

            $upsertData[] = [
                'billing_node_id'    => $tenant->id,
                'remote_customer_id' => (int) $remoteId,
                'no_services'        => (string) $noServices,
                'name'               => (string) $name,
                'phone'              => $cust['phone'] ?? $cust['no_wa'] ?? null,
                'address'            => isset($cust['address']) ? trim($cust['address']) : null,
                'odp_name'           => $finalOdpName,
                'port_number'        => $portNumber,
                'ip_address'         => $ipAddress,
                'pppoe_user'         => $pppoeUser,
                'latitude'           => isset($cust['latitude']) ? (string) $cust['latitude'] : null,
                'longitude'          => isset($cust['longitude']) ? (string) $cust['longitude'] : null,
                'package_name'       => $cust['package_name'] ?? $cust['user_profile'] ?? null,
                'monthly_fee'        => isset($cust['monthly_fee']) ? (float) $cust['monthly_fee'] : (isset($cust['cust_amount']) ? (float) $cust['cust_amount'] : null),
                'status'             => $status,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        if (empty($upsertData)) {
            return 0;
        }

        if ($this->option('fresh')) {
            $deleted = Customer::where('billing_node_id', $tenant->id)->delete();
            $this->info("  → Membersihkan {$deleted} data pelanggan lama untuk node [{$tenant->tenant_code}]");
        } else {
            $remoteIds = array_column($upsertData, 'remote_customer_id');
            $deleted = Customer::where('billing_node_id', $tenant->id)
                ->whereNotIn('remote_customer_id', $remoteIds)
                ->delete();
            if ($deleted > 0) {
                $this->info("  → Membersihkan {$deleted} data pelanggan lama yang sudah tidak ada di database billing");
            }
        }

        Customer::upsert(
            $upsertData,
            ['billing_node_id', 'remote_customer_id'],
            ['no_services', 'name', 'phone', 'address', 'odp_name', 'port_number', 'ip_address', 'pppoe_user', 'latitude', 'longitude', 'package_name', 'monthly_fee', 'status', 'updated_at']
        );

        $this->info("  ✓ Berhasil menyimpan " . count($upsertData) . " pelanggan ke database Central.");
        return count($upsertData);
    }

    /**
     * Sync customers directly from CI3 MySQL database (Fallback method)
     */
    private function syncFromDirectDatabase(BillingInstance $tenant): int
    {
        $dbName = $tenant->db_database ?: env('BILLING_CI3_DB_DATABASE', 'bill3-gyh');

        try {
            /** @var \Illuminate\Database\Connection $conn */
            $conn = $tenant->getDatabaseConnection() ?: DB::connection('billing_ci3');
            $conn->getPdo();
            $this->info("  ✓ Fallback: Koneksi database [{$dbName}] berhasil");
        } catch (\Exception $e) {
            $this->error("  ✗ Gagal koneksi ke database [{$dbName}]: " . $e->getMessage());
            return 0;
        }

        $this->info("  → Mengambil data pelanggan dari tabel `customer`...");

        $remoteCustomers = $conn
            ->table('customer')
            ->leftJoin('m_odp', 'customer.id_odp', '=', 'm_odp.id_odp')
            ->select([
                'customer.customer_id',
                'customer.name',
                'customer.no_services',
                'customer.email',
                'customer.address',
                'customer.no_wa',
                'customer.c_status',
                'customer.latitude',
                'customer.longitude',
                'customer.user_profile',
                'customer.cust_amount',
                'customer.user_mikrotik as pppoe_user',
                'customer.id_odp',
                'customer.no_port_odp',
                'm_odp.code_odp as odp_code',
            ])
            ->get();

        $this->info("  → Ditemukan: {$remoteCustomers->count()} pelanggan di database CI3");

        if ($remoteCustomers->isEmpty()) {
            $this->warn("  ⚠ Tidak ada data pelanggan ditemukan.");
            return 0;
        }

        if ($this->option('fresh')) {
            $deleted = Customer::where('billing_node_id', $tenant->id)->delete();
            $this->info("  → Membersihkan {$deleted} data pelanggan lama untuk node [{$tenant->tenant_code}]");
        } else {
            $remoteIds = $remoteCustomers->pluck('customer_id')->map(fn($id) => (int)$id)->toArray();
            $deleted = Customer::where('billing_node_id', $tenant->id)
                ->whereNotIn('remote_customer_id', $remoteIds)
                ->delete();
            if ($deleted > 0) {
                $this->info("  → Membersihkan {$deleted} data pelanggan lama yang sudah tidak ada di database billing");
            }
        }

        $now = Carbon::now();
        $batchSize = 500;
        $batches = $remoteCustomers->chunk($batchSize);
        $syncedCount = 0;

        $bar = $this->output->createProgressBar($remoteCustomers->count());
        $bar->setFormat("  [%bar%] %current%/%max% pelanggan (%percent%%)");
        $bar->start();

        foreach ($batches as $batch) {
            $upsertData = [];

            foreach ($batch as $cust) {
                $status = $this->mapStatus($cust->c_status);

                $upsertData[] = [
                    'billing_node_id'    => $tenant->id,
                    'remote_customer_id' => (int) $cust->customer_id,
                    'no_services'        => (string) $cust->no_services,
                    'name'               => (string) $cust->name,
                    'phone'              => !empty($cust->no_wa) ? (string) $cust->no_wa : null,
                    'address'            => !empty($cust->address) ? trim((string) $cust->address) : null,
                    'odp_name'           => !empty($cust->odp_code) ? (string) $cust->odp_code : null,
                    'port_number'        => !empty($cust->no_port_odp) ? (int) $cust->no_port_odp : null,
                    'ip_address'         => !empty($cust->ip_address) ? (string) $cust->ip_address : null,
                    'pppoe_user'         => !empty($cust->pppoe_user) ? (string) $cust->pppoe_user : (!empty($cust->user_mikrotik) ? (string) $cust->user_mikrotik : null),
                    'latitude'           => !empty($cust->latitude) ? (string) $cust->latitude : null,
                    'longitude'          => !empty($cust->longitude) ? (string) $cust->longitude : null,
                    'package_name'       => !empty($cust->user_profile) ? (string) $cust->user_profile : null,
                    'monthly_fee'        => !empty($cust->cust_amount) ? (float) $cust->cust_amount : null,
                    'status'             => $status,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }

            Customer::upsert(
                $upsertData,
                ['billing_node_id', 'remote_customer_id'],
                ['no_services', 'name', 'phone', 'address', 'odp_name', 'port_number', 'ip_address', 'pppoe_user', 'latitude', 'longitude', 'package_name', 'monthly_fee', 'status', 'updated_at']
            );

            $syncedCount += count($upsertData);
            $bar->advance(count($upsertData));
        }

        $bar->finish();
        $this->newLine();
        $this->info("  ✓ SUKSES: {$syncedCount} pelanggan tersinkronkan dari [{$tenant->tenant_code}] {$tenant->name}");

        return $syncedCount;
    }

    /**
     * Enrich customers with ODP and port numbers from direct database if available
     */
    private function enrichPortsFromDirectDatabase(BillingInstance $tenant): void
    {
        try {
            $conn = $tenant->getDatabaseConnection() ?: ($tenant->tenant_code === 'BILL-001' ? DB::connection('billing_ci3') : null);
            if (!$conn) {
                return;
            }

            $portRows = $conn->table('customer')
                ->leftJoin('m_odp', 'customer.id_odp', '=', 'm_odp.id_odp')
                ->whereNotNull('customer.no_port_odp')
                ->where('customer.no_port_odp', '!=', '')
                ->where('customer.no_port_odp', '!=', 0)
                ->select([
                    'customer.customer_id',
                    'customer.id_odp',
                    'customer.no_port_odp',
                    'm_odp.code_odp as odp_code',
                ])
                ->get();

            if ($portRows->isNotEmpty()) {
                $allOdps = Odp::where('billing_node_id', $tenant->id)->get()->keyBy('code_odp');
                $allOdpsById = Odp::where('billing_node_id', $tenant->id)->get()->keyBy('id');

                $updatedCount = 0;
                foreach ($portRows as $row) {
                    $rawOdp = (string) ($row->odp_code ?? '');
                    $base = explode('-', $rawOdp)[0];

                    $odp = $allOdps->get($rawOdp)
                        ?? $allOdps->get('ODP-' . $rawOdp)
                        ?? $allOdps->get('ODP-' . $base)
                        ?? $allOdpsById->get((int) $row->id_odp);

                    $updateData = [
                        'port_number' => (int) $row->no_port_odp,
                    ];
                    if ($odp) {
                        $updateData['odp_name'] = $odp->code_odp;
                    }

                    $updated = Customer::where('billing_node_id', $tenant->id)
                        ->where('remote_customer_id', (int) $row->customer_id)
                        ->update($updateData);

                    if ($updated) {
                        $updatedCount++;
                    }
                }
                $this->info("  ✓ Sinkronisasi Port: {$updatedCount} pelanggan berhasil disematkan nomor port ODP dari database billing.");
            }
        } catch (\Throwable $e) {
            Log::info("Enrichment ports from direct DB skipped: " . $e->getMessage());
        }
    }

    /**
     * Recalculate ODP used_ports and status for a billing instance
     */
    private function recalculateOdpCapacity(BillingInstance $tenant): void
    {
        try {
            $odps = Odp::all();
            foreach ($odps as $odp) {
                $cleanCode = preg_replace('/^ODP-/i', '', $odp->code_odp);
                $usedCount = Customer::where(function ($q) use ($odp, $cleanCode) {
                        $q->where('odp_name', $odp->code_odp)
                          ->orWhere('odp_name', $cleanCode);
                    })
                    ->when($odp->billing_node_id, fn($q) => $q->where('billing_node_id', $odp->billing_node_id))
                    ->count();

                $status = ($usedCount >= $odp->total_ports && $odp->status === 'active') ? 'full' : $odp->status;
                if ($odp->status === 'full' && $usedCount < $odp->total_ports) {
                    $status = 'active';
                }

                $odp->update([
                    'used_ports' => $usedCount,
                    'status'     => $status,
                ]);
            }
            $this->info("  ✓ Kapasitas dan status ODP berhasil diperbarui secara otomatis.");
        } catch (\Throwable $e) {
            Log::info("Recalculate ODP capacity skipped: " . $e->getMessage());
        }
    }

    /**
     * Sync ODPs via RESTful API (/central/odps) if available on CI3
     */
    private function syncOdpsViaRestApi(BillingInstance $tenant): int
    {
        if (empty($tenant->domain_url)) {
            return 0;
        }

        $domainUrl = rtrim($tenant->domain_url, '/');
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'X-API-Key' => $tenant->api_key,
                    'Accept'    => 'application/json',
                ])
                ->get("{$domainUrl}/central/odps");

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? (is_array($data) && array_is_list($data) ? $data : null);

                if (!empty($items) && is_array($items)) {
                    $count = 0;
                    foreach ($items as $odpItem) {
                        $rawCode = trim((string)($odpItem['code_odp'] ?? $odpItem['name'] ?? ''));
                        if (empty($rawCode)) continue;

                        $clean = strtoupper(trim(preg_replace('/^ODP-/i', '', $rawCode)));
                        $normalizedCode = 'ODP-' . $clean;

                        $totalPorts = !empty($odpItem['total_ports']) 
                            ? (int)$odpItem['total_ports'] 
                            : (!empty($odpItem['total_port']) ? (int)$odpItem['total_port'] : 16);

                        Odp::updateOrCreate(
                            [
                                'billing_node_id' => $tenant->id,
                                'code_odp'        => $normalizedCode,
                            ],
                            [
                                'name'        => $odpItem['name'] ?? ('ODP ' . $clean),
                                'latitude'    => !empty($odpItem['latitude']) ? (string)$odpItem['latitude'] : null,
                                'longitude'   => !empty($odpItem['longitude']) ? (string)$odpItem['longitude'] : null,
                                'total_ports' => $totalPorts,
                                'notes'       => $odpItem['notes'] ?? $odpItem['remark'] ?? null,
                                'created_by'  => 1,
                            ]
                        );
                        $count++;
                    }

                    if ($count > 0) {
                        $this->info("  ✓ Berhasil menyinkronkan {$count} master ODP dari REST API /central/odps");
                        return $count;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore if /central/odps is not implemented yet on CI3
        }

        return 0;
    }

    /**
     * Auto-discover missing ODPs from customer records for this billing instance
     */
    private function autoDiscoverMissingOdps(BillingInstance $tenant): void
    {
        $odpNames = Customer::where('billing_node_id', $tenant->id)
            ->whereNotNull('odp_name')
            ->where('odp_name', '!=', '')
            ->pluck('odp_name')
            ->unique();

        if ($odpNames->isEmpty()) {
            return;
        }

        $existingOdps = Odp::all()->keyBy('code_odp');
        $createdCount = 0;

        foreach ($odpNames as $rawName) {
            $clean = strtoupper(trim(preg_replace('/^ODP-/i', '', $rawName)));
            $clean = strtoupper(trim(preg_replace('/-C[0-9]+$/i', '', $clean)));
            $code = 'ODP-' . $clean;

            if (!$existingOdps->has($code)) {
                $sampleCust = Customer::where('billing_node_id', $tenant->id)
                    ->where(function ($q) use ($code, $clean, $rawName) {
                        $q->where('odp_name', $code)
                          ->orWhere('odp_name', $clean)
                          ->orWhere('odp_name', $rawName);
                    })
                    ->whereNotNull('latitude')
                    ->where('latitude', '!=', '')
                    ->where('latitude', '!=', '0')
                    ->first();

                $maxPort = Customer::where('billing_node_id', $tenant->id)
                    ->where(function ($q) use ($code, $clean, $rawName) {
                        $q->where('odp_name', $code)
                          ->orWhere('odp_name', $clean)
                          ->orWhere('odp_name', $rawName);
                    })
                    ->max('port_number');

                $totalPorts = max(16, (int)$maxPort);

                $newOdp = Odp::create([
                    'billing_node_id' => $tenant->id,
                    'code_odp'        => $code,
                    'name'            => 'ODP ' . $clean,
                    'latitude'        => $sampleCust?->latitude,
                    'longitude'       => $sampleCust?->longitude,
                    'total_ports'     => $totalPorts,
                    'used_ports'      => 0,
                    'status'          => 'active',
                    'created_by'      => 1,
                ]);

                $existingOdps->put($code, $newOdp);
                $createdCount++;
            }

            // Keep customer odp_name normalized
            Customer::where('billing_node_id', $tenant->id)
                ->where('odp_name', $rawName)
                ->update(['odp_name' => $code]);
        }

        if ($createdCount > 0) {
            $this->info("  ✓ Auto-discover: Menemukan dan mendaftarkan {$createdCount} ODP baru dari data pelanggan.");
        }
    }

    /**
     * Map CI3 c_status values to Central Ticket System status
     */
    private function mapStatus(?string $ci3Status): string
    {
        if (empty($ci3Status)) return 'active';

        $status = strtolower(trim($ci3Status));

        return match(true) {
            str_contains($status, 'isolir') || str_contains($status, 'isolate') => 'isolated',
            str_contains($status, 'non') || str_contains($status, 'inactive')   => 'inactive',
            str_contains($status, 'free') || str_contains($status, 'gratis')     => 'free',
            str_contains($status, 'menunggu') || str_contains($status, 'pending') || str_contains($status, 'wait') => 'pending',
            str_contains($status, 'aktif') || str_contains($status, 'active')    => 'active',
            default                                                              => 'active',
        };
    }
}
