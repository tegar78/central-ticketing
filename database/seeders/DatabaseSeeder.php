<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Users
        User::create([
            'name' => 'Admin Ticket Central',
            'email' => 'admin@central.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Operator Helpdesk',
            'email' => 'operator@central.local',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'phone' => '081234567891',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Teknisi Gayuh A',
            'email' => 'teknisi1@central.local',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'phone' => '081234567892',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Teknisi Gayuh B',
            'email' => 'teknisi2@central.local',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'phone' => '081234567893',
            'is_active' => true,
        ]);

        // Create Sample Tenants/Billing Instances
        \App\Models\BillingInstance::updateOrCreate(
            ['tenant_code' => 'BILL-001'],
            [
                'name' => 'PT Gayuh Media Informatika',
                'domain_url' => 'https://billingtest.gayuh.net.id.test',
                'api_key' => 'key-bill-001-secret-12345',
                'callback_url' => 'https://billingtest.gayuh.net.id.test/central/callback',
                'is_active' => true,
                'db_host' => '127.0.0.1',
                'db_port' => '3306',
                'db_database' => 'bill3-gyh',
                'db_username' => 'root',
                'db_password' => '',
            ]
        );


    }
}
