<?php

use App\Models\Customer;
use App\Models\Odp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Data Consolidation: Merge duplicate ODP rows into 1 canonical row per physical box (code_odp)
        $allOdps = Odp::all();
        $byCode = $allOdps->groupBy('code_odp');

        $deleteIds = [];

        foreach ($byCode as $code => $group) {
            if ($group->count() <= 1) {
                // Already single row, just recalculate global used_ports
                $odp = $group->first();
                $cleanCode = preg_replace('/^ODP-/i', '', $code);
                $usedCount = Customer::where(function ($q) use ($code, $cleanCode) {
                    $q->where('odp_name', $code)->orWhere('odp_name', $cleanCode);
                })->count();

                $odp->update([
                    'total_ports' => max(16, $odp->total_ports),
                    'used_ports'  => $usedCount,
                    'status'      => ($usedCount >= max(16, $odp->total_ports)) ? 'full' : $odp->status,
                ]);
                continue;
            }

            // Sort to find best canonical row: prefer row with photo, valid coords, notes
            $sorted = $group->sort(function ($a, $b) {
                $scoreA = (!empty($a->photo_path) && $a->photo_path !== '-' ? 10 : 0)
                        + (!empty($a->latitude) && $a->latitude !== '0' ? 5 : 0)
                        + (!empty($a->notes) ? 2 : 0);
                $scoreB = (!empty($b->photo_path) && $b->photo_path !== '-' ? 10 : 0)
                        + (!empty($b->latitude) && $b->latitude !== '0' ? 5 : 0)
                        + (!empty($b->notes) ? 2 : 0);
                if ($scoreA !== $scoreB) {
                    return $scoreB <=> $scoreA;
                }
                return $a->id <=> $b->id;
            })->values();

            $best = $sorted->first();
            $others = $sorted->slice(1);

            $photo = $best->photo_path;
            $lat = $best->latitude;
            $lng = $best->longitude;
            $notes = $best->notes;

            foreach ($others as $row) {
                if ((empty($photo) || $photo === '-') && !empty($row->photo_path) && $row->photo_path !== '-') {
                    $photo = $row->photo_path;
                }
                if ((empty($lat) || $lat === '0') && !empty($row->latitude) && $row->latitude !== '0') {
                    $lat = $row->latitude;
                    $lng = $row->longitude;
                }
                if (empty($notes) && !empty($row->notes)) {
                    $notes = $row->notes;
                }
                $deleteIds[] = $row->id;
            }

            $cleanCode = preg_replace('/^ODP-/i', '', $code);
            $usedCount = Customer::where(function ($q) use ($code, $cleanCode) {
                $q->where('odp_name', $code)->orWhere('odp_name', $cleanCode);
            })->count();

            $totalPorts = max(16, $best->total_ports);
            $best->update([
                'latitude'    => $lat,
                'longitude'   => $lng,
                'photo_path'  => $photo,
                'notes'       => $notes,
                'total_ports' => $totalPorts,
                'used_ports'  => $usedCount,
                'status'      => ($usedCount >= $totalPorts) ? 'full' : $best->status,
            ]);
        }

        // Delete duplicate records
        if (!empty($deleteIds)) {
            foreach (array_chunk($deleteIds, 500) as $chunk) {
                DB::table('odps')->whereIn('id', $chunk)->delete();
            }
        }

        // 2. Schema Modification: Drop billing_node_id and make code_odp unique globally
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            Schema::create('odps_unified_temp', function (Blueprint $table) {
                $table->id();
                $table->string('code_odp', 100)->unique('odps_code_odp_unique');
                $table->string('name')->nullable();
                $table->string('latitude', 50)->nullable();
                $table->string('longitude', 50)->nullable();
                $table->unsignedSmallInteger('total_ports')->default(16);
                $table->unsignedSmallInteger('used_ports')->default(0);
                $table->string('status')->default('active')->index();
                $table->string('photo_path')->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            DB::statement('INSERT INTO odps_unified_temp (id, code_odp, name, latitude, longitude, total_ports, used_ports, status, photo_path, address, notes, created_by, created_at, updated_at) SELECT id, code_odp, name, latitude, longitude, total_ports, used_ports, status, photo_path, address, notes, created_by, created_at, updated_at FROM odps');
            Schema::drop('odps');
            Schema::rename('odps_unified_temp', 'odps');

            Schema::enableForeignKeyConstraints();
        } else {
            Schema::table('odps', function (Blueprint $table) {
                try {
                    $table->dropForeign(['billing_node_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key did not exist
                }

                try {
                    $table->dropUnique('odps_billing_node_code_unique');
                } catch (\Throwable $e) {
                    // Ignore if index was named differently
                }

                $table->dropColumn('billing_node_id');
                $table->unique('code_odp', 'odps_code_odp_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropUnique('odps_code_odp_unique');
            $table->foreignId('billing_node_id')->nullable()->after('id')->constrained('billing_instances')->nullOnDelete();
            $table->unique(['billing_node_id', 'code_odp'], 'odps_billing_node_code_unique');
        });
    }
};
