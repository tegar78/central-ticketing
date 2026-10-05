<?php

namespace Tests\Feature;

use App\Models\BillingInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Deactivated user cannot log in even with correct credentials
     */
    public function test_deactivated_user_cannot_login(): void
    {
        $inactiveUser = User::factory()->create([
            'email' => 'inactive_' . time() . '@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $inactiveUser->email,
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test 2: Active user can log in successfully
     */
    public function test_active_user_can_login(): void
    {
        $activeUser = User::factory()->create([
            'email' => 'active_' . time() . '@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $activeUser->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($activeUser);
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Test 3: Logged-in admin cannot deactivate their own account (Anti Self-Lockout)
     */
    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
            'is_active' => '0',
        ]);

        $response->assertSessionHas('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        $this->assertTrue($admin->fresh()->is_active);
    }

    /**
     * Test 4: Logged-in admin cannot demote their own role (Anti Self-Lockout)
     */
    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'technician',
            'is_active' => '1',
        ]);

        $response->assertSessionHas('error', 'Anda tidak dapat mengubah role Administrator pada akun Anda sendiri.');
        $this->assertEquals('admin', $admin->fresh()->role);
    }

    /**
     * Test 5: Tenant API Key via query string is rejected, header is required
     */
    public function test_tenant_api_key_must_be_in_header_not_query_string(): void
    {
        $tenant = BillingInstance::first() ?? BillingInstance::create([
            'tenant_code' => 'BILL-TEST-KEY',
            'name' => 'Test Tenant',
            'api_key' => 'test-secret-key-12345',
            'is_active' => true,
        ]);

        // Attempt via query string should be rejected with 400
        $queryResponse = $this->postJson('/api/v1/sync-customer?api_key=' . $tenant->api_key, [
            'remote_customer_id' => 1,
            'no_services' => 'TEST-001',
            'name' => 'John Doe',
        ]);
        $queryResponse->assertStatus(400);

        // Attempt via X-API-KEY header should pass auth
        $headerResponse = $this->withHeaders([
            'X-API-KEY' => $tenant->api_key,
        ])->postJson('/api/v1/sync-customer', [
            'remote_customer_id' => 99999,
            'no_services' => 'TEST-999',
            'name' => 'Valid Header Customer',
        ]);
        $headerResponse->assertStatus(200);
    }

    /**
     * Test 6: SecurityHeaders middleware attaches CSP and security headers
     */
    public function test_security_headers_are_attached(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }

    /**
     * Test 7: User password requires minimum 8 characters
     */
    public function test_user_password_requires_minimum_eight_characters(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Attempt creation with 6-character password should fail
        $failResponse = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Weak Password User',
            'email' => 'weak_' . time() . '@example.com',
            'role' => 'operator',
            'password' => '123456',
        ]);
        $failResponse->assertSessionHasErrors('password');

        // Attempt creation with 8-character password should succeed
        $passResponse = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Strong Password User',
            'email' => 'strong_' . time() . '@example.com',
            'role' => 'operator',
            'password' => 'ValidPass123',
        ]);
        $passResponse->assertSessionHasNoErrors();
    }

    /**
     * Test 8: CSV Export escapes formula characters to mitigate CSV/Formula Injection
     */
    public function test_csv_export_escapes_formula_characters(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $tenant = BillingInstance::first() ?? BillingInstance::create([
            'tenant_code' => 'BILL-CSV-TEST',
            'name' => 'CSV Test Tenant',
            'api_key' => 'csv-test-key-12345',
            'is_active' => true,
        ]);

        \App\Models\Ticket::create([
            'ticket_number' => 'TKT-TEST-CSV-01',
            'billing_instance_id' => $tenant->id,
            'no_services' => '+62811122233',
            'customer_name' => '=CMD|\' /C calc\'!A0',
            'problem_description' => 'Test CSV formula escaping',
            'status' => 'pending',
            'created_by_name' => $admin->name,
            'created_by_role' => $admin->role,
        ]);

        $response = $this->actingAs($admin)->get(route('tickets.export.csv'));
        $response->assertStatus(200);

        $content = $response->streamedContent();
        // Formula triggers should be prefixed with single quote
        $this->assertStringContainsString("'=CMD", $content);
        $this->assertStringContainsString("'+62811122233", $content);
    }

    /**
     * Test 9: CORS configuration is registered with strict headers and methods
     */
    public function test_cors_configuration_is_active(): void
    {
        $this->assertIsArray(config('cors.allowed_headers'));
        $this->assertContains('X-API-KEY', config('cors.allowed_headers'));
        $this->assertContains('Content-Type', config('cors.allowed_headers'));
        $this->assertContains('GET', config('cors.allowed_methods'));
        $this->assertContains('POST', config('cors.allowed_methods'));
    }
}

