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
    protected $signature = 'sync:billing-customers {tenant_code? : Tenant code (e.g. BILL-001)} {--all : Sync all active billing instances}';

    protected $description = 'Sinkronisasi data pelanggan langsung dari database MySQL billing CI3 (bill-gyh.gayuh.net.id) ke Central Ticket System';

    public function handle()
    {
        $tenantCode = $this->argument('tenant_code');

        if (!$tenantCode && !$this->option('all')) {
            $tenantCode = 'BILL-001'; // Default: bill-gyh.gayuh.net.id
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
            $this->info("  Domain: {$tenant->domain_url}");
            $this->info("══════════════════════════════════════════════════");

            $syncedCount = $this->syncFromDirectDatabase($tenant);
            $totalSynced += $syncedCount;
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("  SINKRONISASI SELESAI: Total {$totalSynced} pelanggan tersinkronkan");
        $this->info("═══════════════════════════════════════════════════════");

        return 0;
    }

    /**
     * Sync customers directly from CI3 MySQL database (bill2-gyh)
     */
    private function syncFromDirectDatabase(BillingInstance $tenant): int
    {
        try {
            // Test connection to billing_ci3 database
            $testConn = DB::connection('billing_ci3')->getPdo();
            $this->info("  ✓ Koneksi database billing_ci3 berhasil");
        } catch (\Exception $e) {
            $this->error("  ✗ Gagal koneksi ke database billing_ci3: " . $e->getMessage());
            return 0;
        }

        // Query the CI3 customer table with ODP join
        $this->info("  → Mengambil data pelanggan dari tabel `customer`...");

        $remoteCustomers = DB::connection('billing_ci3')
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
                // Map CI3 status to Central status
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
