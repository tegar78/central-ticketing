<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BillingInstance;
use App\Models\Customer;
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
            $response = \Illuminate\Support\Facades\Http::timeout(15)
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
            $response = \Illuminate\Support\Facades\Http::timeout(15)
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
            $response = \Illuminate\Support\Facades\Http::timeout(15)
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

        foreach ($items as $cust) {
            $remoteId = $cust['remote_customer_id'] ?? $cust['customer_id'] ?? $cust['id'] ?? null;
            $noServices = $cust['no_services'] ?? $cust['no_layanan'] ?? null;
            $name = $cust['name'] ?? $cust['nama'] ?? null;

            if (!$remoteId || !$noServices || !$name) {
                continue;
            }

            $upsertData[] = [
                'billing_node_id'    => $tenant->id,
                'remote_customer_id' => (int) $remoteId,
                'no_services'        => (string) $noServices,
                'name'               => (string) $name,
                'phone'              => $cust['phone'] ?? $cust['no_wa'] ?? null,
                'address'            => isset($cust['address']) ? trim($cust['address']) : null,
                'odp_name'           => $cust['odp_name'] ?? $cust['odp_code'] ?? null,
                'latitude'           => isset($cust['latitude']) ? (string) $cust['latitude'] : null,
                'longitude'          => isset($cust['longitude']) ? (string) $cust['longitude'] : null,
                'package_name'       => $cust['package_name'] ?? $cust['user_profile'] ?? null,
                'monthly_fee'        => isset($cust['monthly_fee']) ? (float) $cust['monthly_fee'] : (isset($cust['cust_amount']) ? (float) $cust['cust_amount'] : null),
                'status'             => $this->mapStatus($cust['status'] ?? $cust['c_status'] ?? 'active'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        if (empty($upsertData)) {
            return 0;
        }

        Customer::upsert(
            $upsertData,
            ['billing_node_id', 'remote_customer_id'],
            ['no_services', 'name', 'phone', 'address', 'odp_name', 'latitude', 'longitude', 'package_name', 'monthly_fee', 'status', 'updated_at']
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
                'customer.id_odp',
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
                ['no_services', 'name', 'phone', 'address', 'odp_name', 'latitude', 'longitude', 'package_name', 'monthly_fee', 'status', 'updated_at']
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
     * Map CI3 c_status values to Central Ticket System status
     */
    private function mapStatus(?string $ci3Status): string
    {
        if (empty($ci3Status)) return 'active';

        $status = strtolower(trim($ci3Status));

        return match(true) {
            str_contains($status, 'aktif')     => 'active',
            str_contains($status, 'active')    => 'active',
            str_contains($status, 'isolir')    => 'isolated',
            str_contains($status, 'non')       => 'inactive',
            str_contains($status, 'nonaktif')  => 'inactive',
            str_contains($status, 'inactive')  => 'inactive',
            str_contains($status, 'menunggu')  => 'pending',
            str_contains($status, 'free')      => 'free',
            default                            => 'active',
        };
    }
}
