<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class CustomerSyncController extends Controller
{
    /**
     * Idempotent Ingestion & Sync API endpoint for CI3 Billing Instances
     * Supports single customer payload and batch array payloads.
     */
    public function sync(SyncCustomerRequest $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant identification failed.',
            ], 401);
        }

        $payload = $request->validated();
        $now = Carbon::now();

        // Detect if payload is batch array or single customer object
        $isBatch = is_array($payload) && array_is_list($payload);
        $records = $isBatch ? $payload : [$payload];

        $upsertData = [];

        foreach ($records as $item) {
            $upsertData[] = [
                'billing_node_id' => $tenant->id,
                'remote_customer_id' => (int) $item['remote_customer_id'],
                'no_services' => (string) $item['no_services'],
                'name' => (string) $item['name'],
                'phone' => isset($item['phone']) ? (string) $item['phone'] : null,
                'address' => isset($item['address']) ? (string) $item['address'] : null,
                'odp_name' => isset($item['odp_name']) ? (string) $item['odp_name'] : null,
                'latitude' => isset($item['latitude']) ? (string) $item['latitude'] : null,
                'longitude' => isset($item['longitude']) ? (string) $item['longitude'] : null,
                'package_name' => isset($item['package_name']) ? (string) $item['package_name'] : null,
                'monthly_fee' => isset($item['monthly_fee']) ? (float) $item['monthly_fee'] : null,
                'status' => isset($item['status']) ? (string) $item['status'] : 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Perform bulk upsert based on composite unique index ['billing_node_id', 'remote_customer_id']
        Customer::upsert(
            $upsertData,
            ['billing_node_id', 'remote_customer_id'],
            [
                'no_services',
                'name',
                'phone',
                'address',
                'odp_name',
                'latitude',
                'longitude',
                'package_name',
                'monthly_fee',
                'status',
                'updated_at'
            ]
        );

        $count = count($upsertData);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menyinkronkan {$count} data pelanggan dari billing node [{$tenant->tenant_code}] {$tenant->name}.",
            'processed_count' => $count,
            'billing_node' => [
                'id' => $tenant->id,
                'tenant_code' => $tenant->tenant_code,
                'name' => $tenant->name,
            ]
        ], 200);
    }
}
