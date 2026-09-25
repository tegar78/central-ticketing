<?php

namespace Tests\Feature;

use App\Models\BillingInstance;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_valid_api_key(): void
    {
        $response = $this->postJson('/api/v1/tickets', [
            'no_services' => '10203040',
            'customer_name' => 'Testing Customer',
            'problem_description' => 'Test Modem Mati',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['success' => false]);
    }

    public function test_can_ingest_ticket_from_billing_instance(): void
    {
        $tenant = BillingInstance::create([
            'tenant_code' => 'BILL-TEST',
            'name' => 'Test Billing App',
            'api_key' => 'test-secret-api-key',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => 'test-secret-api-key',
        ])->postJson('/api/v1/tickets', [
            'remote_ticket_id' => 'CI-999',
            'no_services' => '10203040',
            'customer_name' => 'Budi Testing',
            'customer_phone' => '08123456789',
            'customer_address' => 'Jl. Test No 1',
            'category_name' => 'Modem Mati',
            'problem_description' => 'Lampu modem mati total.',
            'created_by_name' => 'Admin Test',
            'created_by_role' => 'Administrator',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tickets', [
            'billing_instance_id' => $tenant->id,
            'remote_ticket_id' => 'CI-999',
            'customer_name' => 'Budi Testing',
            'status' => 'pending',
        ]);
    }

    public function test_technician_only_sees_assigned_tickets(): void
    {
        $tenant = BillingInstance::create([
            'tenant_code' => 'BILL-TEST-2',
            'name' => 'Test Billing App 2',
            'api_key' => 'test-secret-key-2',
            'is_active' => true,
        ]);

        $tech1 = User::create([
            'name' => 'Teknisi Satu',
            'email' => 'tech1@test.local',
            'password' => bcrypt('password'),
            'role' => 'technician',
        ]);

        $tech2 = User::create([
            'name' => 'Teknisi Dua',
            'email' => 'tech2@test.local',
            'password' => bcrypt('password'),
            'role' => 'technician',
        ]);

        $ticket1 = Ticket::create([
            'ticket_number' => 'TKT-001',
            'billing_instance_id' => $tenant->id,
            'customer_name' => 'Customer Tech 1',
            'no_services' => '111',
            'problem_description' => 'Problem 1',
            'status' => 'pending',
            'assigned_technician_id' => $tech1->id,
        ]);

        $ticket2 = Ticket::create([
            'ticket_number' => 'TKT-002',
            'billing_instance_id' => $tenant->id,
            'customer_name' => 'Customer Tech 2',
            'no_services' => '222',
            'problem_description' => 'Problem 2',
            'status' => 'pending',
            'assigned_technician_id' => $tech2->id,
        ]);

        // Login as Tech 1
        $this->actingAs($tech1);

        $response = $this->get('/tickets');
        $response->assertStatus(200);
        $response->assertSee('Customer Tech 1');
        $response->assertDontSee('Customer Tech 2');
    }
}
