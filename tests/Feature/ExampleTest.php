<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySite;
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
            'country' => 'Congo (RDC)',
        ]);

        $response = $this->actingAs($admin)->get('/main');

        $response->assertOk();
        $response->assertSee('Prestavice');
        $response->assertSee(__('main.sites'), false);
        $response->assertSee('id="companyTable"', false);
        $response->assertSee('data-sort-index="2"', false);
        $response->assertSee(route('main.companies.create'), false);
        $response->assertSee(route('main.companies.edit', Company::first()), false);
        $response->assertSee(route('main.companies.sites', Company::first()), false);
        $response->assertSee(__('main.profile'), false);
        $response->assertSee(__('main.users'), false);
    }

    public function test_admin_main_companies_table_is_paginated(): void
    {
        $subscription = Subscription::create([
            'name' => 'Paginated subscription',
            'code' => 'PAGINATED_MAIN',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'paginated admin',
            'email' => 'paginated-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        foreach (range(1, 6) as $index) {
            Company::create([
                'subscription_id' => $subscription->id,
                'created_by' => $admin->id,
                'name' => 'Company '.$index,
                'country' => 'Congo (RDC)',
                'email' => 'company-'.$index.'@example.test',
            ]);
        }

        $response = $this->actingAs($admin)->get('/main');

        $response->assertOk();
        $response->assertSee('subscriptions-pagination', false);
        $response->assertSee('pagination-shell', false);
        $response->assertSee(__('admin.showing'), false);
        $response->assertSee(__('admin.next'), false);
        $response->assertSee('>5</strong>', false);
        $response->assertSee('>6</strong>', false);
    }

    public function test_admin_can_open_company_sites_and_create_site_with_assignments(): void
    {
        $subscription = Subscription::create([
            'name' => 'Sites Business',
            'code' => 'SITES_BUSINESS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'site admin',
            'email' => 'site-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $worker = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'site worker',
            'email' => 'site-worker@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Site Company',
            'country' => 'Congo (RDC)',
            'email' => 'site-company@example.test',
        ]);

        $response = $this->actingAs($admin)->get(route('main.companies.sites', $company));

        $response->assertOk();
        $response->assertSee(__('main.company_sites_title', ['name' => 'Site Company']), false);
        $response->assertSee(__('main.new_site'), false);
        $response->assertSee(__('main.profile'), false);
        $response->assertSee(__('main.users'), false);
        $response->assertSee(__('main.status'), false);
        $response->assertDontSee(__('main.assigned_users'), false);
        $response->assertSee('siteModal', false);
        $response->assertSee('name="modules[]" value="'.CompanySite::MODULE_ACCOUNTING.'" checked', false);

        $storeResponse = $this->actingAs($admin)->post(route('main.companies.sites.store', $company), [
            'name' => 'Kinshasa Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'responsible_id' => $admin->id,
            'modules' => [CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $storeResponse->assertRedirect(route('main.companies.sites', $company));

        $this->assertDatabaseHas('company_sites', [
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Kinshasa Site',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $site = CompanySite::where('name', 'Kinshasa Site')->firstOrFail();

        $this->assertDatabaseHas('company_site_user', [
            'company_site_id' => $site->id,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseMissing('company_site_user', [
            'company_site_id' => $site->id,
            'user_id' => $worker->id,
        ]);
    }

    public function test_site_form_validation_reopens_modal_and_shows_field_errors(): void
    {
        $subscription = Subscription::create([
            'name' => 'Sites Validation',
            'code' => 'SITES_VALIDATION',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'validation admin',
            'email' => 'validation-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Validation Company',
            'country' => 'Congo (RDC)',
            'email' => 'validation-company@example.test',
        ]);

        $response = $this
            ->followingRedirects()
            ->actingAs($admin)
            ->from(route('main.companies.sites', $company))
            ->post(route('main.companies.sites.store', $company), [
                '_site_modal_id' => 'siteModal',
                'name' => '',
                'type' => CompanySite::TYPE_PRODUCTION,
                'responsible_id' => '',
                'currency' => '',
                'status' => CompanySite::STATUS_ACTIVE,
            ]);

        $response->assertOk();
        $response->assertSee('data-reopen-site-modal="siteModal"', false);
        $response->assertSee('Le champ nom est obligatoire.', false);
        $response->assertSee('Le champ responsable est obligatoire.', false);
        $response->assertSee('Le champ modules est obligatoire.', false);
        $response->assertSee('Le champ devise est obligatoire.', false);
        $response->assertSee('class="form-control is-invalid"', false);
        $response->assertDontSee('class="flash-toast', false);

        $this->assertDatabaseMissing('company_sites', [
            'company_id' => $company->id,
        ]);
    }

    public function test_standard_subscription_can_only_create_one_accounting_site(): void
    {
        $subscription = Subscription::create([
            'name' => 'Standard Sites',
            'code' => 'STANDARD_SITES',
            'type' => 'standard',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'standard admin',
            'email' => 'standard-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Standard Company',
            'country' => 'Congo (RDC)',
            'email' => 'standard-company@example.test',
        ]);

        $this->actingAs($admin)
            ->from(route('main.companies.sites', $company))
            ->post(route('main.companies.sites.store', $company), [
                'name' => 'Forbidden HR Site',
                'type' => CompanySite::TYPE_PRODUCTION,
                'responsible_id' => $admin->id,
                'modules' => [CompanySite::MODULE_HUMAN_RESOURCES],
                'currency' => 'CDF',
                'status' => CompanySite::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('main.companies.sites', $company))
            ->assertSessionHasErrors(['modules']);

        CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Existing Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->from(route('main.companies.sites', $company))
            ->post(route('main.companies.sites.store', $company), [
                'name' => 'Second Site',
                'type' => CompanySite::TYPE_PRODUCTION,
                'responsible_id' => $admin->id,
                'modules' => [CompanySite::MODULE_ACCOUNTING],
                'currency' => 'CDF',
                'status' => CompanySite::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors(['site']);
    }

    public function test_user_without_assigned_site_sees_pending_access_page(): void
    {
        $subscription = Subscription::create([
            'name' => 'Pending subscription',
            'code' => 'PENDING_SUBSCRIPTION',
            'status' => 'active',
        ]);

        $user = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'Pending User',
            'email' => 'pending@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get('/main');

        $response->assertOk();
        $response->assertSee(__('main.pending_access_title'), false);
        $response->assertSee(__('main.profile'), false);
        $response->assertDontSee(__('main.users'), false);
        $response->assertSee('pending@example.test', false);
        $response->assertDontSee('id="companyTable"', false);
    }

    public function test_admin_can_create_company_from_main_without_assignment_section(): void
    {
        $subscription = Subscription::create([
            'name' => 'Main Create',
            'code' => 'MAIN_CREATE',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'main admin',
            'email' => 'main-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/main/companies/create');

        $response->assertOk();
        $response->assertSee(__('admin.new_company'), false);
        $response->assertDontSee(__('admin.assignment'), false);

        $storeResponse = $this->actingAs($admin)->post('/main/companies', [
            'name' => 'Main Company',
            'country' => 'Congo (RDC)',
            'email' => 'main-company@example.test',
            'accounts' => [
                ['bank_name' => 'Main Bank', 'account_number' => '001', 'currency' => 'CDF'],
            ],
        ]);

        $storeResponse->assertRedirect('/main');

        $this->assertDatabaseHas('companies', [
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Main Company',
            'email' => 'main-company@example.test',
        ]);

        $this->assertDatabaseHas('company_user', [
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_company_creation_requires_account_number_after_bank_and_currency_after_number(): void
    {
        $subscription = Subscription::create([
            'name' => 'Main Currency Required',
            'code' => 'MAIN_CURRENCY_REQUIRED',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'currency required admin',
            'email' => 'currency-required-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->from('/main/companies/create')
            ->post('/main/companies', [
                'name' => 'Missing Currency Company',
                'country' => 'Congo (RDC)',
                'email' => 'missing-currency-main@example.test',
                'accounts' => [
                    ['bank_name' => 'Main Bank', 'account_number' => '', 'currency' => ''],
                ],
            ])
            ->assertRedirect('/main/companies/create')
            ->assertSessionHasErrors([
                'accounts.0.account_number' => 'Le numéro de compte est obligatoire lorsque la banque est renseignée.',
            ]);

        $this->actingAs($admin)
            ->from('/main/companies/create')
            ->post('/main/companies', [
                'name' => 'Missing Currency Company',
                'country' => 'Congo (RDC)',
                'email' => 'missing-currency-main@example.test',
                'accounts' => [
                    ['bank_name' => 'Main Bank', 'account_number' => '001', 'currency' => ''],
                ],
            ])
            ->assertRedirect('/main/companies/create')
            ->assertSessionHasErrors([
                'accounts.0.currency' => 'La devise est obligatoire lorsque le numéro de compte est renseigné.',
            ]);
    }

    public function test_admin_company_creation_requires_complete_phone_rows(): void
    {
        $subscription = Subscription::create([
            'name' => 'Main Phone Required',
            'code' => 'MAIN_PHONE_REQUIRED',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'phone required admin',
            'email' => 'phone-required-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->from('/main/companies/create')
            ->post('/main/companies', [
                'name' => 'Missing Phone Company',
                'country' => 'Congo (RDC)',
                'email' => 'missing-phone-main@example.test',
                'phones' => [
                    ['label' => 'Fax', 'phone_number' => ''],
                ],
            ])
            ->assertRedirect('/main/companies/create')
            ->assertSessionHasErrors([
                'phones.0.phone_number' => 'Le téléphone est obligatoire lorsque le libellé est renseigné.',
            ]);

        $this->actingAs($admin)
            ->from('/main/companies/create')
            ->post('/main/companies', [
                'name' => 'Missing Phone Label Company',
                'country' => 'Congo (RDC)',
                'email' => 'missing-phone-label-main@example.test',
                'phones' => [
                    ['label' => '', 'phone_number' => '+243810000000'],
                ],
            ])
            ->assertRedirect('/main/companies/create')
            ->assertSessionHasErrors([
                'phones.0.label' => 'Le libellé est obligatoire lorsque le téléphone est renseigné.',
            ]);
    }

    public function test_admin_can_update_and_delete_company_from_main_unless_it_has_sites(): void
    {
        $subscription = Subscription::create([
            'name' => 'Main Update',
            'code' => 'MAIN_UPDATE',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'main update admin',
            'email' => 'main-update-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Editable Main Company',
            'country' => 'Congo (RDC)',
            'email' => 'editable-main@example.test',
        ]);

        $this->actingAs($admin)
            ->put(route('main.companies.update', $company), [
                'name' => 'Updated Main Company',
                'country' => 'Congo (RDC)',
                'email' => 'updated-main@example.test',
            ])
            ->assertRedirect('/main');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Main Company',
            'email' => 'updated-main@example.test',
        ]);

        CompanySite::create([
            'company_id' => $company->id,
            'name' => 'Protected Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
        ]);

        $this->actingAs($admin)
            ->delete(route('main.companies.destroy', $company))
            ->assertRedirect('/main');

        $this->assertDatabaseHas('companies', ['id' => $company->id]);

        $company->sites()->delete();

        $this->actingAs($admin)
            ->delete(route('main.companies.destroy', $company))
            ->assertRedirect('/main');

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
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
        $response->assertSee('id="dashboardChartData"', false);
        $response->assertSee('"periods"', false);
        $response->assertSee('data-dashboard-period="week"', false);
        $response->assertSee('class="active" data-dashboard-period="month"', false);
        $response->assertSee('data-dashboard-period="year"', false);
        $response->assertSee('subscriptionsEvolutionChart', false);
        $response->assertSee('rolesDistributionChart', false);
        $response->assertSee('usersByCompanyChart', false);
        $response->assertSee('globalActivityChart', false);
        $response->assertSee('ApexCharts', false);
        $response->assertSee('resources/js/admin/dashboard.js', false);
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
        $this->assertSame('Côte d\'Ivoire', $countries['CI']['name_fr']);
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
        $response->assertSee('Côte d&#039;Ivoire (+225 - TVA 18,00%)', false);
        $response->assertDontSee('CÃ', false);
    }

    public function test_company_create_account_currency_select_shows_currency_name_and_iso_code(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-currency-select@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/companies/create');

        $response->assertOk();
        $response->assertSee('Franc congolais (CDF - FC)', false);
        $response->assertSee('Dollar américain (USD - $)', false);
        $response->assertSee('Euro (EUR - €)', false);

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Euro (EUR - €)'), strpos($content, 'Dollar américain (USD - $)'));
        $this->assertLessThan(strpos($content, 'Franc congolais (CDF - FC)'), strpos($content, 'Euro (EUR - €)'));
    }

    public function test_company_create_account_currency_select_uses_english_names_when_locale_is_english(): void
    {
        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-currency-select-en@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this
            ->withSession(['locale' => 'en'])
            ->actingAs($superadmin)
            ->get('/admin/companies/create');

        $response->assertOk();
        $response->assertSee('Congolese franc (CDF - FC)', false);
        $response->assertSee('United States dollar (USD - $)', false);
    }

    public function test_superadmin_can_create_company_with_iso_currency_code(): void
    {
        $subscription = Subscription::create([
            'name' => 'Currency subscription',
            'code' => 'CURRENCY',
            'type' => 'business',
            'status' => 'active',
        ]);

        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-company-currency@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'currency admin',
            'email' => 'currency-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($superadmin)->post('/admin/companies', [
            'subscription_id' => $subscription->id,
            'admin_id' => $admin->id,
            'name' => 'Currency Company',
            'country' => 'Congo (RDC)',
            'email' => 'currency-company@example.test',
            'accounts' => [
                [
                    'bank_name' => 'Test Bank',
                    'account_number' => '000123456789',
                    'currency' => 'CDF',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.companies'));
        $this->assertDatabaseHas('company_accounts', [
            'bank_name' => 'Test Bank',
            'account_number' => '000123456789',
            'currency' => 'CDF',
        ]);
    }

    public function test_superadmin_company_creation_requires_account_number_after_bank_and_currency_after_number(): void
    {
        $subscription = Subscription::create([
            'name' => 'Required currency subscription',
            'code' => 'REQCUR',
            'status' => 'active',
        ]);

        $superadmin = User::create([
            'name' => 'superadmin required currency',
            'email' => 'superadmin-required-currency@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'required currency admin',
            'email' => 'required-currency-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($superadmin)
            ->from('/admin/companies/create')
            ->post('/admin/companies', [
                'subscription_id' => $subscription->id,
                'admin_id' => $admin->id,
                'name' => 'Required Currency Company',
                'country' => 'Congo (RDC)',
                'email' => 'required-currency-company@example.test',
                'accounts' => [
                    ['bank_name' => 'Required Bank', 'account_number' => '', 'currency' => ''],
                ],
            ])
            ->assertRedirect('/admin/companies/create')
            ->assertSessionHasErrors([
                'accounts.0.account_number' => 'Le numéro de compte est obligatoire lorsque la banque est renseignée.',
            ]);

        $this->actingAs($superadmin)
            ->from('/admin/companies/create')
            ->post('/admin/companies', [
                'subscription_id' => $subscription->id,
                'admin_id' => $admin->id,
                'name' => 'Required Currency Company',
                'country' => 'Congo (RDC)',
                'email' => 'required-currency-company@example.test',
                'accounts' => [
                    ['bank_name' => 'Required Bank', 'account_number' => '001', 'currency' => ''],
                ],
            ])
            ->assertRedirect('/admin/companies/create')
            ->assertSessionHasErrors([
                'accounts.0.currency' => 'La devise est obligatoire lorsque le numéro de compte est renseigné.',
            ]);
    }

    public function test_superadmin_company_creation_requires_complete_phone_rows(): void
    {
        $subscription = Subscription::create([
            'name' => 'Required phone subscription',
            'code' => 'REQPHONE',
            'status' => 'active',
        ]);

        $superadmin = User::create([
            'name' => 'superadmin required phone',
            'email' => 'superadmin-required-phone@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'required phone admin',
            'email' => 'required-phone-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($superadmin)
            ->from('/admin/companies/create')
            ->post('/admin/companies', [
                'subscription_id' => $subscription->id,
                'admin_id' => $admin->id,
                'name' => 'Required Phone Company',
                'country' => 'Congo (RDC)',
                'email' => 'required-phone-company@example.test',
                'phones' => [
                    ['label' => 'Fax', 'phone_number' => ''],
                ],
            ])
            ->assertRedirect('/admin/companies/create')
            ->assertSessionHasErrors([
                'phones.0.phone_number' => 'Le téléphone est obligatoire lorsque le libellé est renseigné.',
            ]);

        $this->actingAs($superadmin)
            ->from('/admin/companies/create')
            ->post('/admin/companies', [
                'subscription_id' => $subscription->id,
                'admin_id' => $admin->id,
                'name' => 'Required Phone Label Company',
                'country' => 'Congo (RDC)',
                'email' => 'required-phone-label-company@example.test',
                'phones' => [
                    ['label' => '', 'phone_number' => '+243810000000'],
                ],
            ])
            ->assertRedirect('/admin/companies/create')
            ->assertSessionHasErrors([
                'phones.0.label' => 'Le libellé est obligatoire lorsque le téléphone est renseigné.',
            ]);
    }

    public function test_companies_page_uses_placeholder_when_logo_is_missing(): void
    {
        $subscription = Subscription::create([
            'name' => 'Test subscription',
            'code' => 'TEST',
            'status' => 'active',
        ]);

        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-company-logo@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $superadmin->id,
            'name' => 'No Logo Company',
            'country' => 'Congo (RDC)',
            'email' => 'no-logo@example.test',
            'logo' => null,
        ]);

        Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $superadmin->id,
            'name' => 'Broken Logo Company',
            'country' => 'Congo (RDC)',
            'email' => 'broken-logo@example.test',
            'logo' => 'company-logos/missing-logo.png',
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/companies');

        $response->assertOk();
        $response->assertSee('placeholder-logo', false);
        $response->assertSee('bi-building', false);
        $response->assertDontSee('company-logos/missing-logo.png', false);
        $response->assertDontSee('<img src=""', false);
    }

    public function test_companies_page_shows_sites_and_users_count_and_edit_link(): void
    {
        $subscription = Subscription::create([
            'name' => 'Sites subscription',
            'code' => 'SITES',
            'status' => 'active',
        ]);

        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-company-sites@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $superadmin->id,
            'name' => 'Sites Company',
            'country' => 'Congo (RDC)',
            'email' => 'sites-company@example.test',
        ]);

        $companyUser = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'Company member',
            'email' => 'company-member@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $company->users()->attach($companyUser->id, [
            'can_view' => true,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ]);

        CompanySite::create([
            'company_id' => $company->id,
            'name' => 'Kinshasa Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
        ]);

        $response = $this->actingAs($superadmin)->get('/admin/companies');

        $response->assertOk();
        $response->assertSee(route('admin.companies.edit', $company), false);
        $response->assertSee('Sites Company', false);
        $response->assertSee(__('admin.users'), false);
        $response->assertSee('data-bs-target="#companyUsersModal-'.$company->id.'"', false);
        $response->assertSee(__('admin.company_users_title', ['name' => 'Sites Company']), false);
        $response->assertSee('data-datatable', false);
        $response->assertSee('data-datatable-search', false);
        $response->assertSee('data-datatable-table', false);
        $response->assertSee('<button type="button" class="modal-cancel" data-bs-dismiss="modal">'.__('admin.close').'</button>', false);
        $response->assertSee('Company member', false);
        $response->assertSee('company-member@example.test', false);
        $response->assertSee('>1</td>', false);
    }

    public function test_superadmin_can_open_company_edit_form(): void
    {
        $subscription = Subscription::create([
            'name' => 'Edit subscription',
            'code' => 'EDIT',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'edit admin',
            'email' => 'edit-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-company-edit@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Editable Company',
            'country' => 'Congo (RDC)',
            'email' => 'editable-company@example.test',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.companies.edit', $company));

        $response->assertOk();
        $response->assertSee(__('admin.edit_company'), false);
        $response->assertSee('value="Editable Company"', false);
        $response->assertSee('method="POST" action="'.route('admin.companies.update', $company).'"', false);
        $response->assertSee('name="_method" value="PUT"', false);
    }

    public function test_superadmin_can_update_company_from_edit_form(): void
    {
        $subscription = Subscription::create([
            'name' => 'Update subscription',
            'code' => 'UPDATE',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'update admin',
            'email' => 'update-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-company-update@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Old Company',
            'country' => 'Congo (RDC)',
            'email' => 'old-company@example.test',
        ]);

        $response = $this->actingAs($superadmin)->put(route('admin.companies.update', $company), [
            'subscription_id' => $subscription->id,
            'admin_id' => $admin->id,
            'name' => 'Updated Company',
            'country' => 'Congo (RDC)',
            'email' => 'updated-company@example.test',
            'phones' => [
                ['label' => 'Office', 'phone_number' => '+243810000000'],
            ],
            'accounts' => [
                ['bank_name' => 'Bank', 'account_number' => '12345', 'currency' => 'USD'],
            ],
        ]);

        $response->assertRedirect(route('admin.companies'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company',
            'email' => 'updated-company@example.test',
        ]);
        $this->assertDatabaseHas('company_phones', [
            'company_id' => $company->id,
            'phone_number' => '+243810000000',
        ]);
        $this->assertDatabaseHas('company_accounts', [
            'company_id' => $company->id,
            'account_number' => '12345',
            'currency' => 'USD',
        ]);
    }

    public function test_superadmin_can_delete_company_without_sites(): void
    {
        $subscription = Subscription::create([
            'name' => 'Delete subscription',
            'code' => 'DELETE',
            'status' => 'active',
        ]);

        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-company-delete@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $superadmin->id,
            'name' => 'Delete Company',
            'country' => 'Congo (RDC)',
            'email' => 'delete-company@example.test',
        ]);

        $response = $this->actingAs($superadmin)->delete(route('admin.companies.destroy', $company));

        $response->assertRedirect(route('admin.companies'));
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_superadmin_cannot_delete_company_with_sites(): void
    {
        $subscription = Subscription::create([
            'name' => 'Blocked delete subscription',
            'code' => 'BLOCKED_DELETE',
            'status' => 'active',
        ]);

        $superadmin = User::create([
            'name' => 'superadmin',
            'email' => 'superadmin-company-delete-blocked@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $superadmin->id,
            'name' => 'Protected Company',
            'country' => 'Congo (RDC)',
            'email' => 'protected-company@example.test',
        ]);

        CompanySite::create([
            'company_id' => $company->id,
            'name' => 'Protected Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_DOCUMENT_MANAGEMENT],
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($superadmin)->delete(route('admin.companies.destroy', $company));

        $response->assertRedirect(route('admin.companies'));
        $response->assertSessionHasErrors('company');
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
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
