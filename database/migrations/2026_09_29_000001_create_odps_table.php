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
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_node_id')->nullable()->constrained('billing_instances')->nullOnDelete();
            $table->string('code_odp', 100)->index();
            $table->string('name')->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->unsignedSmallInteger('total_ports')->default(8);
            $table->unsignedSmallInteger('used_ports')->default(0);
            $table->enum('status', ['active', 'full', 'maintenance', 'damaged'])->default('active')->index();
            $table->string('photo_path')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Composite unique index: satu billing node tidak boleh memiliki kode ODP yang sama
            $table->unique(['billing_node_id', 'code_odp'], 'odps_billing_node_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odps');
    }
};
