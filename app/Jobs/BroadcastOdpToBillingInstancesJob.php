<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BillingInstance;
use App\Models\Odp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastOdpToBillingInstancesJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 180;
    public int $tries = 2;

    public function __construct(
        public Odp $odp
    ) {}

    public function handle(): void
    {
        $tenants = BillingInstance::where('is_active', true)->get();
        $cleanCode = preg_replace('/^ODP-/i', '', $this->odp->code_odp);

        foreach ($tenants as $tenant) {
            // 1. Direct DB sync if connection is available
            try {
                $conn = $tenant->getDatabaseConnection() ?: ($tenant->tenant_code === 'BILL-001' ? DB::connection('billing_ci3') : null);
                if ($conn) {
                    $exists = $conn->table('m_odp')
                        ->where('code_odp', $this->odp->code_odp)
                        ->orWhere('code_odp', $cleanCode)
                        ->exists();

                    if (!$exists) {
                        $conn->table('m_odp')->insert([
                            'code_odp'   => $cleanCode,
                            'latitude'   => $this->odp->latitude,
                            'longitude'  => $this->odp->longitude,
                            'total_port' => $this->odp->total_ports,
                            'remark'     => $this->odp->notes ?? $this->odp->name,
                            'created'    => time(),
                            'create_by'  => 0,
                        ]);
                        Log::info("Auto-added ODP {$cleanCode} to billing DB [{$tenant->tenant_code}]");
                    } else {
                        $conn->table('m_odp')
                            ->where('code_odp', $this->odp->code_odp)
                            ->orWhere('code_odp', $cleanCode)
                            ->update([
                                'latitude'   => $this->odp->latitude,
                                'longitude'  => $this->odp->longitude,
                                'total_port' => $this->odp->total_ports,
                                'remark'     => $this->odp->notes ?? $this->odp->name,
                            ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Direct DB sync failed for ODP {$this->odp->code_odp} to [{$tenant->tenant_code}]: " . $e->getMessage());
            }

            // 2. HTTP push notification if domain_url configured
            if (!empty($tenant->domain_url)) {
                try {
                    \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->timeout(3)
                        ->withHeaders([
                            'X-API-Key' => $tenant->api_key,
                            'Accept'    => 'application/json',
                        ])
                        ->post(rtrim($tenant->domain_url, '/') . '/central/odp_sync', [
                            'code_odp'    => $this->odp->code_odp,
                            'clean_code'  => $cleanCode,
                            'name'        => $this->odp->name,
                            'latitude'    => $this->odp->latitude,
                            'longitude'   => $this->odp->longitude,
                            'total_ports' => $this->odp->total_ports,
                        ]);
                } catch (\Throwable $e) {
                    Log::debug("HTTP webhook skipped or failed for [{$tenant->tenant_code}]: " . $e->getMessage());
                }
            }
        }
    }
}
