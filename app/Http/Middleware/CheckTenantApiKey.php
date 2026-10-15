<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\BillingInstance;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY') ?? $request->bearerToken();

        if (blank($apiKey)) {
            if ($request->has('api_key')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'API Key must be provided securely via X-API-KEY or Authorization Bearer header, not via query string or body.',
                ], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse([
                'success' => false,
                'message' => 'API Key is missing. Please provide X-API-KEY header.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Cache lookup tenant ID by key hash and optional tenant_code to support shared API keys
        $tenantCode = $request->header('X-Tenant-Code') ?? $request->input('tenant_code');
        $keyHash = hash('sha256', (string) $apiKey);
        $cacheKey = "tenant_api_key_id:{$keyHash}" . ($tenantCode ? ":{$tenantCode}" : '');

        $tenantId = Cache::remember($cacheKey, 3600, function () use ($apiKey, $tenantCode) {
            $query = BillingInstance::query()
                ->where('is_active', true)
                ->where(function ($q) use ($apiKey) {
                    $q->where('api_key', hash('sha256', (string) $apiKey))
                      ->orWhere('api_key', (string) $apiKey);
                });

            if (!empty($tenantCode)) {
                $query->where('tenant_code', strtoupper((string) $tenantCode));
            }

            return $query->value('id');
        });

        if (!$tenantId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid or inactive API Key.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tenant = Cache::remember("billing_instance_model:{$tenantId}", 3600, function () use ($tenantId) {
            return BillingInstance::find($tenantId);
        });

        if (!$tenant || !$tenant->is_active) {
            Cache::forget("tenant_api_key_id:{$keyHash}");
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid or inactive API Key.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Attach tenant object to request attributes
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
