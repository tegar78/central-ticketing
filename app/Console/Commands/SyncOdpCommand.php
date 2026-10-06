<?php

namespace App\Console\Commands;

use App\Models\BillingInstance;
use App\Models\Odp;
use App\Services\OdpSyncService;
use Illuminate\Console\Command;

class SyncOdpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'odp:sync
                            {--tenant= : Kode tenant billing spesifik (e.g. BILL-001, BILL-NEW)}
                            {--node-id= : ID billing instance spesifik}
                            {--fresh : Kosongkan data ODP untuk tenant terpilih sebelum sinkronisasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan seluruh master data ODP langsung dari database billing ke central-ticketing (hanya data ODP tanpa memerlukan data pelanggan)';

    /**
     * Execute the console command.
     */
    public function handle(OdpSyncService $syncService): int
    {
        $this->newLine();
        $this->info("╔══════════════════════════════════════════════════════════════════════╗");
        $this->info("║             SINKRONISASI MASTER DATA ODP CENTRAL-TICKETING           ║");
        $this->info("║        (Direct Database Import — Hanya Data ODP Tanpa Pelanggan)     ║");
        $this->info("╚══════════════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $tenantCode = $this->option('tenant');
        $nodeId = $this->option('node-id');
        $fresh = (bool) $this->option('fresh');

        $query = BillingInstance::where('is_active', true);
        if (!empty($tenantCode)) {
            $query->where('tenant_code', $tenantCode);
        } elseif (!empty($nodeId)) {
            $query->where('id', $nodeId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->error("  ✗ Tidak ada billing instance aktif yang ditemukan untuk kriteria ini.");
            return 1;
        }

        $totalSynced = 0;
        $summary = [];

        foreach ($tenants as $tenant) {
            $this->info("▶ Menyinkronkan Server Billing: [{$tenant->tenant_code}] {$tenant->name}");
            if (!empty($tenant->db_database)) {
                $this->line("  • Target Database : `{$tenant->db_database}` @ {$tenant->db_host}");
            } else {
                $this->line("  • Mode REST API   : {$tenant->domain_url}/central/odps");
            }

            $bar = null;
            $count = $syncService->syncTenant($tenant, $fresh, function ($msg, $current, $max) use (&$bar) {
                if ($max !== null && $max > 0) {
                    if (!$bar) {
                        $bar = $this->output->createProgressBar($max);
                        $bar->setFormat("  [%bar%] %current%/%max% ODP (%percent%%)");
                        $bar->start();
                    }
                    $bar->setProgress($current);
                } else {
                    $this->line("  → {$msg}");
                }
            });

            if ($bar) {
                $bar->finish();
                $this->newLine();
            }

            $this->info("  ✓ Berhasil menyinkronkan {$count} ODP untuk [{$tenant->tenant_code}]");
            $this->newLine();

            $summary[] = [
                'Tenant Code' => $tenant->tenant_code,
                'Tenant Name' => $tenant->name,
                'Source DB'   => $tenant->db_database ?: '(REST API)',
                'ODP Synced'  => $count,
                'Status'      => $count > 0 ? '✓ Sukses' : '⚠ 0 Data',
            ];

            $totalSynced += $count;
        }

        $this->table(['Tenant Code', 'Tenant Name', 'Source DB', 'ODP Synced', 'Status'], $summary);

        $distinctPhysicalBoxes = Odp::distinct('code_odp')->count('code_odp');
        $totalOdpRows = Odp::count();

        $this->info("══════════════════════════════════════════════════════════════════════");
        $this->info("  TOTAL ODP TERIMPOR      : {$totalSynced} data ODP");
        $this->info("  TOTAL BARIS RECORD ODP  : {$totalOdpRows} records");
        $this->info("  TOTAL KOTAK FISIK ODP   : {$distinctPhysicalBoxes} titik fisik unik");
        $this->info("══════════════════════════════════════════════════════════════════════");
        $this->newLine();

        return 0;
    }
}
