<?php

namespace Database\Seeders;

use App\Models\BillingInstance;
use App\Models\Ticket;
use App\Models\TicketTimeline;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestTicketSeeder extends Seeder
{
    public function run(): void
    {
        $tenant1 = BillingInstance::where('tenant_code', 'BILL-001')->first();

        $techA = User::where('email', 'teknisi1@central.local')->first();
        $techB = User::where('email', 'teknisi2@central.local')->first();

        $tkt1 = Ticket::create([
            'ticket_number' => 'TKT-20260814-0001',
            'billing_instance_id' => $tenant1->id,
            'remote_ticket_id' => '101',
            'no_services' => '10293847',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081299887766',
            'customer_address' => 'Jl. Merdeka No. 12, Jakarta',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'category_name' => 'Kabel FO Putus',
            'problem_description' => 'Lampu PON di modem berwarna merah sejak jam 14:00.',
            'status' => 'pending',
            'assigned_technician_id' => $techA->id,
            'created_by_name' => 'Pelanggan Direct',
            'created_by_role' => 'Pelanggan',
        ]);

        TicketTimeline::create([
            'ticket_id' => $tkt1->id,
            'user_id' => null,
            'status' => 'pending',
            'remark' => 'Tiket dibuat via PT Gayuh Media Informatika',
        ]);

    }
}
