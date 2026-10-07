<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BillingInstance;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchBillingWebhookJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;
    public int $timeout = 10;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public BillingInstance $billingInstance,
        public array $payload,
        public ?int $ticketId = null
    ) {}

    public function handle(): void
    {
        $url = $this->resolveCallbackUrl($this->billingInstance);
        if (!$url) {
            return;
        }

        try {
            $timestamp = time();
            $payloadJson = json_encode($this->payload, JSON_THROW_ON_ERROR);
            $signature = hash_hmac('sha256', "{$timestamp}.{$payloadJson}", $this->billingInstance->api_key ?? '');

            $verifySsl = (bool) env('BILLING_VERIFY_SSL', false);
            $client = $verifySsl
                ? Http::timeout(8)
                : Http::withoutVerifying()->timeout(8);

            $response = $client->withHeaders([
                'X-Central-Signature' => $signature,
                'X-Central-Timestamp' => (string) $timestamp,
                'X-API-KEY'           => $this->billingInstance->api_key,
                'Content-Type'        => 'application/json',
                'Accept'              => 'application/json',
            ])->post($url, $this->payload);

            if ($response->successful()) {
                if ($this->ticketId) {
                    $resData = $response->json();
                    if (!empty($resData['help_id'])) {
                        Ticket::where('id', $this->ticketId)->update(['remote_ticket_id' => $resData['help_id']]);
                    }
                }
            } else {
                $snippet = substr($response->body(), 0, 200);
                Log::warning("Billing webhook failed for {$this->billingInstance->name} (HTTP {$response->status()}): {$snippet}");
            }
        } catch (\Throwable $e) {
            Log::warning("Billing webhook exception for {$this->billingInstance->name}: " . $e->getMessage());
        }
    }

    private function resolveCallbackUrl(BillingInstance $instance): ?string
    {
        $url = $instance->callback_url;
        if (empty($url) || str_contains($url, '/help/api_callback')) {
            $domain = rtrim((string) $instance->domain_url, '/');
            return !empty($domain) ? "{$domain}/central/callback" : null;
        }
        return $url;
    }
}
