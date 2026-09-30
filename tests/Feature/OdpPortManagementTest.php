<?php

namespace Tests\Feature;

use App\Models\BillingInstance;
use App\Models\Customer;
use App\Models\Odp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdpPortManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;
    private User $admin;
    private Odp $odp;
    private BillingInstance $billing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->technician = User::factory()->create([
            'name' => 'Teknisi Test',
            'email' => 'teknisi@test.com',
            'role' => 'technician',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->billing = BillingInstance::create([
            'tenant_code' => 'BILL-TEST-NODE',
            'name' => 'Billing Node Test',
            'api_key' => 'secret-key-123',
            'is_active' => true,
        ]);

        $this->odp = Odp::create([
            'code_odp' => 'ODP-TEST-01',
            'billing_node_id' => $this->billing->id,
            'cluster_name' => 'Cluster Test',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'total_ports' => 8,
            'used_ports' => 0,
            'status' => 'active',
        ]);
    }

    public function test_technician_can_view_odp_ports_with_read_only_access(): void
    {
        $response = $this->actingAs($this->technician)
            ->getJson("/maps/odp/{$this->odp->id}/ports");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_edit' => false,
                'user_role' => 'technician',
                'odp' => [
                    'id' => $this->odp->id,
                    'code_odp' => 'ODP-TEST-01',
                    'total_ports' => 8,
                ],
            ]);

        $data = $response->json();
        $this->assertCount(8, $data['ports']);
        $this->assertEquals(1, $data['ports'][0]['port_number']);
        $this->assertEquals('available', $data['ports'][0]['status']);
    }

    public function test_technician_is_forbidden_from_assigning_ports(): void
    {
        $customer = Customer::create([
            'billing_node_id' => $this->billing->id,
            'remote_customer_id' => 'REMOTE-001',
            'name' => 'Pelanggan Uji',
            'no_services' => '2026001',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->technician)
            ->postJson("/maps/odp/{$this->odp->id}/assign-port", [
                'port_number' => 1,
                'customer_id' => $customer->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Role teknisi hanya memiliki hak baca (read-only).'
            ]);

        // Verify customer was NOT assigned
        $customer->refresh();
        $this->assertNull($customer->port_number);
        $this->assertNull($customer->odp_name);
    }

    public function test_technician_is_forbidden_from_detaching_ports(): void
    {
        $customer = Customer::create([
            'billing_node_id' => $this->billing->id,
            'remote_customer_id' => 'REMOTE-002',
            'name' => 'Pelanggan Terpasang',
            'no_services' => '2026002',
            'odp_name' => 'ODP-TEST-01',
            'port_number' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->technician)
            ->postJson("/maps/odp/{$this->odp->id}/detach-port", [
                'port_number' => 1,
                'customer_id' => $customer->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Role teknisi hanya memiliki hak baca (read-only).'
            ]);

        // Verify customer is still assigned
        $customer->refresh();
        $this->assertEquals(1, $customer->port_number);
        $this->assertEquals('ODP-TEST-01', $customer->odp_name);
    }

    public function test_admin_can_view_odp_ports_with_edit_access(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/maps/odp/{$this->odp->id}/ports");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_edit' => true,
                'user_role' => 'admin',
            ]);
    }

    public function test_admin_can_search_available_customers_scoped_to_billing_node(): void
    {
        $customerInNode = Customer::create([
            'billing_node_id' => $this->billing->id,
            'remote_customer_id' => 'REMOTE-003',
            'name' => 'Siti Soleha',
            'no_services' => '10203040',
            'status' => 'active',
        ]);

        $otherBilling = BillingInstance::create([
            'tenant_code' => 'OTHER-NODE',
            'name' => 'Other Node',
            'api_key' => 'secret-other',
            'is_active' => true,
        ]);

        $customerInOtherNode = Customer::create([
            'billing_node_id' => $otherBilling->id,
            'remote_customer_id' => 'REMOTE-004',
            'name' => 'Siti In Other Node',
            'no_services' => '99999999',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/maps/odp/{$this->odp->id}/search-customers?q=Siti");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $customers = $response->json('customers');
        $this->assertCount(1, $customers);
        $this->assertEquals('Siti Soleha', $customers[0]['name']);
    }

    public function test_admin_can_assign_customer_to_port(): void
    {
        $customer = Customer::create([
            'billing_node_id' => $this->billing->id,
            'remote_customer_id' => 'REMOTE-005',
            'name' => 'Ahmad Dahlan',
            'no_services' => '2026101',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/maps/odp/{$this->odp->id}/assign-port", [
                'port_number' => 2,
                'customer_id' => $customer->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $customer->refresh();
        $this->assertEquals(2, $customer->port_number);
        $this->assertEquals('ODP-TEST-01', $customer->odp_name);

        $this->odp->refresh();
        $this->assertEquals(1, $this->odp->used_ports);
    }

    public function test_admin_can_detach_customer_from_port(): void
    {
        $customer = Customer::create([
            'billing_node_id' => $this->billing->id,
            'remote_customer_id' => 'REMOTE-006',
            'name' => 'Ahmad Dahlan',
            'no_services' => '2026101',
            'odp_name' => 'ODP-TEST-01',
            'port_number' => 2,
            'status' => 'active',
        ]);

        $this->odp->update(['used_ports' => 1]);

        $response = $this->actingAs($this->admin)
            ->postJson("/maps/odp/{$this->odp->id}/detach-port", [
                'port_number' => 2,
                'customer_id' => $customer->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $customer->refresh();
        $this->assertNull($customer->port_number);
        $this->assertNull($customer->odp_name);

        $this->odp->refresh();
        $this->assertEquals(0, $this->odp->used_ports);
    }
}
