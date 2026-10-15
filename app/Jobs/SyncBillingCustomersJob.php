<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SyncBillingCustomersJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(
        public ?string $tenantCode = null,
        public bool $syncAll = false
    ) {}

    public function handle(): void
    {
        if ($this->syncAll) {
            $instances = \App\Models\BillingInstance::where('is_active', true)->get();
            Log::info("SyncBillingCustomersJob: Fanning out parallel sync jobs for {$instances->count()} active billing instances.");
            foreach ($instances as $instance) {
                if (!empty($instance->tenant_code)) {
                    self::dispatch($instance->tenant_code, false);
                }
            }
            return;
        }

        $params = [];
        if ($this->tenantCode) {
            $params['tenant_code'] = $this->tenantCode;
        }

        try {
            Artisan::call('sync:billing-customers', $params);
            Log::info('SyncBillingCustomersJob completed successfully: ' . json_encode($params));
        } catch (\Throwable $e) {
            Log::error('SyncBillingCustomersJob failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
