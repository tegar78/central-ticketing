<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('status', 'tickets_status_index');
            $table->index('no_services', 'tickets_no_services_index');
            $table->index('created_at', 'tickets_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_status_index');
            $table->dropIndex('tickets_no_services_index');
            $table->dropIndex('tickets_created_at_index');
        });
    }
};
