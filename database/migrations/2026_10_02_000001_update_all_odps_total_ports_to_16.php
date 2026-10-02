<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Odp;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update column default to 16
        Schema::table('odps', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_ports')->default(16)->change();
        });

        // 2. Update all existing ODP records to have 16 total_ports
        DB::table('odps')->update([
            'total_ports' => 16,
            'updated_at'  => now(),
        ]);

        // 3. Recalculate used_ports and status for each ODP with the new 16-port capacity
        $odps = Odp::all();
        foreach ($odps as $odp) {
            $cleanCode = preg_replace('/^ODP-/i', '', $odp->code_odp);
            $usedCount = Customer::where(function ($q) use ($odp, $cleanCode) {
                    $q->where('odp_name', $odp->code_odp)
                      ->orWhere('odp_name', $cleanCode);
                })
                ->when($odp->billing_node_id, fn($q) => $q->where('billing_node_id', $odp->billing_node_id))
                ->count();

            $status = $odp->status;
            if ($usedCount >= 16 && $status === 'active') {
                $status = 'full';
            } elseif ($status === 'full' && $usedCount < 16) {
                $status = 'active';
            }

            $odp->update([
                'used_ports' => $usedCount,
                'status'     => $status,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_ports')->default(8)->change();
        });

        DB::table('odps')->update([
            'total_ports' => 8,
            'updated_at'  => now(),
        ]);

        $odps = Odp::all();
        foreach ($odps as $odp) {
            $cleanCode = preg_replace('/^ODP-/i', '', $odp->code_odp);
            $usedCount = Customer::where(function ($q) use ($odp, $cleanCode) {
                    $q->where('odp_name', $odp->code_odp)
                      ->orWhere('odp_name', $cleanCode);
                })
                ->when($odp->billing_node_id, fn($q) => $q->where('billing_node_id', $odp->billing_node_id))
                ->count();

            $status = $odp->status;
            if ($usedCount >= 8 && $status === 'active') {
                $status = 'full';
            } elseif ($status === 'full' && $usedCount < 8) {
                $status = 'active';
            }

            $odp->update([
                'used_ports' => $usedCount,
                'status'     => $status,
            ]);
        }
    }
};
