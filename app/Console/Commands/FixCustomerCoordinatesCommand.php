<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixCustomerCoordinatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:fix-coordinates {--dry-run : Run without making actual database changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sanitize, swap inverted coordinates, and normalize GPS points for customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? 'Running in DRY-RUN mode...' : 'Sanitizing customer GPS coordinates...');

        $customers = Customer::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->get(['id', 'name', 'latitude', 'longitude']);

        $updatedCount = 0;

        foreach ($customers as $c) {
            $parsed = $this->parseAndNormalize($c->latitude, $c->longitude);

            if ($parsed !== null) {
                $newLat = round($parsed['lat'], 6);
                $newLng = round($parsed['lng'], 6);

                $oldLat = trim((string)$c->latitude);
                $oldLng = trim((string)$c->longitude);

                if ((string)$newLat !== $oldLat || (string)$newLng !== $oldLng) {
                    $this->line("Fixing #{$c->id} {$c->name}: [{$oldLat}, {$oldLng}] -> [{$newLat}, {$newLng}]");

                    if (!$isDryRun) {
                        DB::table('customers')
                            ->where('id', $c->id)
                            ->update([
                                'latitude' => (string)$newLat,
                                'longitude' => (string)$newLng,
                                'updated_at' => now(),
                            ]);
                    }

                    $updatedCount++;
                }
            }
        }

        $this->info("Completed. Total records " . ($isDryRun ? "would be updated: " : "updated: ") . $updatedCount);

        return Command::SUCCESS;
    }

    /**
     * Parse and normalize coordinates, handling swapped lat/lng, DMS, and dirty strings.
     */
    private function parseAndNormalize($rawLat, $rawLng): ?array
    {
        $cleanLatStr = trim((string)$rawLat, " \t\n\r\0\x0B,\"'");
        $cleanLngStr = trim((string)$rawLng, " \t\n\r\0\x0B,\"'");

        $lat = is_numeric($cleanLatStr) ? (float)$cleanLatStr : $this->parseDms($rawLat);
        $lng = is_numeric($cleanLngStr) ? (float)$cleanLngStr : $this->parseDms($rawLng);

        if ($lat === null || $lng === null) {
            return null;
        }

        // Detect inverted coordinates (e.g. lat was entered as 106.xxx and lng as -6.xxx)
        if ($lat > 6 && $lat <= 141 && $lng >= -11 && $lng <= 6) {
            $temp = $lat;
            $lat = $lng;
            $lng = $temp;
        }

        // Validate within Indonesian geographical boundary (Latitude -11 to 6, Longitude 95 to 141)
        if ($lat >= -11 && $lat <= 6 && $lng >= 95 && $lng <= 141) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        return null;
    }

    /**
     * Parse DMS format (e.g. 6°08'49.5"S or S 106°39'29.1"E) into decimal degrees
     */
    private function parseDms($coord): ?float
    {
        if (preg_match('/(\d+)[°\x{00B0}\x{2550}\x{256C}\s]+(\d+)[\'′\s]+([\d.]+)/u', (string)$coord, $m)) {
            $deg = (float)$m[1];
            $min = (float)$m[2];
            $sec = (float)$m[3];
            $dec = $deg + ($min / 60) + ($sec / 3600);
            if (stripos($coord, 'S') !== false || stripos($coord, 'W') !== false) {
                $dec = -$dec;
            }
            return $dec;
        }

        return null;
    }
}
