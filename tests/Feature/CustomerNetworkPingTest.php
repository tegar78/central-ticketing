<?php

namespace Tests\Feature;

use App\Models\BillingInstance;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerNetworkPingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private BillingInstance $billingInstance;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->billingInstance = BillingInstance::create([
            'tenant_code' => 'BILL-TEST-PING',
            'name' => 'Server Testing Ping',
            'domain_url' => 'https://bill-test.local',
            'api_key' => 'secret-key-test',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'billing_node_id' => $this->billingInstance->id,
            'remote_customer_id' => 881,
            'no_services' => '2026881001',
            'name' => 'Budi Santoso Network',
            'phone' => '081234567890',
            'address' => 'Jl. Jaringan No. 12',
            'ip_address' => '127.0.0.1',
            'pppoe_user' => 'budi_net@gayuh.id',
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: Customer model can store and retrieve ip_address and pppoe_user
     */
    public function test_customer_model_stores_ip_address_and_pppoe_user(): void
    {
        $this->assertEquals('127.0.0.1', $this->customer->ip_address);
        $this->assertEquals('budi_net@gayuh.id', $this->customer->pppoe_user);

        $this->assertDatabaseHas('customers', [
            'id' => $this->customer->id,
            'ip_address' => '127.0.0.1',
            'pppoe_user' => 'budi_net@gayuh.id',
        ]);
    }

    /**
     * Test 2: Customer scopeSearch successfully matches ip_address and pppoe_user
     */
    public function test_customer_scope_search_finds_by_ip_and_pppoe(): void
    {
        // Search by IP
        $foundByIp = Customer::search('127.0.0.1')->get();
        $this->assertTrue($foundByIp->contains('id', $this->customer->id));

        // Search by PPPoE user
        $foundByPppoe = Customer::search('budi_net')->get();
        $this->assertTrue($foundByPppoe->contains('id', $this->customer->id));

        // Search non-existent
        $notFound = Customer::search('999.999.999.999')->get();
        $this->assertFalse($notFound->contains('id', $this->customer->id));
    }

    /**
     * Test 3: Unauthenticated user cannot access ping or network update routes
     */
    public function test_unauthenticated_user_cannot_access_ping_or_network_update(): void
    {
        $pingResponse = $this->postJson("/customers/{$this->customer->id}/ping", [
            'ip_address' => '127.0.0.1',
        ]);
        $pingResponse->assertStatus(401);

        $updateResponse = $this->putJson("/customers/{$this->customer->id}/network", [
            'ip_address' => '10.0.0.1',
        ]);
        $updateResponse->assertStatus(401);
    }

    /**
     * Test 4: Ping endpoint rejects empty IP with HTTP 422
     */
    public function test_ping_rejects_empty_ip_with_422(): void
    {
        $emptyCustomer = Customer::create([
            'billing_node_id' => $this->billingInstance->id,
            'remote_customer_id' => 882,
            'no_services' => '2026881002',
            'name' => 'Pelanggan Tanpa IP',
            'ip_address' => null,
            'pppoe_user' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->postJson("/customers/{$emptyCustomer->id}/ping", [
            'ip_address' => '',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status'  => 'error',
            ]);
    }

    /**
     * Test 5: Command Injection defense (CWE-78) strictly blocks shell characters
     */
    public function test_ping_strictly_blocks_command_injection_attempts(): void
    {
        $maliciousPayloads = [
            '127.0.0.1; whoami',
            '127.0.0.1 && dir',
            '127.0.0.1 | ls',
            '$(whoami)',
            '`id`',
            '127.0.0.1 & echo hacked',
            '192.168.1.1\ncat /etc/passwd',
            'invalid_ip_format',
        ];

        foreach ($maliciousPayloads as $payload) {
            $response = $this->actingAs($this->user)->postJson("/customers/{$this->customer->id}/ping", [
                'ip_address' => $payload,
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'status'  => 'invalid_ip',
                ]);
        }
    }

    /**
     * Test 6: Ping executes safely on valid loopback IP (127.0.0.1)
     */
    public function test_ping_executes_safely_on_valid_loopback_ip(): void
    {
        $response = $this->actingAs($this->user)->postJson("/customers/{$this->customer->id}/ping", [
            'ip_address' => '127.0.0.1',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'customer' => [
                    'id',
                    'no_services',
                    'name',
                    'ip_address',
                    'pppoe_user',
                ],
                'result' => [
                    'status',
                    'is_online',
                    'packet_loss_pct',
                    'latency_ms',
                    'duration_ms',
                    'raw_output',
                    'timestamp',
                ]
            ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('127.0.0.1', $data['customer']['ip_address']);
        $this->assertContains($data['result']['status'], ['online', 'unstable', 'offline']);
    }

    /**
     * Test 7: Update network info endpoint saves IP and PPPoE username
     */
    public function test_update_network_info_successfully_saves_ip_and_pppoe(): void
    {
        $response = $this->actingAs($this->user)->putJson("/customers/{$this->customer->id}/network", [
            'ip_address' => '192.168.100.25',
            'pppoe_user' => 'user_baru@gayuh.id',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'customer' => [
                    'id' => $this->customer->id,
                    'ip_address' => '192.168.100.25',
                    'pppoe_user' => 'user_baru@gayuh.id',
                ]
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $this->customer->id,
            'ip_address' => '192.168.100.25',
            'pppoe_user' => 'user_baru@gayuh.id',
        ]);
    }

    /**
     * Test 8: Update network info rejects invalid IP format
     */
     public function test_update_network_info_validates_ip_format(): void
     {
         $response = $this->actingAs($this->user)->putJson("/customers/{$this->customer->id}/network", [
             'ip_address' => '999.888.777.666_invalid',
             'pppoe_user' => 'test_user',
         ]);

         $response->assertStatus(422)
             ->assertJsonValidationErrors(['ip_address']);
     }

    /**
     * Test 9: Ping automatically discovers IP and PPPoE from active MikroTik session
     */
    public function test_ping_auto_discovers_ip_from_mikrotik_active_session(): void
    {
        $targetCustomer = Customer::create([
            'billing_node_id' => $this->billingInstance->id,
            'remote_customer_id' => 999,
            'no_services' => '00170',
            'name' => 'Auto Discover Customer',
            'ip_address' => null,
            'pppoe_user' => null,
            'status' => 'active',
        ]);

        Http::fake([
            'https://bill-test.local/central/customer_network/00170' => Http::response([
                'status' => true,
                'data' => [
                    'no_services'  => '00170',
                    'name'         => 'Auto Discover Customer',
                    'pppoe_user'   => 'user_00170@gayuh.id',
                    'router_alias' => 'RO ZTE C300',
                    'is_online'    => true,
                    'ip_address'   => '127.0.0.1',
                    'uptime'       => '3d 21h',
                    'caller_id'    => '64:58:AD:C0:3F:11',
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson("/customers/{$targetCustomer->id}/ping");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'customer' => [
                    'id'          => $targetCustomer->id,
                    'no_services' => '00170',
                    'ip_address'  => '127.0.0.1',
                    'pppoe_user'  => 'user_00170@gayuh.id',
                ],
                'session' => [
                    'router_alias' => 'RO ZTE C300',
                    'is_online'    => true,
                    'uptime'       => '3d 21h',
                ]
            ]);

        // Verify that database was updated with auto-discovered IP and PPPoE
        $this->assertDatabaseHas('customers', [
            'id' => $targetCustomer->id,
            'ip_address' => '127.0.0.1',
            'pppoe_user' => 'user_00170@gayuh.id',
        ]);
    }

    /**
     * Test 10: Ping handles offline MikroTik session with appropriate status
     */
    public function test_ping_handles_offline_mikrotik_session(): void
    {
        $offlineCustomer = Customer::create([
            'billing_node_id' => $this->billingInstance->id,
            'remote_customer_id' => 1000,
            'no_services' => '001619',
            'name' => 'Offline Customer',
            'ip_address' => null,
            'pppoe_user' => 'user_001619',
            'status' => 'active',
        ]);

        Http::fake([
            'https://bill-test.local/central/customer_network/001619' => Http::response([
                'status' => true,
                'data' => [
                    'no_services'  => '001619',
                    'name'         => 'Offline Customer',
                    'pppoe_user'   => 'user_001619',
                    'router_alias' => 'RO ZTE C300',
                    'is_online'    => false,
                    'ip_address'   => null,
                    'uptime'       => null,
                    'caller_id'    => null,
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson("/customers/{$offlineCustomer->id}/ping");

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'status'  => 'offline_mikrotik',
            ]);
    }
}
