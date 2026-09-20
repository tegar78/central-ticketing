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
        Schema::create('billing_instances', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_code')->unique();
            $table->string('name');
            $table->string('domain_url')->nullable();
            $table->string('api_key')->unique();
            $table->string('callback_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_instances');
    }
};
