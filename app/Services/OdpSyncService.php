<?php

namespace App\Services;

use App\Models\BillingInstance;
use App\Models\Customer;
use App\Models\Odp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdpSyncService
{
    /**
     * Synchronize ODP master data across all active billing instances without requiring customer data.
     *
     * @param bool $fresh
     * @param callable|null $onProgress function(string $message, ?int $current, ?int $max)
     * @return array
     */
    public function syncAll(bool $fresh = false, ?callable $onProgress = null): array
    {
        $tenants = BillingInstance::where('is_active', true)->get();
        $results = [];
        $totalSynced = 0;

        foreach ($tenants as $tenant) {
            if ($onProgress) {
                $onProgress("Memproses node [{$tenant->tenant_code}] {$tenant->name}...", null, null);
            }

            $count = $this->syncTenant($tenant, $fresh, $onProgress);
            $results[$tenant->tenant_code] = $count;
            $totalSynced += $count;
        }

        // Post-sync global coordination: propagate surveyed coordinates and photos across shared physical boxes
        $this->propagatePhysicalBoxAttributes();

        return [
            'success'              => true,
            'total_synced'         => $totalSynced,
            'tenants'              => $results,
            'total_physical_boxes' => Odp::distinct('code_odp')->count('code_odp'),
        ];
    }

    /**
     * Synchronize ODP master data for a specific billing instance.
     *
     * @param BillingInstance $tenant
     * @param bool $fresh
     * @param callable|null $onProgress
     * @return int
     */
    public function syncTenant(BillingInstance $tenant, bool $fresh = false, ?callable $onProgress = null): int
    {
        // 1. Try direct database connection first if configured
        if (!empty($tenant->db_database)) {
            try {
                $conn = $tenant->getDatabaseConnection();
                if ($conn) {
                    $conn->getPdo();
                    $synced = $this->syncFromDirectDatabase($tenant, $conn, $fresh, $onProgress);
                    if ($synced > 0) {
                        return $synced;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Direct DB ODP sync failed for [{$tenant->tenant_code}]: " . $e->getMessage());
                if ($onProgress) {
                    $onProgress("Direct DB [{$tenant->tenant_code}] gagal ({$e->getMessage()}), mencoba fallback REST API...", null, null);
                }
            }
        }

        // 2. Fallback to REST API /central/odps if direct DB is not accessible
        return $this->syncFromRestApi($tenant, $onProgress);
    }

    /**
     * Sync ODPs directly from CI3 billing database `m_odp` table.
     */
    protected function syncFromDirectDatabase(BillingInstance $tenant, $conn, bool $fresh, ?callable $onProgress): int
    {
        // Check if m_odp table exists
        $hasTable = false;
        try {
            $tables = array_map(fn($t) => array_values((array)$t)[0], $conn->select("SHOW TABLES"));
            $hasTable = in_array('m_odp', $tables);
        } catch (\Throwable $e) {
            $hasTable = false;
        }

        if (!$hasTable) {
            Log::info("Table m_odp does not exist in DB [{$tenant->db_database}] for tenant [{$tenant->tenant_code}]");
            return 0;
        }

        $remoteRows = $conn->table('m_odp')->get();
        if ($remoteRows->isEmpty()) {
            return 0;
        }

        if ($fresh) {
            // Safe fresh mode: clear ODPs if explicitly requested
            Odp::query()->delete();
        }

        // Preload global surveyed coordinates to inherit if remote ODP has no GPS
        $surveyedCoords = Odp::whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->where('latitude', '!=', '0')
            ->pluck('longitude', 'code_odp')
            ->toArray();

        $surveyedLats = Odp::whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->where('latitude', '!=', '0')
            ->pluck('latitude', 'code_odp')
            ->toArray();

        $surveyedPhotos = Odp::whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->pluck('photo_path', 'code_odp')
            ->toArray();

        // Deduplicate remote rows by normalized code (handling duplicates in remote m_odp)
        $grouped = [];
        foreach ($remoteRows as $row) {
            $rawCode = trim((string)($row->code_odp ?? ''));
            if (empty($rawCode)) continue;

            $clean = strtoupper(trim((string)preg_replace('/^ODP-/i', '', $rawCode)));
            $clean = strtoupper(trim((string)preg_replace('/-C[0-9]+$/i', '', $clean)));
            $normalizedCode = 'ODP-' . $clean;

            if (!isset($grouped[$normalizedCode])) {
                $grouped[$normalizedCode] = $row;
            } else {
                // Keep the record with the most information
                $existing = $grouped[$normalizedCode];
                $existingScore = (!empty($existing->document) && $existing->document !== '-' ? 3 : 0)
                               + (!empty($existing->latitude) && $existing->latitude !== '0' ? 3 : 0)
                               + (!empty($existing->remark) && $existing->remark !== '-' ? 1 : 0);
                $newScore = (!empty($row->document) && $row->document !== '-' ? 3 : 0)
                          + (!empty($row->latitude) && $row->latitude !== '0' ? 3 : 0)
                          + (!empty($row->remark) && $row->remark !== '-' ? 1 : 0);

                if ($newScore > $existingScore) {
                    $grouped[$normalizedCode] = $row;
                }
            }
        }

        $existingOdps = Odp::all()->keyBy('code_odp');
        $totalItems = count($grouped);
        $processed = 0;

        foreach ($grouped as $normalizedCode => $row) {
            $clean = strtoupper(trim((string)preg_replace('/^ODP-/i', '', $normalizedCode)));
            $rawCode = trim((string)($row->code_odp ?? ''));

            $existing = $existingOdps->get($normalizedCode);

            // Coordinates: Remote > Existing record > Global surveyed > Customer sample
            $lat = !empty($row->latitude) && $row->latitude !== '0' ? (string)$row->latitude : null;
            $lng = !empty($row->longitude) && $row->longitude !== '0' ? (string)$row->longitude : null;

            if ((empty($lat) || empty($lng)) && $existing && !empty($existing->latitude) && $existing->latitude !== '0') {
                $lat = $existing->latitude;
                $lng = $existing->longitude;
            }

            if (empty($lat) || empty($lng)) {
                $lat = $surveyedLats[$normalizedCode] ?? null;
                $lng = $surveyedCoords[$normalizedCode] ?? null;
            }

            if (empty($lat) || empty($lng)) {
                $sampleCust = Customer::where(function ($q) use ($normalizedCode, $clean, $rawCode) {
                        $q->where('odp_name', $normalizedCode)
                          ->orWhere('odp_name', $clean)
                          ->orWhere('odp_name', $rawCode);
                    })
                    ->whereNotNull('latitude')
                    ->where('latitude', '!=', '')
                    ->where('latitude', '!=', '0')
                    ->first();

                if ($sampleCust) {
                    $lat = $sampleCust->latitude;
                    $lng = $sampleCust->longitude;
                }
            }

            // Photo: Remote > Existing record > Global surveyed
            $photoPath = !empty($row->document) && $row->document !== '-' ? trim($row->document) : null;
            if (empty($photoPath) && $existing && !empty($existing->photo_path)) {
                $photoPath = $existing->photo_path;
            }
            if (empty($photoPath) && isset($surveyedPhotos[$normalizedCode])) {
                $photoPath = $surveyedPhotos[$normalizedCode];
            }

            // Total ports
            $totalPorts = !empty($row->total_port) ? (int)$row->total_port : 16;
            if ($totalPorts < 1) $totalPorts = 16;

            // Notes
            $notesParts = [];
            if (!empty($row->remark) && trim($row->remark) !== '-') {
                $notesParts[] = trim($row->remark);
            }
            if (!empty($row->color_tube_fo) && trim($row->color_tube_fo) !== '-') {
                $notesParts[] = 'Tube: ' . trim($row->color_tube_fo);
            }
            if (!empty($row->no_pole) && trim($row->no_pole) !== '-' && trim($row->no_pole) !== '0') {
                $notesParts[] = 'Tiang: ' . trim($row->no_pole);
            }
            $notes = !empty($notesParts) ? implode(' | ', $notesParts) : ($existing?->notes ?? null);

            // Calculate used ports across ALL connected customers in Central for this physical ODP
            $usedPorts = Customer::where(function ($q) use ($normalizedCode, $clean, $rawCode) {
                    $q->where('odp_name', $normalizedCode)
                      ->orWhere('odp_name', $clean)
                      ->orWhere('odp_name', $rawCode);
                })
                ->count();

            $status = ($usedPorts >= $totalPorts && $totalPorts > 0) ? 'full' : ($existing?->status ?? 'active');

            Odp::updateOrCreate(
                [
                    'code_odp' => $normalizedCode,
                ],
                [
                    'name'        => $row->name ?? ('ODP ' . $clean),
                    'latitude'    => $lat,
                    'longitude'   => $lng,
                    'total_ports' => $totalPorts,
                    'used_ports'  => $usedPorts,
                    'status'      => $status,
                    'photo_path'  => $photoPath,
                    'notes'       => $notes,
                    'created_by'  => $existing?->created_by ?? 1,
                ]
            );

            $processed++;
            if ($onProgress && $processed % 50 === 0) {
                $onProgress("Menyinkronkan [{$tenant->tenant_code}]...", $processed, $totalItems);
            }
        }

        if ($onProgress) {
            $onProgress("Selesai menyinkronkan {$processed} ODP untuk [{$tenant->tenant_code}].", $processed, $totalItems);
        }

        return $processed;
    }

    /**
     * Fallback to sync via REST API /central/odps if available on CI3
     */
    protected function syncFromRestApi(BillingInstance $tenant, ?callable $onProgress): int
    {
        if (empty($tenant->domain_url)) {
            return 0;
        }

        $domainUrl = rtrim($tenant->domain_url, '/');
        try {
            $response = Http::withoutVerifying()
                ->timeout(12)
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
                        $rawCode = trim((string)($odpItem['code_odp'] ?? $odpItem['code'] ?? $odpItem['name'] ?? ''));
                        if (empty($rawCode)) continue;

                        $clean = strtoupper(trim((string)preg_replace('/^ODP-/i', '', $rawCode)));
                        $clean = strtoupper(trim((string)preg_replace('/-C[0-9]+$/i', '', $clean)));
                        $normalizedCode = 'ODP-' . $clean;

                        $totalPorts = !empty($odpItem['total_ports'])
                            ? (int)$odpItem['total_ports']
                            : (!empty($odpItem['total_port']) ? (int)$odpItem['total_port'] : 16);

                        $lat = !empty($odpItem['latitude']) && $odpItem['latitude'] !== '0' ? (string)$odpItem['latitude'] : null;
                        $lng = !empty($odpItem['longitude']) && $odpItem['longitude'] !== '0' ? (string)$odpItem['longitude'] : null;

                        Odp::updateOrCreate(
                            [
                                'code_odp' => $normalizedCode,
                            ],
                            [
                                'name'        => $odpItem['name'] ?? ('ODP ' . $clean),
                                'latitude'    => $lat,
                                'longitude'   => $lng,
                                'total_ports' => max(16, $totalPorts),
                                'notes'       => $odpItem['notes'] ?? $odpItem['remark'] ?? null,
                                'created_by'  => 1,
                            ]
                        );
                        $count++;
                    }

                    if ($onProgress) {
                        $onProgress("Berhasil menyinkronkan {$count} ODP via REST API untuk [{$tenant->tenant_code}].", $count, $count);
                    }

                    return $count;
                }
            }
        } catch (\Throwable $e) {
            Log::info("REST API /central/odps skipped for [{$tenant->tenant_code}]: " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Cross-propagate surveyed coordinates and photos across physical ODP boxes.
     * If ODP-A1 on node 1 has surveyed coordinates or photo, share them to ODP-A1 on node 2 and node 3.
     */
    protected function propagatePhysicalBoxAttributes(): void
    {
        // 1. Propagate coordinates
        $coords = Odp::whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->where('latitude', '!=', '0')
            ->select('code_odp', 'latitude', 'longitude')
            ->get()
            ->unique('code_odp');

        foreach ($coords as $box) {
            Odp::where('code_odp', $box->code_odp)
                ->where(function ($q) {
                    $q->whereNull('latitude')
                      ->orWhere('latitude', '')
                      ->orWhere('latitude', '0');
                })
                ->update([
                    'latitude'  => $box->latitude,
                    'longitude' => $box->longitude,
                ]);
        }

        // 2. Propagate photos
        $photos = Odp::whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->where('photo_path', '!=', '-')
            ->select('code_odp', 'photo_path')
            ->get()
            ->unique('code_odp');

        foreach ($photos as $box) {
            Odp::where('code_odp', $box->code_odp)
                ->where(function ($q) {
                    $q->whereNull('photo_path')
                      ->orWhere('photo_path', '')
                      ->orWhere('photo_path', '-');
                })
                ->update([
                    'photo_path' => $box->photo_path,
                ]);
        }
    }
}
