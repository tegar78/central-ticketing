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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_node_id')->constrained('billing_instances')->cascadeOnDelete();
            $table->unsignedBigInteger('remote_customer_id')->index();
            $table->string('no_services', 50)->index();
            $table->string('name')->index();
            $table->string('phone', 25)->nullable()->index();
            $table->text('address')->nullable();
            $table->string('odp_name', 100)->nullable()->index();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->string('package_name', 100)->nullable();
            $table->decimal('monthly_fee', 12, 2)->nullable();
            $table->enum('status', ['active', 'isolated', 'inactive'])->default('active')->index();
            $table->timestamps();

            // Composite Unique Index for idempotent upserts per billing server
            $table->unique(['billing_node_id', 'remote_customer_id'], 'customers_billing_node_remote_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
