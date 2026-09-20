<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SQLite workaround: recreate the entire table without the enum constraint.
     */
    public function up(): void
    {
        // Step 1: Backup existing data
        $existingCustomers = DB::table('customers')->get();

        // Step 2: Drop and recreate table with string status
        Schema::dropIfExists('customers');

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
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->unique(['billing_node_id', 'remote_customer_id'], 'customers_billing_node_remote_id_unique');
        });

        // Step 3: Restore existing data
        foreach ($existingCustomers as $customer) {
            DB::table('customers')->insert([
                'id' => $customer->id,
                'billing_node_id' => $customer->billing_node_id,
                'remote_customer_id' => $customer->remote_customer_id,
                'no_services' => $customer->no_services,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'odp_name' => $customer->odp_name,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'package_name' => $customer->package_name,
                'monthly_fee' => $customer->monthly_fee,
                'status' => $customer->status ?? 'active',
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        // Not reversible cleanly
    }
};
