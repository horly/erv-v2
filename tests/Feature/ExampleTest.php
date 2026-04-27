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
        $response->assertSee(__('admin.expired'));
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
    public function test_users_page_exposes_subscription_counts(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-counts@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $subscription = Subscription::create([
            'name' => 'Counted subscription',
            'code' => 'COUNTED_SUBSCRIPTION',
            'type' => 'standard',
            'status' => 'active',
            'company_limit' => 1,
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'counted-admin',
            'email' => 'counted-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        User::create([
            'subscription_id' => $subscription->id,
            'name' => 'counted-user',
            'email' => 'counted-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Counted company',
            'email' => 'counted-company@example.test',
        ]);


        $response = $this->actingAs($superadmin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('data-users="2"', false);
        $response->assertSee('data-companies="1"', false);
    }
    public function test_superadmin_can_create_user_from_users_page(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-create-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $subscription = Subscription::create([
            'name' => 'User subscription',
            'code' => 'USER_SUBSCRIPTION',
            'type' => 'standard',
            'status' => 'active',
            'company_limit' => 1,
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        $response = $this->actingAs($superadmin)->post('/admin/users', [
            'name' => 'new user',
            'email' => 'new-user@example.test',
            'password' => 'StrongPass@123!',
            'password_confirmation' => 'StrongPass@123!',
            'role' => User::ROLE_USER,
            'subscription_id' => $subscription->id,
            'phone_number' => '+243000000000',
            'grade' => 'Manager',
            'address' => 'Kinshasa',
        ]);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'new user',
            'email' => 'new-user@example.test',
            'role' => User::ROLE_USER,
            'subscription_id' => $subscription->id,
            'phone_number' => '+243000000000',
            'grade' => 'Manager',
            'address' => 'Kinshasa',
        ]);
    }
    public function test_admin_creation_rejects_invalid_email_and_missing_password(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-invalid-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $subscription = Subscription::create([
            'name' => 'Admin validation subscription',
            'code' => 'ADMIN_VALIDATION_SUBSCRIPTION',
            'type' => 'standard',
            'status' => 'active',
            'company_limit' => 1,
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        $response = $this->actingAs($superadmin)->post('/admin/admins', [
            'admin_name' => 'bad admin',
            'admin_email' => 'bad@',
            'password' => '',
            'password_confirmation' => '',
            'admin_subscription_id' => $subscription->id,
        ]);

        $response->assertSessionHasErrors(['admin_email', 'password']);
        $this->assertDatabaseMissing('users', [
            'email' => 'bad@',
        ]);
    }
    public function test_users_page_shows_superadmin_then_latest_user(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-order@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $subscription = Subscription::create([
            'name' => 'Order subscription',
            'code' => 'ORDER_SUBSCRIPTION',
            'type' => 'standard',
            'status' => 'active',
            'company_limit' => 1,
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        $oldUser = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'old-user',
            'email' => 'old-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
            'created_at' => now()->subDay(),
        ]);

        $latestUser = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'latest-user',
            'email' => 'latest-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
            'created_at' => now(),
        ]);

        $oldUser->forceFill(['created_at' => now()->subDay()])->save();
        $latestUser->forceFill(['created_at' => now()])->save();

        $response = $this->actingAs($superadmin)->get('/admin/users');

        $response->assertOk();
        $response->assertSeeInOrder(['superadmin-order@example.test', 'latest-user@example.test', 'old-user@example.test']);
    }

    public function test_superadmin_can_update_user_without_changing_password(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-update-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $subscription = Subscription::create([
            'name' => 'Update subscription',
            'code' => 'UPDATE_SUBSCRIPTION',
            'type' => 'standard',
            'status' => 'active',
            'company_limit' => 1,
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        $account = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'editable-user',
            'email' => 'editable-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($superadmin)->put('/admin/users/'.$account->id, [
            'user_id' => $account->id,
            'form_mode' => 'edit',
            'name' => 'edited-user',
            'email' => 'edited-user@example.test',
            'password' => '',
            'password_confirmation' => '',
            'role' => User::ROLE_ADMIN,
            'subscription_id' => $subscription->id,
            'phone_number' => '+243111111111',
            'grade' => 'Lead',
            'address' => 'Gombe',
        ]);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'name' => 'edited-user',
            'email' => 'edited-user@example.test',
            'role' => User::ROLE_ADMIN,
            'phone_number' => '+243111111111',
            'grade' => 'Lead',
            'address' => 'Gombe',
        ]);
    }

    public function test_superadmin_can_delete_user(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-delete-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $subscription = Subscription::create([
            'name' => 'Delete subscription',
            'code' => 'DELETE_SUBSCRIPTION',
            'type' => 'standard',
            'status' => 'active',
            'company_limit' => 1,
            'expires_at' => now()->addYear()->toDateString(),
        ]);

        $account = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'delete-user',
            'email' => 'delete-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($superadmin)->delete('/admin/users/'.$account->id);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('toast_type', 'danger');
        $this->assertDatabaseMissing('users', ['id' => $account->id]);
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
    public function test_authenticated_user_visiting_login_is_redirected_to_main(): void
    {
        $user = User::create([
            'name' => 'logged-user',
            'email' => 'logged-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(route('main'));
    }
    public function test_superadmin_can_open_company_create_page_with_companies_breadcrumb_link(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-company-create@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/companies/create');

        $response->assertOk();
        $response->assertSee('href="'.route('admin.companies').'"', false);
    }
    public function test_country_catalog_contains_phone_codes_and_vat_rates(): void
    {
        $countries = config('countries');

        $this->assertGreaterThanOrEqual(190, count($countries));
        $this->assertSame('Congo (RDC)', $countries['CD']['name']);
        $this->assertSame('+243', $countries['CD']['phone_code']);
        $this->assertSame(16.0, $countries['CD']['vat_rate']);
        $this->assertSame(0.0, $countries['US']['vat_rate']);
    }

    public function test_company_create_country_select_shows_phone_code_and_vat_rate(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-country-select@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/companies/create');

        $response->assertOk();
        $response->assertSee('data-phone-code="+243"', false);
        $response->assertSee('data-vat-rate="16"', false);
        $response->assertSee('Congo (RDC) (+243 - TVA 16,00%)', false);
    }
    public function test_company_create_country_select_uses_english_country_names_when_locale_is_english(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-country-select-en@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this
            ->withSession(['locale' => 'en'])
            ->actingAs($superadmin)
            ->get('/admin/companies/create');

        $response->assertOk();
        $response->assertSee('data-name-en="Congo (DRC)"', false);
        $response->assertSee('Congo (DRC) (+243 - VAT 16,00%)', false);
    }
}