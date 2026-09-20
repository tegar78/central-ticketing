<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY') ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key is missing. Please provide X-API-KEY header.',
            ], 401);
        }

        $tenant = \App\Models\BillingInstance::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API Key.',
            ], 401);
        }

        // Attach tenant object to request
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
