<?php

namespace Tests\Feature;

use App\Models\BillingInstance;
use App\Models\Odp;
use App\Models\User;
use App\Services\OdpSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdpSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $technician;
    private BillingInstance $billing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'  => 'Admin Test',
            'email' => 'admin@test.com',
            'role'  => 'admin',
        ]);

        $this->technician = User::factory()->create([
            'name'  => 'Teknisi Test',
            'email' => 'teknisi@test.com',
            'role'  => 'technician',
        ]);

        $this->billing = BillingInstance::create([
            'tenant_code' => 'BILL-SYNC-TEST',
            'name'        => 'Billing Sync Test',
            'api_key'     => 'test-key-sync',
            'is_active'   => true,
        ]);
    }

    public function test_sync_odp_requires_authentication(): void
    {
        $response = $this->postJson(route('odp.sync'));
        $response->assertStatus(401);
    }

    public function test_technician_cannot_sync_odp(): void
    {
        $response = $this->actingAs($this->technician)->postJson(route('odp.sync'));
        $response->assertStatus(403);
    }

    public function test_admin_can_trigger_odp_sync_via_controller(): void
    {
        // Mock OdpSyncService
        $this->mock(OdpSyncService::class, function ($mock) {
            $mock->shouldReceive('syncAll')
                ->once()
                ->with(false)
                ->andReturn([
                    'success'              => true,
                    'total_synced'         => 10,
                    'tenants'              => ['BILL-SYNC-TEST' => 10],
                    'total_physical_boxes' => 10,
                ]);
        });

        $response = $this->actingAs($this->admin)->postJson(route('odp.sync'));

        $response->assertStatus(200)
            ->assertJson([
                'success'      => true,
                'total_synced' => 10,
            ]);
    }

    public function test_odp_sync_artisan_command_executes_successfully(): void
    {
        $this->mock(OdpSyncService::class, function ($mock) {
            $mock->shouldReceive('syncTenant')
                ->once()
                ->andReturn(5);
        });

        $this->artisan('odp:sync', ['--tenant' => 'BILL-SYNC-TEST'])
            ->assertExitCode(0);
    }
}
