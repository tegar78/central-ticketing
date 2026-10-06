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

        // Scoped search with billing_node_id
        $response = $this->actingAs($this->admin)
            ->getJson("/maps/odp/{$this->odp->id}/search-customers?q=Siti&billing_node_id={$this->billing->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $customers = $response->json('customers');
        $this->assertCount(1, $customers);
        $this->assertEquals('Siti Soleha', $customers[0]['name']);

        // Unscoped search across all billings
        $globalResponse = $this->actingAs($this->admin)
            ->getJson("/maps/odp/{$this->odp->id}/search-customers?q=Siti");

        $globalResponse->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertCount(2, $globalResponse->json('customers'));
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

    public function test_odp_code_is_globally_unique_in_database(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempting to create duplicate physical ODP with the same code must fail
        Odp::create([
            'code_odp' => 'ODP-TEST-01',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'total_ports' => 8,
            'used_ports' => 0,
            'status' => 'active',
        ]);
    }

    public function test_odp_index_serves_single_canonical_physical_box_with_multi_tenant_customers(): void
    {
        $billing2 = BillingInstance::create([
            'tenant_code' => 'BILL-TEST-NODE-2',
            'name' => 'Billing Node Test 2',
            'api_key' => 'secret-key-456',
            'is_active' => true,
        ]);

        // Client 1 from Node 1 on Port 1
        Customer::create([
            'billing_node_id' => $this->billing->id,
            'remote_customer_id' => 'REMOTE-A1',
            'name' => 'Client A',
            'no_services' => '1001',
            'odp_name' => 'ODP-TEST-01',
            'port_number' => 1,
            'status' => 'active',
        ]);

        // Client 2 from Node 2 on Port 5
        Customer::create([
            'billing_node_id' => $billing2->id,
            'remote_customer_id' => 'REMOTE-B1',
            'name' => 'Client B',
            'no_services' => '2001',
            'odp_name' => 'ODP-TEST-01',
            'port_number' => 5,
            'status' => 'active',
        ]);

        $this->odp->update(['used_ports' => 2]);

        $response = $this->actingAs($this->admin)
            ->get('/maps/odp');

        $response->assertStatus(200);

        // Verify only 1 physical ODP row is returned in odpList and totalOdps is 1
        $odpList = $response->viewData('odpList');
        $totalOdps = $response->viewData('totalOdps');
        $totalUsedPorts = $response->viewData('totalUsedPorts');

        $this->assertEquals(1, $totalOdps);
        $this->assertCount(1, $odpList);

        $row = $odpList->first();
        $this->assertEquals('ODP-TEST-01', $row->code_odp);
        $this->assertEquals(2, $row->used_ports);
        $this->assertEquals(2, $totalUsedPorts);
    }

    public function test_port_matrix_displays_clients_from_their_respective_billing_nodes(): void
    {
        $billing2 = BillingInstance::create([
            'tenant_code' => 'BILL-NEW-TENANT',
            'name' => 'Tenant Baru',
            'api_key' => 'secret-789',
            'is_active' => true,
        ]);

        // Client 1 from Node 1 on Port 2
        Customer::create([
            'billing_node_id' => $this->billing->id,
            'remote_customer_id' => 'CUST-N1',
            'name' => 'Customer Node Satu',
            'no_services' => '1111',
            'odp_name' => 'ODP-TEST-01',
            'port_number' => 2,
            'status' => 'active',
        ]);

        // Client 2 from Node 2 on Port 6
        Customer::create([
            'billing_node_id' => $billing2->id,
            'remote_customer_id' => 'CUST-N2',
            'name' => 'Customer Node Dua',
            'no_services' => '2222',
            'odp_name' => 'ODP-TEST-01',
            'port_number' => 6,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/maps/odp/{$this->odp->id}/ports");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'odp' => [
                    'code_odp' => 'ODP-TEST-01',
                    'used_ports' => 2,
                ],
            ]);

        $ports = $response->json('ports');
        $this->assertCount(8, $ports);

        // Port 1 is available
        $this->assertEquals('available', $ports[0]['status']);
        $this->assertNull($ports[0]['customer']);

        // Port 2 has Customer Node Satu from BILL-TEST-NODE
        $this->assertEquals('occupied', $ports[1]['status']);
        $this->assertEquals('Customer Node Satu', $ports[1]['customer']['name']);
        $this->assertEquals('BILL-TEST-NODE', $ports[1]['customer']['billing_node']['tenant_code']);

        // Port 6 has Customer Node Dua from BILL-NEW-TENANT
        $this->assertEquals('occupied', $ports[5]['status']);
        $this->assertEquals('Customer Node Dua', $ports[5]['customer']['name']);
        $this->assertEquals('BILL-NEW-TENANT', $ports[5]['customer']['billing_node']['tenant_code']);
    }

    public function test_odp_index_page_1_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/maps/odp?page=1');

        $response->assertStatus(200);
        $response->assertSee('ODP-TEST-01');
    }
}


