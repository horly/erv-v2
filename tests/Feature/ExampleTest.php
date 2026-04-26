<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_open_main_page(): void
    {
        $subscription = Subscription::create([
            'name' => 'Test subscription',
            'code' => 'TEST',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Prestavice',
            'email' => 'contact@prestavice.com',
        ]);

        $response = $this->actingAs($admin)->get('/main');

        $response->assertOk();
        $response->assertSee('Prestavice');
    }

    public function test_superadmin_can_open_admin_dashboard(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Tableau de bord');
        $response->assertSee('Console superadmin');
    }

    public function test_admin_is_redirected_back_when_opening_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from('/main')
            ->get('/admin/dashboard');

        $response->assertRedirect('/main');
        $response->assertSessionHasErrors('authorization');
    }

    public function test_superadmin_can_open_subscriptions_page(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        Subscription::create([
            'name' => 'EXAD Default',
            'code' => 'EXAD-DEFAULT',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/subscriptions');

        $response->assertOk();
        $response->assertSee('Abonnements');
        $response->assertSee('EXAD Default');
    }

    public function test_admin_is_redirected_back_when_opening_subscriptions_page(): void
    {
        $admin = User::create([
            'name' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from('/main')
            ->get('/admin/subscriptions');

        $response->assertRedirect('/main');
        $response->assertSessionHasErrors('authorization');
    }

    public function test_superadmin_can_create_business_subscription(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($superadmin)->post('/admin/subscriptions', [
            'name' => 'EXAD Business',
            'type' => 'business',
            'expires_at' => '2027-04-25',
        ]);

        $response->assertRedirect(route('admin.subscriptions'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'name' => 'EXAD Business',
            'type' => 'business',
            'company_limit' => null,
            'status' => 'active',
        ]);
    }

    public function test_expired_subscription_is_shown_as_expired(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-expired@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        Subscription::create([
            'name' => 'Expired subscription',
            'code' => 'EXPIRED_SUBSCRIPTION',
            'type' => 'standard',
            'status' => 'active',
            'company_limit' => 1,
            'expires_at' => '2025-01-01',
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/subscriptions');

        $response->assertOk();
        $response->assertSee('Expired subscription');
        $response->assertSee('EXPIRÉ');
    }
    public function test_superadmin_can_open_users_page(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-users@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Utilisateurs');
        $response->assertSee('superadmin-users@example.test');
    }
    public function test_superadmin_login_redirects_to_admin_dashboard(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin@example.test',
            'password' => 'H@mshyef@#154dsgfd',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->post('/login', [
            'email' => $superadmin->email,
            'password' => 'H@mshyef@#154dsgfd',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($superadmin);
    }
}
