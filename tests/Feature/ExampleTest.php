<?php

namespace Tests\Feature;

use App\Models\AccountingClient;
use App\Models\AccountingCashRegisterSession;
use App\Models\AccountingCreditor;
use App\Models\AccountingCurrency;
use App\Models\AccountingCustomerOrder;
use App\Models\AccountingCustomerOrderLine;
use App\Models\AccountingDebtor;
use App\Models\AccountingDeliveryNote;
use App\Models\AccountingDeliveryNoteLine;
use App\Models\AccountingDeliveryNoteSerial;
use App\Models\AccountingPaymentMethod;
use App\Models\AccountingPartner;
use App\Models\AccountingProformaInvoice;
use App\Models\AccountingProformaInvoiceLine;
use App\Models\AccountingProspect;
use App\Models\AccountingSalesRepresentative;
use App\Models\AccountingSalesInvoice;
use App\Models\AccountingSalesInvoiceLine;
use App\Models\AccountingSalesInvoicePayment;
use App\Models\AccountingRecurringService;
use App\Models\AccountingService;
use App\Models\AccountingServiceCategory;
use App\Models\AccountingServiceSubcategory;
use App\Models\AccountingServiceUnit;
use App\Models\AccountingStockCategory;
use App\Models\AccountingStockItem;
use App\Models\AccountingStockMovement;
use App\Models\AccountingStockSubcategory;
use App\Models\AccountingStockUnit;
use App\Models\AccountingStockWarehouse;
use App\Models\AccountingSupplier;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserLoginHistory;
use App\Support\CurrencyCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
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
        $response->assertSee(route('main.users'), false);
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

    public function test_table_search_filters_all_paginated_records(): void
    {
        $subscription = Subscription::create([
            'name' => 'Search subscription',
            'code' => 'SEARCH_SUBSCRIPTION',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'search admin',
            'email' => 'search-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Search Company',
            'country' => 'Congo (RDC)',
            'email' => 'search-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Search Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $hiddenClient = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Hidden Global Search Client',
            'email' => 'hidden-global@example.test',
        ]);
        AccountingClient::query()
            ->whereKey($hiddenClient->id)
            ->update(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

        foreach (range(1, 5) as $index) {
            $recentClient = AccountingClient::create([
                'company_site_id' => $site->id,
                'created_by' => $admin->id,
                'type' => AccountingClient::TYPE_COMPANY,
                'name' => 'Recent Client '.$index,
                'email' => 'recent-'.$index.'@example.test',
            ]);
            AccountingClient::query()
                ->whereKey($recentClient->id)
                ->update(['created_at' => now()->addSeconds($index), 'updated_at' => now()->addSeconds($index)]);
        }

        $route = route('main.accounting.clients', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertDontSee('Hidden Global Search Client');

        $this->actingAs($admin)->get($route.'?search=Hidden+Global')
            ->assertOk()
            ->assertSee('Hidden Global Search Client')
            ->assertSee('>1</strong>', false)
            ->assertDontSee('Recent Client 5');
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
        $response->assertSee('site-module-card module-accounting', false);
        $response->assertSee('site-module-card module-human-resources', false);
        $response->assertSee('site-module-card module-archiving', false);
        $response->assertSee('site-module-card module-document-management', false);

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

        $defaultWarehouse = AccountingStockWarehouse::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();
        $defaultCategory = AccountingStockCategory::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();
        $defaultUnit = AccountingStockUnit::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();

        $this->assertDatabaseHas('accounting_stock_categories', [
            'company_site_id' => $site->id,
            'warehouse_id' => $defaultWarehouse->id,
            'name' => 'Categorie generale',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('accounting_stock_subcategories', [
            'company_site_id' => $site->id,
            'category_id' => $defaultCategory->id,
            'name' => 'Sous-categorie generale',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('accounting_stock_warehouses', [
            'company_site_id' => $site->id,
            'name' => 'Entrepot principal',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('accounting_stock_units', [
            'company_site_id' => $site->id,
            'name' => 'Pièce',
            'symbol' => 'pc',
            'type' => AccountingStockUnit::TYPE_QUANTITY,
            'status' => 'active',
            'is_default' => true,
        ]);

        $defaultServiceCategory = AccountingServiceCategory::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();
        $defaultServiceSubcategory = AccountingServiceSubcategory::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();
        $defaultServiceUnit = AccountingServiceUnit::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();

        $this->assertDatabaseHas('accounting_service_units', [
            'company_site_id' => $site->id,
            'name' => 'Forfait',
            'is_default' => true,
        ]);

        $serviceUnitsRoute = route('main.accounting.services.index', [$company, $site, 'units']);

        $this->actingAs($admin)->get($serviceUnitsRoute)
            ->assertOk()
            ->assertSee('data-service-mode="edit"', false)
            ->assertDontSee('data-service-mode="view"', false);

        $this->actingAs($admin)
            ->put(route('main.accounting.services.update', [$company, $site, 'units', $defaultServiceUnit]), [
                'name' => 'Forfait modifie',
                'symbol' => 'forfait',
                'status' => 'active',
            ])
            ->assertRedirect($serviceUnitsRoute);

        $this->assertDatabaseHas('accounting_service_units', [
            'id' => $defaultServiceUnit->id,
            'name' => 'Forfait modifie',
            'is_default' => true,
        ]);

        $serviceCategoriesRoute = route('main.accounting.services.index', [$company, $site, 'categories']);

        $this->actingAs($admin)->get($serviceCategoriesRoute)
            ->assertOk()
            ->assertSee('data-service-mode="edit"', false)
            ->assertDontSee('data-service-mode="view"', false);

        $this->actingAs($admin)
            ->put(route('main.accounting.services.update', [$company, $site, 'categories', $defaultServiceCategory]), [
                'name' => 'Services generaux modifies',
                'status' => 'active',
                'description' => 'Categorie par defaut modifiee.',
            ])
            ->assertRedirect($serviceCategoriesRoute);

        $this->assertDatabaseHas('accounting_service_categories', [
            'id' => $defaultServiceCategory->id,
            'name' => 'Services generaux modifies',
            'is_default' => true,
        ]);

        $serviceSubcategoriesRoute = route('main.accounting.services.index', [$company, $site, 'subcategories']);

        $this->actingAs($admin)->get($serviceSubcategoriesRoute)
            ->assertOk()
            ->assertSee('data-service-mode="edit"', false)
            ->assertDontSee('data-service-mode="view"', false);

        $this->actingAs($admin)
            ->put(route('main.accounting.services.update', [$company, $site, 'subcategories', $defaultServiceSubcategory]), [
                'category_id' => $defaultServiceCategory->id,
                'name' => 'Prestations generales modifiees',
                'status' => 'active',
                'description' => 'Sous-categorie par defaut modifiee.',
            ])
            ->assertRedirect($serviceSubcategoriesRoute);

        $this->assertDatabaseHas('accounting_service_subcategories', [
            'id' => $defaultServiceSubcategory->id,
            'category_id' => $defaultServiceCategory->id,
            'name' => 'Prestations generales modifiees',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('accounting_currencies', [
            'company_site_id' => $site->id,
            'code' => 'CDF',
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('accounting_payment_methods', [
            'company_site_id' => $site->id,
            'name' => 'Espèces',
            'type' => AccountingPaymentMethod::TYPE_CASH,
            'currency_code' => 'CDF',
            'is_default' => true,
            'is_system_default' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('main.accounting.stock.destroy', [$company, $site, 'categories', $defaultCategory]))
            ->assertRedirect(route('main.accounting.stock.index', [$company, $site, 'categories']));

        $this->assertDatabaseHas('accounting_stock_categories', [
            'id' => $defaultCategory->id,
            'is_default' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('main.accounting.stock.destroy', [$company, $site, 'units', $defaultUnit]))
            ->assertRedirect(route('main.accounting.stock.index', [$company, $site, 'units']));

        $this->assertDatabaseHas('accounting_stock_units', [
            'id' => $defaultUnit->id,
            'is_default' => true,
        ]);

        AccountingStockCategory::create([
            'company_site_id' => $site->id,
            'warehouse_id' => $defaultWarehouse->id,
            'created_by' => $admin->id,
            'name' => 'Categorie secondaire',
            'status' => AccountingStockCategory::STATUS_ACTIVE,
        ]);

        $categoriesResponse = $this->actingAs($admin)->get(route('main.accounting.stock.index', [$company, $site, 'categories']));
        $categoriesResponse->assertOk();
        $categoriesResponse->assertSee('data-stock-mode="edit"', false);
        $categoriesResponse->assertDontSee('data-stock-mode="view"', false);
        $categoriesResponse->assertSeeInOrder(['Categorie generale', 'Categorie secondaire']);

        $this->actingAs($admin)
            ->put(route('main.accounting.stock.update', [$company, $site, 'categories', $defaultCategory]), [
                'warehouse_id' => $defaultWarehouse->id,
                'name' => 'Categorie modifiee',
                'status' => AccountingStockCategory::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('main.accounting.stock.index', [$company, $site, 'categories']));

        $this->assertDatabaseHas('accounting_stock_categories', [
            'id' => $defaultCategory->id,
            'name' => 'Categorie modifiee',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('company_site_user', [
            'company_site_id' => $site->id,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseMissing('company_site_user', [
            'company_site_id' => $site->id,
            'user_id' => $worker->id,
        ]);

        $listResponse = $this->actingAs($admin)->get(route('main.companies.sites', $company));

        $listResponse->assertSee(route('main.companies.sites.show', [$company, $site]), false);
        $listResponse->assertSee('class="site-name-link"', false);
    }

    public function test_admin_can_open_company_site_detail_and_see_available_modules(): void
    {
        $subscription = Subscription::create([
            'name' => 'Site Detail Business',
            'code' => 'SITE_DETAIL_BUSINESS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'detail admin',
            'email' => 'detail-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Detail Company',
            'country' => 'Congo (RDC)',
            'email' => 'detail-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Archive HQ',
            'type' => CompanySite::TYPE_ARCHIVE,
            'modules' => [
                CompanySite::MODULE_ARCHIVING,
                CompanySite::MODULE_DOCUMENT_MANAGEMENT,
                CompanySite::MODULE_HUMAN_RESOURCES,
            ],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('main.companies.sites.show', [$company, $site]));

        $response->assertOk();
        $response->assertSee(__('main.back_to_company_sites', ['name' => $company->name]), false);
        $response->assertSee('Archive HQ');
        $response->assertSee(__('main.site_details'), false);
        $response->assertSee(__('main.site_modules_intro'), false);
        $response->assertSee(__('main.module_archiving'), false);
        $response->assertSee(__('main.module_document_management'), false);
        $response->assertSee(__('main.module_human_resources'), false);
        $response->assertSee('site-module-link module-archiving', false);
        $response->assertSee('site-module-link module-document-management', false);
        $response->assertSee('site-module-link module-human-resources', false);
        $response->assertSee(route('main.companies.sites.modules.show', [$company, $site, CompanySite::MODULE_ARCHIVING]), false);
        $response->assertSee(route('main.companies.sites.modules.show', [$company, $site, CompanySite::MODULE_DOCUMENT_MANAGEMENT]), false);
        $response->assertSee(route('main.companies.sites.modules.show', [$company, $site, CompanySite::MODULE_HUMAN_RESOURCES]), false);
        $response->assertSee(__('main.plan'), false);
        $response->assertSee('BUSINESS');
    }

    public function test_accounting_module_opens_dedicated_dashboard(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Module',
            'code' => 'ACCOUNTING_MODULE',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'accounting admin',
            'email' => 'accounting-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Accounting Company',
            'country' => 'Congo (RDC)',
            'email' => 'accounting-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Accounting Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_INDIVIDUAL,
            'name' => 'Jean Client',
        ]);

        $businessClient = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Client SARL',
        ]);
        $businessClient->contacts()->create([
            'full_name' => 'Marie Contact',
        ]);

        $supplier = AccountingSupplier::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingSupplier::TYPE_COMPANY,
            'name' => 'Supplier SARL',
            'status' => AccountingSupplier::STATUS_ACTIVE,
        ]);
        $supplier->contacts()->create([
            'full_name' => 'Supplier Contact',
        ]);

        $prospect = AccountingProspect::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingProspect::TYPE_COMPANY,
            'name' => 'Prospect SARL',
            'source' => AccountingProspect::SOURCE_REFERRAL,
            'status' => AccountingProspect::STATUS_NEW,
            'interest_level' => AccountingProspect::INTEREST_HOT,
        ]);
        $prospect->contacts()->create([
            'full_name' => 'Prospect Contact',
        ]);

        AccountingCreditor::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingCreditor::TYPE_SUPPLIER,
            'name' => 'Fournisseur dette',
            'currency' => 'CDF',
            'initial_amount' => 5000000,
            'paid_amount' => 1250000,
            'priority' => AccountingCreditor::PRIORITY_HIGH,
            'status' => AccountingCreditor::STATUS_ACTIVE,
        ]);

        AccountingDebtor::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingDebtor::TYPE_CLIENT,
            'name' => 'Client creance',
            'currency' => 'CDF',
            'initial_amount' => 8500000,
            'received_amount' => 1500000,
            'status' => AccountingDebtor::STATUS_ACTIVE,
        ]);

        AccountingPartner::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingPartner::TYPE_DISTRIBUTOR,
            'name' => 'Partenaire distribution',
            'status' => AccountingPartner::STATUS_ACTIVE,
        ]);

        AccountingSalesRepresentative::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingSalesRepresentative::TYPE_INTERNAL,
            'name' => 'Commercial Kin',
            'currency' => 'CDF',
            'status' => AccountingSalesRepresentative::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('main.companies.sites.modules.show', [$company, $site, CompanySite::MODULE_ACCOUNTING]));

        $response->assertOk();
        $response->assertSee(__('main.accounting_dashboard'), false);
        $response->assertSee(__('admin.week'), false);
        $response->assertSee(__('admin.month'), false);
        $response->assertSee(__('admin.year'), false);
        $response->assertSee(__('main.revenue_expenses_evolution'), false);
        $response->assertSee(__('main.contacts_distribution'), false);
        $response->assertSee(__('main.stock_services_activity'), false);
        $response->assertSee(__('main.documents_flow'), false);
        $response->assertSee(__('main.schedule'), false);
        $response->assertSee(__('main.clients_owe_me'), false);
        $response->assertSee(__('main.i_owe_suppliers'), false);
        $response->assertSee(__('main.customer_receivable'), false);
        $response->assertSee(__('main.supplier_debt'), false);
        $response->assertSee('7 000 000 CDF', false);
        $response->assertSee('3 750 000 CDF', false);
        $response->assertSee(__('main.cashflow_overview'), false);
        $response->assertSee('module-kpi-grid', false);
        $response->assertSee('accountingDashboardData', false);
        $response->assertSee('accountingRevenueChart', false);
        $response->assertSee('accountingContactsChart', false);
        $response->assertSee('accountingStockServicesChart', false);
        $response->assertSee('accountingDocumentsChart', false);
        $response->assertSee('accounting-documents-panel', false);
        $response->assertSee('accountingCashflowChart', false);
        $response->assertSee('accounting-schedule-summary', false);
        $response->assertSee('accounting-schedule-list', false);
        $response->assertSee('resources/js/main/accounting-dashboard.js', false);
        $response->assertSee(__('main.customers'), false);
        $response->assertSee(__('main.client_contacts'), false);
        $response->assertSee(__('main.supplier_contacts'), false);
        $response->assertSee('"series":[1,2,1,1,1,1,1]', false);
        $response->assertSee('dashboard-sidebar accounting-sidebar', false);
        $response->assertSee('id="sidebarToggle"', false);
        $response->assertSee('data-accounting-submenu', false);
        $response->assertSee('aria-expanded="false" data-accounting-submenu', false);
        $response->assertSee(__('main.contacts'), false);
        $response->assertSee(__('main.suppliers'), false);
        $response->assertSee(__('main.prospects'), false);
        $response->assertSee(__('main.creditors'), false);
        $response->assertSee(__('main.debtors'), false);
        $response->assertSee(__('main.partners'), false);
        $response->assertSee(__('main.stock'), false);
        $response->assertSee(__('main.services'), false);
        $response->assertSee(__('main.currencies'), false);
        $response->assertSee(__('main.payment_methods'), false);
        $response->assertSee(__('main.billing'), false);
        $response->assertSee(__('main.sales_invoices'), false);
        $response->assertSee(__('main.proforma_invoices'), false);
        $response->assertSee(__('main.delivery_notes'), false);
        $response->assertSee(__('main.cash_register'), false);
        $response->assertSee(__('main.expenses_group'), false);
        $response->assertSee(__('main.debts'), false);
        $response->assertSee(__('main.receivables'), false);
        $response->assertSee(__('main.bank_reconciliation'), false);
        $response->assertSee(__('main.tasks'), false);
        $response->assertSee(__('main.reports'), false);
        $response->assertSee(__('main.module_settings'), false);
    }

    public function test_accounting_clients_page_manages_individual_and_company_clients(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Clients',
            'code' => 'ACCOUNTING_CLIENTS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'clients admin',
            'email' => 'clients-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Clients Company',
            'country' => 'Congo (RDC)',
            'email' => 'clients-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Clients Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.clients', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.customers'), false)
            ->assertSee(__('main.new_client'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-clients.js', false);

        $this->actingAs($admin)->post(route('main.accounting.clients.store', [$company, $site]), [
            'type' => AccountingClient::TYPE_INDIVIDUAL,
            'name' => 'Jean Client',
            'profession' => 'Consultant',
            'phone' => '+243810000000',
            'email' => 'jean@example.test',
            'address' => 'Kinshasa',
            'bank_name' => 'Equity BCDC',
            'account_number' => 'CD001',
            'currency' => 'CDF',
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_clients', [
            'company_site_id' => $site->id,
            'reference' => 'CLT-000001',
            'type' => AccountingClient::TYPE_INDIVIDUAL,
            'name' => 'Jean Client',
            'profession' => 'Consultant',
            'bank_name' => 'Equity BCDC',
            'account_number' => 'CD001',
            'currency' => 'CDF',
        ]);

        $this->actingAs($admin)->post(route('main.accounting.clients.store', [$company, $site]), [
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Client SARL',
            'rccm' => 'CD/KIN/RCCM/001',
            'id_nat' => 'IDNAT001',
            'nif' => 'NIF001',
            'bank_name' => 'Rawbank',
            'account_number' => '000123456789',
            'currency' => 'USD',
            'website' => 'https://client.example.test',
            'address' => 'Gombe, Kinshasa',
            'contacts' => [
                [
                    'full_name' => 'Marie Contact',
                    'position' => 'Directrice',
                    'department' => 'Finance',
                    'email' => 'marie@example.test',
                    'phone' => '+243820000000',
                ],
            ],
        ])->assertRedirect($route);

        $client = AccountingClient::query()->where('name', 'Client SARL')->firstOrFail();

        AccountingProformaInvoice::create([
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'reference' => 'PRO-000001',
            'title' => 'Offre client',
            'issue_date' => '2026-05-08',
            'expiration_date' => '2026-05-15',
            'currency' => 'USD',
            'status' => AccountingProformaInvoice::STATUS_SENT,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
            'subtotal' => 1200,
            'discount_total' => 0,
            'total_ht' => 1200,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_ttc' => 1200,
        ]);

        $customerOrder = AccountingCustomerOrder::create([
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'reference' => 'CMD-000001',
            'title' => 'Commande client',
            'order_date' => '2026-05-09',
            'expected_delivery_date' => '2026-05-12',
            'currency' => 'USD',
            'status' => AccountingCustomerOrder::STATUS_CONFIRMED,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
            'subtotal' => 1200,
            'cost_total' => 900,
            'margin_total' => 300,
            'margin_rate' => 25,
            'discount_total' => 0,
            'total_ht' => 1200,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_ttc' => 1200,
        ]);

        $deliveryNote = AccountingDeliveryNote::create([
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'customer_order_id' => $customerOrder->id,
            'created_by' => $admin->id,
            'reference' => 'BL-000001',
            'title' => 'Livraison client',
            'delivery_date' => '2026-05-10',
            'status' => AccountingDeliveryNote::STATUS_READY,
        ]);

        AccountingSalesInvoice::create([
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'customer_order_id' => $customerOrder->id,
            'delivery_note_id' => $deliveryNote->id,
            'created_by' => $admin->id,
            'reference' => 'FAC-000001',
            'title' => 'Facture client',
            'invoice_date' => '2026-05-11',
            'due_date' => '2026-05-18',
            'currency' => 'USD',
            'status' => AccountingSalesInvoice::STATUS_ISSUED,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
            'subtotal' => 1200,
            'discount_total' => 0,
            'total_ht' => 1200,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_ttc' => 1200,
            'paid_total' => 0,
            'balance_due' => 1200,
        ]);

        $this->assertDatabaseHas('accounting_client_contacts', [
            'accounting_client_id' => $client->id,
            'full_name' => 'Marie Contact',
            'position' => 'Directrice',
        ]);
        $this->assertDatabaseHas('accounting_clients', [
            'name' => 'Client SARL',
            'bank_name' => 'Rawbank',
            'account_number' => '000123456789',
            'currency' => 'USD',
            'address' => 'Gombe, Kinshasa',
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.reference'), false)
            ->assertSee('CLT-000001', false)
            ->assertSee('CLT-000002', false)
            ->assertDontSee('data-sort-index="4">', false)
            ->assertDontSee('data-sort-index="5">', false)
            ->assertSee('Jean Client')
            ->assertSee('Client SARL')
            ->assertSee('Marie Contact', false)
            ->assertSee(__('main.client_company'), false)
            ->assertSee(__('main.view_client_documents'), false)
            ->assertSee(__('main.client_documents_title', ['name' => 'Client SARL']), false)
            ->assertSee('PRO-000001', false)
            ->assertSee('CMD-000001', false)
            ->assertSee('BL-000001', false)
            ->assertSee('FAC-000001', false)
            ->assertSee('table-button table-button-print', false);
    }

    public function test_accounting_suppliers_page_manages_individual_and_company_suppliers(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Suppliers',
            'code' => 'ACCOUNTING_SUPPLIERS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'suppliers admin',
            'email' => 'suppliers-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Suppliers Company',
            'country' => 'Congo (RDC)',
            'email' => 'suppliers-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Suppliers Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.suppliers', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.suppliers'), false)
            ->assertSee(__('main.new_supplier'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-suppliers.js', false);

        $this->actingAs($admin)->post(route('main.accounting.suppliers.store', [$company, $site]), [
            'type' => AccountingSupplier::TYPE_INDIVIDUAL,
            'name' => 'Jean Fournisseur',
            'profession' => 'Prestataire',
            'phone' => '+243810000001',
            'email' => 'fournisseur@example.test',
            'address' => 'Kinshasa',
            'bank_name' => 'Equity BCDC',
            'account_number' => 'FRS001',
            'currency' => 'CDF',
            'status' => AccountingSupplier::STATUS_ACTIVE,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_suppliers', [
            'company_site_id' => $site->id,
            'reference' => 'FRS-000001',
            'type' => AccountingSupplier::TYPE_INDIVIDUAL,
            'name' => 'Jean Fournisseur',
            'bank_name' => 'Equity BCDC',
            'account_number' => 'FRS001',
            'currency' => 'CDF',
        ]);

        $this->actingAs($admin)->post(route('main.accounting.suppliers.store', [$company, $site]), [
            'type' => AccountingSupplier::TYPE_COMPANY,
            'name' => 'Supplier SARL',
            'rccm' => 'CD/KIN/RCCM/FRS001',
            'id_nat' => 'FRSID001',
            'nif' => 'FRSNIF001',
            'bank_name' => 'Rawbank',
            'account_number' => '000987654321',
            'currency' => 'USD',
            'website' => 'https://supplier.example.test',
            'address' => 'Limete, Kinshasa',
            'status' => AccountingSupplier::STATUS_INACTIVE,
            'contacts' => [
                [
                    'full_name' => 'Paul Contact',
                    'position' => 'Responsable achats',
                    'department' => 'Commercial',
                    'email' => 'paul@example.test',
                    'phone' => '+243820000001',
                ],
            ],
        ])->assertRedirect($route);

        $supplier = AccountingSupplier::query()->where('name', 'Supplier SARL')->firstOrFail();

        $this->assertDatabaseHas('accounting_supplier_contacts', [
            'accounting_supplier_id' => $supplier->id,
            'full_name' => 'Paul Contact',
            'position' => 'Responsable achats',
        ]);
        $this->assertDatabaseHas('accounting_suppliers', [
            'name' => 'Supplier SARL',
            'address' => 'Limete, Kinshasa',
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.reference'), false)
            ->assertSee('FRS-000001', false)
            ->assertSee('FRS-000002', false)
            ->assertSee('Jean Fournisseur')
            ->assertSee('Supplier SARL')
            ->assertSee('Paul Contact', false)
            ->assertSee(__('main.supplier_company'), false);
    }

    public function test_accounting_prospects_page_manages_and_converts_prospects(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Prospects',
            'code' => 'ACCOUNTING_PROSPECTS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'prospects admin',
            'email' => 'prospects-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Prospects Company',
            'country' => 'Congo (RDC)',
            'email' => 'prospects-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Prospects Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.prospects', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.prospects'), false)
            ->assertSee(__('main.new_prospect'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-prospects.js', false);

        $this->actingAs($admin)->post(route('main.accounting.prospects.store', [$company, $site]), [
            'type' => AccountingProspect::TYPE_INDIVIDUAL,
            'name' => 'Jean Prospect',
            'profession' => 'Entrepreneur',
            'phone' => '+243810000002',
            'email' => 'jean-prospect@example.test',
            'address' => 'Kinshasa',
            'source' => AccountingProspect::SOURCE_REFERRAL,
            'status' => AccountingProspect::STATUS_CONTACTED,
            'interest_level' => AccountingProspect::INTEREST_HOT,
            'notes' => 'Relance prévue cette semaine.',
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_prospects', [
            'company_site_id' => $site->id,
            'reference' => 'PRS-000001',
            'type' => AccountingProspect::TYPE_INDIVIDUAL,
            'name' => 'Jean Prospect',
            'source' => AccountingProspect::SOURCE_REFERRAL,
            'status' => AccountingProspect::STATUS_CONTACTED,
            'interest_level' => AccountingProspect::INTEREST_HOT,
        ]);

        $this->actingAs($admin)->post(route('main.accounting.prospects.store', [$company, $site]), [
            'type' => AccountingProspect::TYPE_COMPANY,
            'name' => 'Prospect SARL',
            'rccm' => 'CD/KIN/RCCM/PRS001',
            'id_nat' => 'PRSID001',
            'nif' => 'PRSNIF001',
            'website' => 'https://prospect.example.test',
            'address' => 'Avenue du Commerce, Kinshasa',
            'source' => AccountingProspect::SOURCE_CAMPAIGN,
            'status' => AccountingProspect::STATUS_QUALIFIED,
            'interest_level' => AccountingProspect::INTEREST_WARM,
            'contacts' => [
                [
                    'full_name' => 'Claire Prospect',
                    'position' => 'Directrice générale',
                    'department' => 'Direction',
                    'email' => 'claire@example.test',
                    'phone' => '+243820000002',
                ],
            ],
        ])->assertRedirect($route);

        $prospect = AccountingProspect::query()->where('name', 'Prospect SARL')->firstOrFail();
        $this->assertSame('Avenue du Commerce, Kinshasa', $prospect->address);

        $this->assertDatabaseHas('accounting_prospect_contacts', [
            'accounting_prospect_id' => $prospect->id,
            'full_name' => 'Claire Prospect',
            'position' => 'Directrice générale',
        ]);

        $this->actingAs($admin)
            ->post(route('main.accounting.prospects.convert', [$company, $site, $prospect]))
            ->assertRedirect(route('main.accounting.clients', [$company, $site]));

        $prospect->refresh();
        $this->assertNotNull($prospect->converted_client_id);
        $this->assertSame(AccountingProspect::STATUS_WON, $prospect->status);
        $this->assertDatabaseHas('accounting_clients', [
            'company_site_id' => $site->id,
            'name' => 'Prospect SARL',
            'type' => AccountingProspect::TYPE_COMPANY,
            'address' => 'Avenue du Commerce, Kinshasa',
        ]);
        $this->assertDatabaseHas('accounting_client_contacts', [
            'full_name' => 'Claire Prospect',
            'position' => 'Directrice générale',
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('PRS-000001', false)
            ->assertSee('PRS-000002', false)
            ->assertSee(__('main.prospect_company'), false)
            ->assertSee(__('main.prospect_status_won'), false)
            ->assertSee(__('main.prospect_interest_hot'), false);
    }

    public function test_accounting_creditors_page_manages_creditors_and_debts(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Creditors',
            'code' => 'ACCOUNTING_CREDITORS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'creditors admin',
            'email' => 'creditors-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Creditors Company',
            'country' => 'Congo (RDC)',
            'email' => 'creditors-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Creditors Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.creditors', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.creditors'), false)
            ->assertSee(__('main.new_creditor'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-creditors.js', false);

        $this->actingAs($admin)->post(route('main.accounting.creditors.store', [$company, $site]), [
            'type' => AccountingCreditor::TYPE_BANK,
            'name' => 'Rawbank',
            'phone' => '+243810000003',
            'email' => 'rawbank@example.test',
            'address' => 'Kinshasa',
            'currency' => 'CDF',
            'initial_amount' => 5000000,
            'paid_amount' => 1250000,
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'description' => 'Crédit court terme',
            'priority' => AccountingCreditor::PRIORITY_HIGH,
            'status' => AccountingCreditor::STATUS_ACTIVE,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_creditors', [
            'company_site_id' => $site->id,
            'reference' => 'CRE-000001',
            'type' => AccountingCreditor::TYPE_BANK,
            'name' => 'Rawbank',
            'currency' => 'CDF',
            'initial_amount' => 5000000,
            'paid_amount' => 1250000,
            'priority' => AccountingCreditor::PRIORITY_HIGH,
            'status' => AccountingCreditor::STATUS_ACTIVE,
        ]);

        $creditor = AccountingCreditor::query()->where('name', 'Rawbank')->firstOrFail();
        $this->assertSame(3750000.0, $creditor->balanceDue());

        $this->actingAs($admin)->put(route('main.accounting.creditors.update', [$company, $site, $creditor]), [
            'type' => AccountingCreditor::TYPE_BANK,
            'name' => 'Rawbank RDC',
            'currency' => 'USD',
            'initial_amount' => 3000,
            'paid_amount' => 3000,
            'due_date' => now()->addDays(20)->format('Y-m-d'),
            'description' => 'Dette soldee',
            'priority' => AccountingCreditor::PRIORITY_NORMAL,
            'status' => AccountingCreditor::STATUS_SETTLED,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_creditors', [
            'id' => $creditor->id,
            'name' => 'Rawbank RDC',
            'currency' => 'USD',
            'status' => AccountingCreditor::STATUS_SETTLED,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('CRE-000001', false)
            ->assertSee('Rawbank RDC')
            ->assertSee(__('main.creditor_type_bank'), false)
            ->assertSee(__('main.settled'), false);
    }

    public function test_accounting_debtors_page_manages_debtors_and_receivables(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Debtors',
            'code' => 'ACCOUNTING_DEBTORS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'debtors admin',
            'email' => 'debtors-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Debtors Company',
            'country' => 'Congo (RDC)',
            'email' => 'debtors-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Debtors Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.debtors', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.debtors'), false)
            ->assertSee(__('main.new_debtor'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-debtors.js', false);

        $this->actingAs($admin)->post(route('main.accounting.debtors.store', [$company, $site]), [
            'type' => AccountingDebtor::TYPE_CLIENT,
            'name' => 'Client Debiteur',
            'phone' => '+243810000004',
            'email' => 'debiteur@example.test',
            'address' => 'Kinshasa',
            'currency' => 'CDF',
            'initial_amount' => 7200000,
            'received_amount' => 1200000,
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'description' => 'Facture a encaisser',
            'status' => AccountingDebtor::STATUS_ACTIVE,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_debtors', [
            'company_site_id' => $site->id,
            'reference' => 'DEB-000001',
            'type' => AccountingDebtor::TYPE_CLIENT,
            'name' => 'Client Debiteur',
            'currency' => 'CDF',
            'initial_amount' => 7200000,
            'received_amount' => 1200000,
            'status' => AccountingDebtor::STATUS_ACTIVE,
        ]);

        $debtor = AccountingDebtor::query()->where('name', 'Client Debiteur')->firstOrFail();
        $this->assertSame(6000000.0, $debtor->balanceReceivable());

        $this->actingAs($admin)->put(route('main.accounting.debtors.update', [$company, $site, $debtor]), [
            'type' => AccountingDebtor::TYPE_CLIENT,
            'name' => 'Client Solde',
            'currency' => 'USD',
            'initial_amount' => 2500,
            'received_amount' => 2500,
            'due_date' => now()->addDays(18)->format('Y-m-d'),
            'description' => 'Creance soldee',
            'status' => AccountingDebtor::STATUS_SETTLED,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_debtors', [
            'id' => $debtor->id,
            'name' => 'Client Solde',
            'currency' => 'USD',
            'status' => AccountingDebtor::STATUS_SETTLED,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('DEB-000001', false)
            ->assertSee('Client Solde')
            ->assertSee(__('main.debtor_type_client'), false)
            ->assertSee(__('main.settled'), false);
    }

    public function test_accounting_partners_page_manages_partners(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Partners',
            'code' => 'ACCOUNTING_PARTNERS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'partners admin',
            'email' => 'partners-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Partners Company',
            'country' => 'Congo (RDC)',
            'email' => 'partners-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Partners Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.partners', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.partners'), false)
            ->assertSee(__('main.new_partner'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-partners.js', false);

        $this->actingAs($admin)->post(route('main.accounting.partners.store', [$company, $site]), [
            'type' => AccountingPartner::TYPE_DISTRIBUTOR,
            'name' => 'Distributeur Kin',
            'contact_name' => 'Jean Partenaire',
            'contact_position' => 'Directeur commercial',
            'phone' => '+243810000005',
            'email' => 'partner@example.test',
            'address' => 'Kinshasa',
            'website' => 'https://partner.example.test',
            'activity_domain' => 'Distribution',
            'partnership_started_at' => now()->format('Y-m-d'),
            'status' => AccountingPartner::STATUS_ACTIVE,
            'notes' => 'Contrat cadre',
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_partners', [
            'company_site_id' => $site->id,
            'reference' => 'PAR-000001',
            'type' => AccountingPartner::TYPE_DISTRIBUTOR,
            'name' => 'Distributeur Kin',
            'contact_name' => 'Jean Partenaire',
            'status' => AccountingPartner::STATUS_ACTIVE,
        ]);

        $partner = AccountingPartner::query()->where('name', 'Distributeur Kin')->firstOrFail();

        $this->actingAs($admin)->put(route('main.accounting.partners.update', [$company, $site, $partner]), [
            'type' => AccountingPartner::TYPE_CONSULTING_FIRM,
            'name' => 'Cabinet Conseil Kin',
            'contact_name' => 'Marie Conseil',
            'contact_position' => 'Associee gerante',
            'activity_domain' => 'Conseil',
            'status' => AccountingPartner::STATUS_SUSPENDED,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_partners', [
            'id' => $partner->id,
            'name' => 'Cabinet Conseil Kin',
            'type' => AccountingPartner::TYPE_CONSULTING_FIRM,
            'activity_domain' => 'Conseil',
            'status' => AccountingPartner::STATUS_SUSPENDED,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('PAR-000001', false)
            ->assertSee('Cabinet Conseil Kin')
            ->assertSee(__('main.partner_type_consulting_firm'), false)
            ->assertSee(__('main.partner_status_suspended'), false);
    }

    public function test_accounting_sales_representatives_page_manages_representatives(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Commercials',
            'code' => 'ACCOUNTING_COMMERCIALS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'commercials admin',
            'email' => 'commercials-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Commercials Company',
            'country' => 'Congo (RDC)',
            'email' => 'commercials-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Commercials Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.sales-representatives', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.sales_representatives'), false)
            ->assertSee(__('main.new_sales_representative'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-sales-representatives.js', false);

        $this->actingAs($admin)->post(route('main.accounting.sales-representatives.store', [$company, $site]), [
            'type' => AccountingSalesRepresentative::TYPE_INTERNAL,
            'name' => 'Jean Commercial',
            'phone' => '+243810000006',
            'email' => 'commercial@example.test',
            'address' => 'Kinshasa',
            'sales_area' => 'Kinshasa Ouest',
            'currency' => 'CDF',
            'monthly_target' => 5000000,
            'annual_target' => 60000000,
            'commission_rate' => 5.5,
            'status' => AccountingSalesRepresentative::STATUS_ACTIVE,
            'notes' => 'Portefeuille prioritaire',
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_sales_representatives', [
            'company_site_id' => $site->id,
            'reference' => 'COM-000001',
            'type' => AccountingSalesRepresentative::TYPE_INTERNAL,
            'name' => 'Jean Commercial',
            'sales_area' => 'Kinshasa Ouest',
            'currency' => 'CDF',
            'monthly_target' => 5000000,
            'commission_rate' => 5.5,
            'status' => AccountingSalesRepresentative::STATUS_ACTIVE,
        ]);

        $representative = AccountingSalesRepresentative::query()->where('name', 'Jean Commercial')->firstOrFail();

        $this->actingAs($admin)->put(route('main.accounting.sales-representatives.update', [$company, $site, $representative]), [
            'type' => AccountingSalesRepresentative::TYPE_RESELLER,
            'name' => 'Agence Commerciale',
            'sales_area' => 'Grand Kinshasa',
            'currency' => 'USD',
            'monthly_target' => 3000,
            'annual_target' => 36000,
            'commission_rate' => 7,
            'status' => AccountingSalesRepresentative::STATUS_SUSPENDED,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_sales_representatives', [
            'id' => $representative->id,
            'name' => 'Agence Commerciale',
            'type' => AccountingSalesRepresentative::TYPE_RESELLER,
            'sales_area' => 'Grand Kinshasa',
            'currency' => 'USD',
            'status' => AccountingSalesRepresentative::STATUS_SUSPENDED,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('COM-000001', false)
            ->assertSee('Agence Commerciale')
            ->assertSee(__('main.sales_representative_type_reseller'), false)
            ->assertSee(__('main.suspended'), false);
    }

    public function test_accounting_stock_pages_manage_items_and_movements(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Stock',
            'code' => 'ACCOUNTING_STOCK',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'stock admin',
            'email' => 'stock-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Stock Company',
            'country' => 'Congo (RDC)',
            'email' => 'stock-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Stock Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        AccountingCurrency::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'code' => 'USD',
            'name' => 'Dollar americain',
            'symbol' => '$',
            'exchange_rate' => 2800,
            'is_base' => false,
            'is_default' => false,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $categoriesRoute = route('main.accounting.stock.index', [$company, $site, 'categories']);

        $this->actingAs($admin)->get($categoriesRoute)
            ->assertOk()
            ->assertSee(__('main.categories'), false)
            ->assertSee(__('main.new_stock_category'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-stock-resource.js', false);

        $this->actingAs($admin)->post(route('main.accounting.stock.store', [$company, $site, 'warehouses']), [
            'name' => 'Depot principal',
            'code' => 'DEP-01',
            'manager_name' => 'Responsable stock',
            'status' => 'active',
        ])->assertRedirect(route('main.accounting.stock.index', [$company, $site, 'warehouses']));

        $warehouse = AccountingStockWarehouse::query()->where('code', 'DEP-01')->firstOrFail();

        $this->assertDatabaseHas('accounting_stock_warehouses', [
            'company_site_id' => $site->id,
            'reference' => 'DEP-000001',
            'name' => 'Depot principal',
        ]);

        $this->actingAs($admin)->post(route('main.accounting.stock.store', [$company, $site, 'categories']), [
            'warehouse_id' => $warehouse->id,
            'name' => 'Matieres premieres',
            'description' => 'Articles de fabrication',
            'status' => 'active',
        ])->assertRedirect($categoriesRoute);

        $category = AccountingStockCategory::query()->where('name', 'Matieres premieres')->firstOrFail();

        $this->assertDatabaseHas('accounting_stock_categories', [
            'company_site_id' => $site->id,
            'warehouse_id' => $warehouse->id,
            'reference' => 'CAT-000001',
            'name' => 'Matieres premieres',
        ]);

        $this->actingAs($admin)->get($categoriesRoute)
            ->assertOk()
            ->assertSee(base64_encode(json_encode([
                'warehouse_id' => $warehouse->id,
                'name' => 'Matieres premieres',
                'status' => 'active',
                'description' => 'Articles de fabrication',
            ])), false);

        $this->actingAs($admin)->post(route('main.accounting.stock.store', [$company, $site, 'subcategories']), [
            'category_id' => $category->id,
            'name' => 'Ciments',
            'description' => 'Articles ciment',
            'status' => 'active',
        ])->assertRedirect(route('main.accounting.stock.index', [$company, $site, 'subcategories']));

        $subcategory = AccountingStockSubcategory::query()->where('name', 'Ciments')->firstOrFail();

        $this->actingAs($admin)->post(route('main.accounting.stock.store', [$company, $site, 'units']), [
            'name' => 'Piece',
            'symbol' => 'pcs',
            'type' => AccountingStockUnit::TYPE_QUANTITY,
            'status' => 'active',
        ])->assertRedirect(route('main.accounting.stock.index', [$company, $site, 'units']));

        $unit = AccountingStockUnit::query()->where('symbol', 'pcs')->firstOrFail();

        $this->assertDatabaseHas('accounting_stock_units', [
            'company_site_id' => $site->id,
            'reference' => 'UNT-000001',
            'symbol' => 'pcs',
            'type' => AccountingStockUnit::TYPE_QUANTITY,
        ]);

        $itemsRoute = route('main.accounting.stock.index', [$company, $site, 'items']);

        $this->actingAs($admin)->get($itemsRoute)
            ->assertOk()
            ->assertSee('data-warehouse-id="'.$warehouse->id.'"', false)
            ->assertSee('data-category-id="'.$category->id.'"', false)
            ->assertSee(CurrencyCatalog::label('CDF'), false)
            ->assertSee(CurrencyCatalog::label('USD'), false)
            ->assertDontSee(CurrencyCatalog::label('EUR'), false);

        $this->actingAs($admin)->post(route('main.accounting.stock.store', [$company, $site, 'items']), [
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'name' => 'Devise hors site',
            'type' => AccountingStockItem::TYPE_PRODUCT,
            'currency' => 'EUR',
            'purchase_price' => 100,
            'sale_price' => 120,
            'current_stock' => 1,
            'min_stock' => 0,
            'status' => 'active',
        ])->assertSessionHasErrors('currency');

        $this->actingAs($admin)->post(route('main.accounting.stock.store', [$company, $site, 'items']), [
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'name' => 'Ciment gris',
            'type' => AccountingStockItem::TYPE_PRODUCT,
            'currency' => 'CDF',
            'purchase_price' => 12000,
            'sale_price' => 15000,
            'current_stock' => 10,
            'min_stock' => 3,
            'status' => 'active',
        ])->assertRedirect($itemsRoute);

        $item = AccountingStockItem::query()->where('name', 'Ciment gris')->firstOrFail();

        $this->assertDatabaseHas('accounting_stock_items', [
            'company_site_id' => $site->id,
            'reference' => 'ART-000001',
            'name' => 'Ciment gris',
            'current_stock' => 10,
        ]);

        $this->actingAs($admin)->get(route('main.accounting.stock.index', [$company, $site, 'subcategories']))
            ->assertOk()
            ->assertSee('data-bs-target="#stockRelatedModal"', false)
            ->assertSee(base64_encode(json_encode([[
                'reference' => 'ART-000001',
                'name' => 'Ciment gris',
                'unit' => 'Piece',
                'sale_price' => '15 000,00 CDF',
            ]])), false);

        $this->actingAs($admin)->get(route('main.accounting.stock.index', [$company, $site, 'categories']))
            ->assertOk()
            ->assertSee('data-bs-target="#stockRelatedModal"', false)
            ->assertSee(__('main.category_items_title', ['name' => $category->name]))
            ->assertSee(base64_encode(json_encode([[
                'reference' => 'ART-000001',
                'name' => 'Ciment gris',
                'unit' => 'Piece',
                'subcategory' => $subcategory->name,
            ]])), false);

        $this->actingAs($admin)->post(route('main.accounting.stock.store', [$company, $site, 'movements']), [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'type' => AccountingStockMovement::TYPE_ENTRY,
            'quantity' => 5,
            'movement_date' => now()->format('Y-m-d'),
            'reason' => 'Reception fournisseur',
        ])->assertRedirect(route('main.accounting.stock.index', [$company, $site, 'movements']));

        $item->refresh();

        $this->assertSame(15.0, (float) $item->current_stock);
        $this->assertDatabaseHas('accounting_stock_movements', [
            'company_site_id' => $site->id,
            'reference' => 'MVT-000001',
            'item_id' => $item->id,
            'quantity' => 5,
        ]);

        $this->actingAs($admin)->get($itemsRoute)
            ->assertOk()
            ->assertSee('ART-000001', false)
            ->assertSee('Ciment gris')
            ->assertSee('15,00', false);
    }

    public function test_accounting_service_pages_manage_price_list_and_recurring_services(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Services',
            'code' => 'ACCOUNTING_SERVICES',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'service admin',
            'email' => 'service-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Service Company',
            'country' => 'Congo (RDC)',
            'email' => 'service-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Service Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        AccountingCurrency::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'code' => 'USD',
            'name' => 'Dollar americain',
            'symbol' => '$',
            'exchange_rate' => 2800,
            'is_base' => false,
            'is_default' => false,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $categoriesRoute = route('main.accounting.services.index', [$company, $site, 'categories']);

        $this->actingAs($admin)->get($categoriesRoute)
            ->assertOk()
            ->assertSee(__('main.service_categories'), false)
            ->assertSee(__('main.new_service_category'), false)
            ->assertSee('id="companyTable"', false)
            ->assertSee('resources/js/main/accounting-service-resource.js', false);

        $this->actingAs($admin)->post(route('main.accounting.services.store', [$company, $site, 'units']), [
            'name' => 'Heure',
            'symbol' => 'h',
            'status' => 'active',
        ])->assertRedirect(route('main.accounting.services.index', [$company, $site, 'units']));

        $unit = AccountingServiceUnit::query()->where('symbol', 'h')->firstOrFail();

        $this->assertDatabaseHas('accounting_service_units', [
            'company_site_id' => $site->id,
            'reference' => 'SUN-000001',
            'name' => 'Heure',
        ]);

        $this->actingAs($admin)->post(route('main.accounting.services.store', [$company, $site, 'categories']), [
            'name' => 'Conseil',
            'description' => 'Prestations de conseil',
            'status' => 'active',
        ])->assertRedirect($categoriesRoute);

        $category = AccountingServiceCategory::query()->where('name', 'Conseil')->firstOrFail();

        $this->assertDatabaseHas('accounting_service_categories', [
            'company_site_id' => $site->id,
            'reference' => 'SCA-000001',
            'name' => 'Conseil',
        ]);

        $this->actingAs($admin)->get($categoriesRoute)
            ->assertOk()
            ->assertSee(base64_encode(json_encode([
                'name' => 'Conseil',
                'status' => 'active',
                'description' => 'Prestations de conseil',
            ])), false);

        $this->actingAs($admin)->post(route('main.accounting.services.store', [$company, $site, 'subcategories']), [
            'category_id' => $category->id,
            'name' => 'Audit',
            'description' => 'Audit de processus',
            'status' => 'active',
        ])->assertRedirect(route('main.accounting.services.index', [$company, $site, 'subcategories']));

        $subcategory = AccountingServiceSubcategory::query()->where('name', 'Audit')->firstOrFail();

        $this->assertDatabaseHas('accounting_service_subcategories', [
            'company_site_id' => $site->id,
            'reference' => 'SSC-000001',
            'category_id' => $category->id,
            'name' => 'Audit',
        ]);

        $priceListRoute = route('main.accounting.services.index', [$company, $site, 'price-list']);

        $this->actingAs($admin)->get($priceListRoute)
            ->assertOk()
            ->assertSee('data-category-id="'.$category->id.'"', false)
            ->assertSee(CurrencyCatalog::label('CDF'), false)
            ->assertSee(CurrencyCatalog::label('USD'), false)
            ->assertDontSee(CurrencyCatalog::label('EUR'), false);

        $this->actingAs($admin)->post(route('main.accounting.services.store', [$company, $site, 'price-list']), [
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'unit_id' => $unit->id,
            'name' => 'Service devise hors site',
            'billing_type' => AccountingService::BILLING_FIXED,
            'price' => 500,
            'currency' => 'EUR',
            'tax_rate' => 16,
            'estimated_duration' => 60,
            'status' => 'active',
        ])->assertSessionHasErrors('currency');

        $this->actingAs($admin)->post(route('main.accounting.services.store', [$company, $site, 'price-list']), [
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'unit_id' => $unit->id,
            'name' => 'Audit financier',
            'billing_type' => AccountingService::BILLING_FIXED,
            'price' => 500000,
            'currency' => 'CDF',
            'tax_rate' => 16,
            'estimated_duration' => 180,
            'status' => 'active',
            'description' => 'Audit complet',
        ])->assertRedirect($priceListRoute);

        $service = AccountingService::query()->where('name', 'Audit financier')->firstOrFail();

        $this->assertDatabaseHas('accounting_services', [
            'company_site_id' => $site->id,
            'reference' => 'SRV-000001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Audit financier',
        ]);

        $this->actingAs($admin)->get(route('main.accounting.services.index', [$company, $site, 'subcategories']))
            ->assertOk()
            ->assertSee('data-bs-target="#serviceRelatedModal"', false)
            ->assertSee(base64_encode(json_encode([[
                'reference' => 'SRV-000001',
                'name' => 'Audit financier',
                'unit' => 'Heure',
                'price' => '500 000,00 CDF',
            ]])), false);

        $this->actingAs($admin)->get(route('main.accounting.services.index', [$company, $site, 'categories']))
            ->assertOk()
            ->assertSee('data-bs-target="#serviceRelatedModal"', false)
            ->assertSee(__('main.category_services_title', ['name' => $category->name]))
            ->assertSee(base64_encode(json_encode([[
                'reference' => 'SRV-000001',
                'name' => 'Audit financier',
                'unit' => 'Heure',
                'subcategory' => $subcategory->name,
            ]])), false);

        $this->actingAs($admin)->post(route('main.accounting.services.store', [$company, $site, 'recurring']), [
            'service_id' => $service->id,
            'name' => 'Audit mensuel',
            'frequency' => AccountingRecurringService::FREQUENCY_MONTHLY,
            'start_date' => now()->format('Y-m-d'),
            'next_invoice_date' => now()->addMonth()->format('Y-m-d'),
            'status' => 'active',
        ])->assertRedirect(route('main.accounting.services.index', [$company, $site, 'recurring']));

        $this->assertDatabaseHas('accounting_recurring_services', [
            'company_site_id' => $site->id,
            'reference' => 'REC-000001',
            'service_id' => $service->id,
            'name' => 'Audit mensuel',
        ]);

        $this->actingAs($admin)->get($priceListRoute)
            ->assertOk()
            ->assertSee('SRV-000001', false)
            ->assertSee('Audit financier')
            ->assertSee('500 000 CDF', false);
    }

    public function test_accounting_currencies_page_manages_site_currencies(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Currencies',
            'code' => 'ACCOUNTING_CURRENCIES',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'currency admin',
            'email' => 'currency-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Currency Company',
            'country' => 'Congo (RDC)',
            'email' => 'currency-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Currency Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.currencies', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.currencies'), false)
            ->assertSee(__('main.new_currency'), false)
            ->assertSee('resources/js/main/accounting-currencies.js', false)
            ->assertSee('data-currency-mode="view"', false);

        $this->assertDatabaseHas('accounting_currencies', [
            'company_site_id' => $site->id,
            'reference' => 'CUR-000001',
            'code' => 'CDF',
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
        ]);

        $baseCurrency = AccountingCurrency::query()
            ->where('company_site_id', $site->id)
            ->where('is_default', true)
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('main.accounting.currencies.destroy', [$company, $site, $baseCurrency]))
            ->assertRedirect($route);

        $this->assertDatabaseHas('accounting_currencies', [
            'id' => $baseCurrency->id,
            'is_default' => true,
        ]);

        $this->actingAs($admin)->post(route('main.accounting.currencies.store', [$company, $site]), [
            'code' => 'USD',
            'exchange_rate' => 2800,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ])->assertRedirect($route);

        $usd = AccountingCurrency::query()->where('code', 'USD')->firstOrFail();

        $this->assertDatabaseHas('accounting_currencies', [
            'company_site_id' => $site->id,
            'reference' => 'CUR-000002',
            'code' => 'USD',
            'name' => 'Dollar américain',
            'symbol' => '$',
            'exchange_rate' => 2800,
            'is_base' => false,
            'is_default' => false,
        ]);

        $this->actingAs($admin)->put(route('main.accounting.currencies.update', [$company, $site, $usd]), [
            'code' => 'USD',
            'exchange_rate' => 2850,
            'status' => AccountingCurrency::STATUS_INACTIVE,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_currencies', [
            'id' => $usd->id,
            'exchange_rate' => 2850,
            'status' => AccountingCurrency::STATUS_INACTIVE,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('Dollar américain')
            ->assertSee('2 850,00', false)
            ->assertSeeInOrder(['Franc congolais', 'Dollar américain']);
    }

    public function test_accounting_payment_methods_page_manages_site_payment_methods(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Payment Methods',
            'code' => 'ACCOUNTING_PAYMENT_METHODS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'payment admin',
            'email' => 'payment-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Payment Company',
            'country' => 'Congo (RDC)',
            'email' => 'payment-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Payment Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.payment-methods', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.payment_methods'), false)
            ->assertSee(__('main.new_payment_method'), false)
            ->assertSee('resources/js/main/accounting-payment-methods.js', false)
            ->assertSee('data-payment-method-mode="view"', false);

        $this->assertDatabaseHas('accounting_payment_methods', [
            'company_site_id' => $site->id,
            'reference' => 'PAY-000001',
            'name' => 'Espèces',
            'type' => AccountingPaymentMethod::TYPE_CASH,
            'currency_code' => 'CDF',
            'is_default' => true,
            'is_system_default' => true,
        ]);

        $systemMethod = AccountingPaymentMethod::query()
            ->where('company_site_id', $site->id)
            ->where('is_system_default', true)
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('main.accounting.payment-methods.destroy', [$company, $site, $systemMethod]))
            ->assertRedirect($route);

        $this->assertDatabaseHas('accounting_payment_methods', [
            'id' => $systemMethod->id,
            'is_system_default' => true,
        ]);

        $this->actingAs($admin)->post(route('main.accounting.payment-methods.store', [$company, $site]), [
            'name' => 'Compte Rawbank',
            'type' => AccountingPaymentMethod::TYPE_BANK,
            'currency_code' => 'CDF',
            'code' => 'RAW-CDF',
            'bank_name' => 'Rawbank',
            'account_holder' => 'Payment Company',
            'account_number' => '000123456789',
            'iban' => 'CD12 RAWB 0001',
            'bic_swift' => 'RAWBCDKI',
            'bank_address' => 'Kinshasa',
            'description' => 'Compte principal',
            'is_default' => '1',
            'status' => AccountingPaymentMethod::STATUS_ACTIVE,
        ])->assertRedirect($route);

        $bankMethod = AccountingPaymentMethod::query()
            ->where('company_site_id', $site->id)
            ->where('type', AccountingPaymentMethod::TYPE_BANK)
            ->firstOrFail();

        $this->assertDatabaseHas('accounting_payment_methods', [
            'id' => $bankMethod->id,
            'reference' => 'PAY-000002',
            'name' => 'Compte Rawbank',
            'currency_code' => 'CDF',
            'bank_name' => 'Rawbank',
            'account_number' => '000123456789',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('accounting_payment_methods', [
            'id' => $systemMethod->id,
            'is_default' => false,
        ]);

        $this->actingAs($admin)->put(route('main.accounting.payment-methods.update', [$company, $site, $bankMethod]), [
            'name' => 'Mobile money',
            'type' => AccountingPaymentMethod::TYPE_MOBILE_MONEY,
            'currency_code' => 'CDF',
            'code' => 'MOBILE-CDF',
            'is_default' => '1',
            'status' => AccountingPaymentMethod::STATUS_INACTIVE,
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_payment_methods', [
            'id' => $bankMethod->id,
            'name' => 'Mobile money',
            'type' => AccountingPaymentMethod::TYPE_MOBILE_MONEY,
            'bank_name' => null,
            'account_number' => null,
            'status' => AccountingPaymentMethod::STATUS_INACTIVE,
        ]);

        $client = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Receipt Client',
            'email' => 'receipt-client@example.test',
            'currency' => 'CDF',
        ]);

        $invoice = AccountingSalesInvoice::create([
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'reference' => 'FAC-PAY-METHOD',
            'title' => 'Invoice paid by method',
            'invoice_date' => '2026-05-08',
            'due_date' => '2026-05-15',
            'currency' => 'CDF',
            'status' => AccountingSalesInvoice::STATUS_PARTIALLY_PAID,
            'payment_terms' => '100% on order',
            'subtotal' => 500,
            'discount_total' => 0,
            'total_ht' => 500,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_ttc' => 500,
            'paid_total' => 250,
            'balance_due' => 250,
        ]);

        AccountingSalesInvoicePayment::create([
            'sales_invoice_id' => $invoice->id,
            'payment_method_id' => $bankMethod->id,
            'received_by' => $admin->id,
            'payment_date' => '2026-05-08',
            'amount' => 250,
            'currency' => 'CDF',
            'reference' => 'PAY-RECEIPT-001',
            'notes' => 'Partial receipt',
        ]);

        $protectedMethod = AccountingPaymentMethod::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Protected cash',
            'type' => AccountingPaymentMethod::TYPE_CASH,
            'currency_code' => 'CDF',
            'is_default' => false,
            'is_system_default' => false,
            'status' => AccountingPaymentMethod::STATUS_ACTIVE,
        ]);

        AccountingSalesInvoicePayment::create([
            'sales_invoice_id' => $invoice->id,
            'payment_method_id' => $protectedMethod->id,
            'received_by' => $admin->id,
            'payment_date' => '2026-05-08',
            'amount' => 50,
            'currency' => 'CDF',
            'reference' => 'PAY-PROTECTED-001',
        ]);

        $this->actingAs($admin)
            ->delete(route('main.accounting.payment-methods.destroy', [$company, $site, $protectedMethod]))
            ->assertRedirect($route)
            ->assertSessionHas('success', __('main.payment_method_with_movements_cannot_delete'));

        $this->assertDatabaseHas('accounting_payment_methods', [
            'id' => $protectedMethod->id,
            'name' => 'Protected cash',
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('Mobile money')
            ->assertSee(__('main.payment_method_type_mobile_money'), false)
            ->assertSee(__('main.view_receipts'), false)
            ->assertSee(__('main.disbursements_coming_soon'), false)
            ->assertSee(__('main.payment_method_receipts_title', ['name' => 'Mobile money']), false)
            ->assertSee(__('main.payment_method_receipts_total'), false)
            ->assertSee('FAC-PAY-METHOD')
            ->assertSee('Receipt Client')
            ->assertSee('250,00 CDF')
            ->assertSee('PAY-RECEIPT-001')
            ->assertDontSee(__('main.delete_payment_method_text', ['name' => 'Protected cash']), false)
            ->assertSeeInOrder(['Espèces', 'Mobile money']);
    }

    public function test_accounting_proforma_invoices_page_manages_proformas_with_global_vat(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Proformas',
            'code' => 'ACCOUNTING_PROFORMAS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'proforma admin',
            'email' => 'proforma-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Proforma Company',
            'country' => 'Congo (RDC)',
            'email' => 'proforma-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Proforma Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        AccountingCurrency::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'code' => 'USD',
            'name' => 'Dollar americain',
            'symbol' => '$',
            'exchange_rate' => 2800,
            'is_base' => false,
            'is_default' => false,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $client = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Client Proforma',
        ]);

        $route = route('main.accounting.proforma-invoices', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.proforma_invoices'), false)
            ->assertSee(__('main.new_proforma_invoice'), false)
            ->assertSee(route('main.accounting.proforma-invoices.create', [$company, $site]), false);

        $this->actingAs($admin)
            ->get(route('main.accounting.proforma-invoices.create', [$company, $site]))
            ->assertOk()
            ->assertSee(__('main.new_proforma_invoice'), false)
            ->assertSee(CurrencyCatalog::label('CDF'), false)
            ->assertSee(CurrencyCatalog::label('USD'), false)
            ->assertDontSee(CurrencyCatalog::label('EUR'), false)
            ->assertSee('data-default-value="16.00"', false)
            ->assertSee(__('main.offer_validity'), false)
            ->assertSee(__('main.payment_terms_half_order'), false)
            ->assertSee(__('main.payment_terms_to_discuss'), false)
            ->assertSee(__('main.create_stock_item_from_free_line'), false)
            ->assertSee(__('main.import_supplier_quote'), false)
            ->assertSee(route('main.accounting.proforma-invoices.import-quote', [$company, $site]), false)
            ->assertSee('data-proforma-line-list', false)
            ->assertSee('resources/js/main/accounting-proforma-invoices.js', false);

        $importResponse = $this->actingAs($admin)->post(route('main.accounting.proforma-invoices.import-quote', [$company, $site]), [
            'client_id' => $client->id,
            'title' => 'Offre depuis quotation',
            'issue_date' => '2026-05-01',
            'expiration_date' => '2026-05-15',
            'currency' => 'CDF',
            'status' => AccountingProformaInvoice::STATUS_DRAFT,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_TO_DISCUSS,
            'tax_rate' => 16,
            'supplier_quote_create_stock_items' => '1',
            'supplier_quote_file' => UploadedFile::fake()->createWithContent('supplier-quote.csv', "Description;Quantity;Unit Price\nRouteur fournisseur;2;125\nSupport premium;1;80\n"),
            'lines' => [
                [
                    'line_type' => AccountingProformaInvoiceLine::TYPE_FREE,
                    'description' => '',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'discount_amount' => 0,
                ],
            ],
        ]);

        $importResponse
            ->assertRedirect(route('main.accounting.proforma-invoices.create', [$company, $site]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Routeur fournisseur', session()->getOldInput('lines.0.description'));
        $this->assertSame('2.00', session()->getOldInput('lines.0.quantity'));
        $this->assertSame('125.00', session()->getOldInput('lines.0.cost_price'));
        $this->assertSame('1', session()->getOldInput('lines.0.create_stock_item'));
        $this->assertSame('Support premium', session()->getOldInput('lines.1.description'));

        $this->actingAs($admin)->post(route('main.accounting.proforma-invoices.store', [$company, $site]), [
            'client_id' => $client->id,
            'title' => 'Devise non configuree',
            'issue_date' => '2026-05-01',
            'currency' => 'EUR',
            'status' => AccountingProformaInvoice::STATUS_DRAFT,
            'tax_rate' => 16,
            'lines' => [
                [
                    'line_type' => AccountingProformaInvoiceLine::TYPE_FREE,
                    'description' => 'Maintenance applicative',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_amount' => 0,
                ],
            ],
        ])->assertSessionHasErrors(['currency', 'expiration_date']);

        $this->actingAs($admin)->post(route('main.accounting.proforma-invoices.store', [$company, $site]), [
            'client_id' => $client->id,
            'title' => 'Offre maintenance',
            'issue_date' => '2026-05-01',
            'expiration_date' => '2026-05-15',
            'currency' => 'CDF',
            'status' => AccountingProformaInvoice::STATUS_DRAFT,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_HALF_ORDER,
            'tax_rate' => 16,
            'notes' => 'Validite de quinze jours.',
            'terms' => 'Paiement a la commande.',
            'lines' => [
                [
                    'line_type' => AccountingProformaInvoiceLine::TYPE_FREE,
                    'description' => 'Maintenance applicative',
                    'details' => 'Pack initial',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount_amount' => 10,
                ],
                [
                    'line_type' => AccountingProformaInvoiceLine::TYPE_FREE,
                    'description' => 'Support',
                    'details' => 'Article propose hors catalogue',
                    'quantity' => 1,
                    'unit_price' => 50,
                    'discount_amount' => 0,
                    'create_stock_item' => 1,
                ],
            ],
        ])->assertRedirect($route);

        $proforma = AccountingProformaInvoice::query()->firstOrFail();

        $this->assertDatabaseHas('accounting_proforma_invoices', [
            'id' => $proforma->id,
            'reference' => 'PRO-000001',
            'client_id' => $client->id,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_HALF_ORDER,
            'subtotal' => 250,
            'discount_total' => 10,
            'total_ht' => 240,
            'tax_rate' => 16,
            'tax_amount' => 38.4,
            'total_ttc' => 278.4,
        ]);
        $this->assertSame('2026-05-15', $proforma->expiration_date->format('Y-m-d'));

        $this->assertDatabaseHas('accounting_proforma_invoice_lines', [
            'proforma_invoice_id' => $proforma->id,
            'description' => 'Maintenance applicative',
            'line_total' => 190,
        ]);

        $createdItem = AccountingStockItem::query()
            ->where('company_site_id', $site->id)
            ->where('name', 'Support')
            ->firstOrFail();

        $this->assertDatabaseHas('accounting_stock_items', [
            'id' => $createdItem->id,
            'company_site_id' => $site->id,
            'name' => 'Support',
            'purchase_price' => 0,
            'sale_price' => 50,
            'current_stock' => 0,
            'currency' => 'CDF',
        ]);

        $this->assertDatabaseHas('accounting_proforma_invoice_lines', [
            'proforma_invoice_id' => $proforma->id,
            'line_type' => AccountingProformaInvoiceLine::TYPE_ITEM,
            'item_id' => $createdItem->id,
            'description' => 'Support',
            'line_total' => 50,
        ]);

        $this->actingAs($admin)
            ->get(route('main.accounting.proforma-invoices.edit', [$company, $site, $proforma]))
            ->assertOk()
            ->assertSee(__('main.edit_proforma_invoice'), false)
            ->assertSee(route('main.accounting.proforma-invoices.update', [$company, $site, $proforma]), false)
            ->assertSee('Offre maintenance', false)
            ->assertSee('2026-05-15', false)
            ->assertSee('Maintenance applicative', false)
            ->assertSee('resources/js/main/accounting-proforma-invoices.js', false);

        $this->actingAs($admin)->put(route('main.accounting.proforma-invoices.update', [$company, $site, $proforma]), [
            'client_id' => $client->id,
            'title' => 'Offre maintenance acceptee',
            'issue_date' => '2026-05-01',
            'expiration_date' => '2026-05-20',
            'currency' => 'CDF',
            'status' => AccountingProformaInvoice::STATUS_ACCEPTED,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
            'tax_rate' => 10,
            'lines' => [
                [
                    'line_type' => AccountingProformaInvoiceLine::TYPE_FREE,
                    'description' => 'Maintenance applicative',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'discount_type' => AccountingProformaInvoiceLine::DISCOUNT_PERCENT,
                    'discount_amount' => 10,
                ],
            ],
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_proforma_invoices', [
            'id' => $proforma->id,
            'title' => 'Offre maintenance acceptee',
            'status' => AccountingProformaInvoice::STATUS_ACCEPTED,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
            'discount_total' => 20,
            'total_ht' => 180,
            'tax_amount' => 18,
            'total_ttc' => 198,
        ]);
        $this->assertSame('2026-05-20', $proforma->fresh()->expiration_date->format('Y-m-d'));

        $this->assertDatabaseHas('accounting_proforma_invoice_lines', [
            'proforma_invoice_id' => $proforma->id,
            'discount_type' => AccountingProformaInvoiceLine::DISCOUNT_PERCENT,
            'discount_amount' => 10,
            'line_total' => 180,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('PRO-000001')
            ->assertSee('Client Proforma')
            ->assertSee('198,00 CDF', false)
            ->assertSee(route('main.accounting.proforma-invoices.print', [$company, $site, $proforma]), false)
            ->assertSee(route('main.accounting.proforma-invoices.edit', [$company, $site, $proforma]), false)
            ->assertSee(route('main.accounting.proforma-invoices.convert-to-order', [$company, $site, $proforma]), false)
            ->assertSee(__('main.proforma_status_accepted'), false);

        $this->actingAs($admin)
            ->post(route('main.accounting.proforma-invoices.convert-to-order', [$company, $site, $proforma]))
            ->assertRedirect(route('main.accounting.customer-orders', [$company, $site]));

        $order = AccountingCustomerOrder::query()
            ->where('proforma_invoice_id', $proforma->id)
            ->firstOrFail();

        $this->assertDatabaseHas('accounting_customer_orders', [
            'id' => $order->id,
            'reference' => 'CMD-000001',
            'client_id' => $client->id,
            'proforma_invoice_id' => $proforma->id,
            'status' => AccountingCustomerOrder::STATUS_CONFIRMED,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
            'subtotal' => 200,
            'discount_total' => 20,
            'total_ht' => 180,
            'tax_amount' => 18,
            'total_ttc' => 198,
        ]);

        $this->assertDatabaseHas('accounting_customer_order_lines', [
            'customer_order_id' => $order->id,
            'line_type' => AccountingCustomerOrderLine::TYPE_FREE,
            'description' => 'Maintenance applicative',
            'quantity' => 1,
            'unit_price' => 200,
            'discount_type' => AccountingCustomerOrderLine::DISCOUNT_PERCENT,
            'discount_amount' => 10,
            'line_total' => 180,
            'margin_total' => 180,
        ]);

        $this->assertSame(AccountingProformaInvoice::STATUS_CONVERTED, $proforma->fresh()->status);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(route('main.accounting.proforma-invoices.print', [$company, $site, $proforma]), false)
            ->assertDontSee(route('main.accounting.proforma-invoices.edit', [$company, $site, $proforma]), false)
            ->assertDontSee(route('main.accounting.proforma-invoices.convert-to-order', [$company, $site, $proforma]), false)
            ->assertSee(__('main.proforma_status_converted'), false);

        $this->actingAs($admin)
            ->get(route('main.accounting.proforma-invoices.print', [$company, $site, $proforma]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_accounting_customer_orders_page_manages_orders_with_item_margins(): void
    {
        $subscription = Subscription::create([
            'name' => 'Accounting Orders',
            'code' => 'ACCOUNTING_ORDERS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'order admin',
            'email' => 'order-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Order Company',
            'country' => 'Congo (RDC)',
            'email' => 'order-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Order Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $currency = AccountingCurrency::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'code' => 'CDF',
            'name' => 'Franc congolais',
            'symbol' => 'FC',
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $client = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Client Commande',
        ]);

        $unit = AccountingStockUnit::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Piece',
            'symbol' => 'pc',
            'type' => AccountingStockUnit::TYPE_QUANTITY,
            'status' => 'active',
        ]);

        $warehouse = AccountingStockWarehouse::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Entrepot test',
            'code' => 'DEP-TEST',
            'status' => 'active',
        ]);

        $category = AccountingStockCategory::create([
            'company_site_id' => $site->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'name' => 'Materiel',
            'status' => 'active',
        ]);

        $item = AccountingStockItem::create([
            'company_site_id' => $site->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'name' => 'Routeur',
            'type' => AccountingStockItem::TYPE_PRODUCT,
            'purchase_price' => 80,
            'sale_price' => 100,
            'currency' => $currency->code,
            'status' => 'active',
        ]);

        $route = route('main.accounting.customer-orders', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.customer_orders'), false)
            ->assertSee(__('main.new_customer_order'), false)
            ->assertSee(route('main.accounting.customer-orders.create', [$company, $site]), false);

        $this->actingAs($admin)
            ->get(route('main.accounting.customer-orders.create', [$company, $site]))
            ->assertOk()
            ->assertSee(__('main.customer_order_lines'), false)
            ->assertSee(__('main.margin_method'), false)
            ->assertSee(__('main.create_stock_item_from_free_line'), false)
            ->assertSee('resources/js/main/accounting-customer-orders.js', false);

        $this->actingAs($admin)->post(route('main.accounting.customer-orders.store', [$company, $site]), [
            'client_id' => $client->id,
            'title' => 'Commande reseau',
            'order_date' => '2026-05-07',
            'expected_delivery_date' => '2026-05-15',
            'currency' => 'CDF',
            'status' => AccountingCustomerOrder::STATUS_CONFIRMED,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
            'tax_rate' => 16,
            'lines' => [
                [
                    'line_type' => AccountingCustomerOrderLine::TYPE_ITEM,
                    'item_id' => $item->id,
                    'description' => 'Routeur',
                    'quantity' => 2,
                    'cost_price' => 80,
                    'unit_price' => 100,
                    'margin_type' => AccountingCustomerOrderLine::MARGIN_PERCENT,
                    'margin_value' => 25,
                    'discount_type' => AccountingCustomerOrderLine::DISCOUNT_FIXED,
                    'discount_amount' => 10,
                ],
                [
                    'line_type' => AccountingCustomerOrderLine::TYPE_FREE,
                    'description' => 'Switch non catalogue',
                    'details' => 'Article a creer depuis la commande',
                    'quantity' => 1,
                    'cost_price' => 30,
                    'unit_price' => 50,
                    'margin_type' => AccountingCustomerOrderLine::MARGIN_FIXED,
                    'margin_value' => 20,
                    'discount_type' => AccountingCustomerOrderLine::DISCOUNT_FIXED,
                    'discount_amount' => 0,
                    'create_stock_item' => 1,
                ],
            ],
        ])->assertRedirect($route);

        $order = AccountingCustomerOrder::query()->firstOrFail();

        $this->assertDatabaseHas('accounting_customer_orders', [
            'id' => $order->id,
            'reference' => 'CMD-000001',
            'client_id' => $client->id,
            'subtotal' => 250,
            'cost_total' => 190,
            'discount_total' => 10,
            'total_ht' => 240,
            'margin_total' => 50,
            'tax_amount' => 38.4,
            'total_ttc' => 278.4,
        ]);

        $this->assertDatabaseHas('accounting_customer_order_lines', [
            'customer_order_id' => $order->id,
            'item_id' => $item->id,
            'cost_price' => 80,
            'margin_type' => AccountingCustomerOrderLine::MARGIN_PERCENT,
            'margin_value' => 25,
            'line_total' => 190,
            'margin_total' => 30,
        ]);

        $createdItem = AccountingStockItem::query()
            ->where('company_site_id', $site->id)
            ->where('name', 'Switch non catalogue')
            ->firstOrFail();

        $this->assertDatabaseHas('accounting_customer_order_lines', [
            'customer_order_id' => $order->id,
            'line_type' => AccountingCustomerOrderLine::TYPE_ITEM,
            'item_id' => $createdItem->id,
            'description' => 'Switch non catalogue',
            'line_total' => 50,
            'margin_total' => 20,
        ]);

        $this->actingAs($admin)
            ->get(route('main.accounting.customer-orders.edit', [$company, $site, $order]))
            ->assertOk()
            ->assertSee(__('main.edit_customer_order'), false)
            ->assertSee('Commande reseau', false)
            ->assertSee('Routeur', false);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('CMD-000001')
            ->assertSee('Client Commande')
            ->assertSee('50,00 CDF', false)
            ->assertSee('278,40 CDF', false);
    }

    public function test_accounting_sales_invoices_page_manages_invoice_payments_and_pdf(): void
    {
        $subscription = Subscription::create([
            'name' => 'Sales Invoices',
            'code' => 'SALES_INVOICES',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'invoice admin',
            'email' => 'invoice-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Invoice Company',
            'country' => 'Congo (RDC)',
            'email' => 'invoice-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Invoice Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        AccountingCurrency::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'code' => 'CDF',
            'name' => 'Franc congolais',
            'symbol' => 'FC',
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $client = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Client Facture',
            'address' => 'Kinshasa',
        ]);

        $paymentMethod = AccountingPaymentMethod::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Caisse CDF',
            'type' => AccountingPaymentMethod::TYPE_CASH,
            'currency_code' => 'CDF',
            'is_default' => true,
            'status' => AccountingPaymentMethod::STATUS_ACTIVE,
        ]);

        $route = route('main.accounting.sales-invoices', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.sales_invoices'), false)
            ->assertSee(__('main.new_sales_invoice'), false)
            ->assertSee(route('main.accounting.sales-invoices.create', [$company, $site]), false);

        $this->actingAs($admin)
            ->get(route('main.accounting.sales-invoices.create', [$company, $site]))
            ->assertOk()
            ->assertSee(__('main.sales_invoice_lines'), false)
            ->assertSee(__('main.payment_terms_half_order'), false)
            ->assertSee('resources/js/main/accounting-sales-invoices.js', false);

        $this->actingAs($admin)->post(route('main.accounting.sales-invoices.store', [$company, $site]), [
            'client_id' => $client->id,
            'title' => 'Facturation finale',
            'invoice_date' => '2026-05-07',
            'due_date' => '2026-06-07',
            'currency' => 'CDF',
            'status' => AccountingSalesInvoice::STATUS_ISSUED,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_HALF_ORDER,
            'tax_rate' => 16,
            'notes' => 'Merci.',
            'terms' => "Paiement selon accord.\nLivraison incluse.",
            'lines' => [
                [
                    'line_type' => AccountingSalesInvoiceLine::TYPE_FREE,
                    'description' => 'Prestation facturable',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount_type' => AccountingSalesInvoiceLine::DISCOUNT_FIXED,
                    'discount_amount' => 10,
                ],
            ],
        ])->assertRedirect($route);

        $invoice = AccountingSalesInvoice::query()->firstOrFail();

        $this->assertDatabaseHas('accounting_sales_invoices', [
            'id' => $invoice->id,
            'reference' => 'FAC-000001',
            'client_id' => $client->id,
            'status' => AccountingSalesInvoice::STATUS_ISSUED,
            'subtotal' => 200,
            'discount_total' => 10,
            'total_ht' => 190,
            'tax_amount' => 30.4,
            'total_ttc' => 220.4,
            'balance_due' => 220.4,
        ]);

        $this->assertDatabaseHas('accounting_sales_invoice_lines', [
            'sales_invoice_id' => $invoice->id,
            'description' => 'Prestation facturable',
            'line_total' => 190,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee('FAC-000001')
            ->assertSee('Client Facture')
            ->assertSee('220,40 CDF', false)
            ->assertSee(__('main.sales_invoice_status_issued'), false)
            ->assertSee(route('main.accounting.sales-invoices.print', [$company, $site, $invoice]), false);

        $this->actingAs($admin)->post(route('main.accounting.sales-invoices.payments.store', [$company, $site, $invoice]), [
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => '2026-05-08',
            'amount' => 120.4,
            'reference' => 'PAY-CLIENT-1',
        ])->assertRedirect($route);

        $this->assertDatabaseHas('accounting_sales_invoice_payments', [
            'sales_invoice_id' => $invoice->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 120.4,
            'reference' => 'PAY-CLIENT-1',
        ]);

        $this->assertSame(AccountingSalesInvoice::STATUS_PARTIALLY_PAID, $invoice->fresh()->status);
        $this->assertSame('100.00', $invoice->fresh()->balance_due);

        $this->actingAs($admin)->from($route)->post(route('main.accounting.sales-invoices.payments.store', [$company, $site, $invoice]), [
            'payment_invoice_id' => $invoice->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => '2026-05-08',
            'amount' => 100.01,
            'reference' => 'PAY-OVERPAID',
        ])
            ->assertRedirect($route)
            ->assertSessionHasErrors('amount');

        $this->assertSame('100.00', $invoice->fresh()->balance_due);
        $this->assertSame(1, AccountingSalesInvoicePayment::query()->count());

        $this->actingAs($admin)->from($route)->post(route('main.accounting.sales-invoices.payments.store', [$company, $site, $invoice]), [
            'payment_invoice_id' => $invoice->id,
            'payment_date' => '2026-05-08',
            'amount' => 10,
            'reference' => 'PAY-NO-METHOD',
        ])
            ->assertRedirect($route)
            ->assertSessionHasErrors('payment_method_id');

        $this->assertSame('100.00', $invoice->fresh()->balance_due);
        $this->assertSame(1, AccountingSalesInvoicePayment::query()->count());

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.view_payments'), false)
            ->assertSee(__('main.sales_invoice_payments_title', ['reference' => 'FAC-000001']), false)
            ->assertSee('PAY-CLIENT-1')
            ->assertSee('120,40 CDF', false)
            ->assertSee('Caisse CDF');

        $this->actingAs($admin)->post(route('main.accounting.sales-invoices.payments.store', [$company, $site, $invoice]), [
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => '2026-05-09',
            'amount' => 100,
        ])->assertRedirect($route);

        $this->assertSame(AccountingSalesInvoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->balance_due);
        $this->assertSame(2, AccountingSalesInvoicePayment::query()->count());

        $this->actingAs($admin)
            ->get(route('main.accounting.sales-invoices.print', [$company, $site, $invoice]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_accounting_receipts_page_lists_site_receipts_and_filters(): void
    {
        $subscription = Subscription::create([
            'name' => 'Receipts',
            'code' => 'RECEIPTS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'receipts admin',
            'email' => 'receipts-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Receipts Company',
            'country' => 'Congo (RDC)',
            'email' => 'receipts-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Receipts Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        AccountingCurrency::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'code' => 'CDF',
            'name' => 'Franc congolais',
            'symbol' => 'FC',
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $client = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Client Encaissement',
            'address' => 'Kinshasa',
        ]);

        $method = AccountingPaymentMethod::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Caisse CDF',
            'type' => AccountingPaymentMethod::TYPE_CASH,
            'currency_code' => 'CDF',
            'status' => AccountingPaymentMethod::STATUS_ACTIVE,
            'is_default' => true,
        ]);

        $invoice = AccountingSalesInvoice::create([
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'reference' => 'FAC-000123',
            'title' => 'Facture test encaissement',
            'invoice_date' => '2026-05-07',
            'due_date' => '2026-05-20',
            'currency' => 'CDF',
            'status' => AccountingSalesInvoice::STATUS_PARTIALLY_PAID,
            'subtotal' => 100,
            'discount_total' => 0,
            'total_ht' => 100,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_ttc' => 100,
            'paid_total' => 40,
            'balance_due' => 60,
        ]);

        AccountingSalesInvoicePayment::create([
            'sales_invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'received_by' => $admin->id,
            'payment_date' => '2026-05-08',
            'amount' => 40,
            'currency' => 'CDF',
            'reference' => 'PAY-REC-001',
        ]);

        $route = route('main.accounting.receipts', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.payments_received'), false)
            ->assertSee(__('main.receipts_subtitle'), false)
            ->assertSee('FAC-000123')
            ->assertSee('Client Encaissement')
            ->assertSee('PAY-REC-001')
            ->assertSee('40,00 CDF', false)
            ->assertSee(route('main.accounting.sales-invoices.print', [$company, $site, $invoice]), false);

        $this->actingAs($admin)->get($route.'?payment_method_id='.$method->id)
            ->assertOk()
            ->assertSee('PAY-REC-001');

        $this->actingAs($admin)->get($route.'?search=PAY-REC-001')
            ->assertOk()
            ->assertSee('PAY-REC-001')
            ->assertSee('>1</strong>', false);
    }

    public function test_accounting_cash_register_creates_quick_paid_sale_and_releases_stock(): void
    {
        $subscription = Subscription::create([
            'name' => 'Cash Register',
            'code' => 'CASH_REGISTER',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'cash admin',
            'email' => 'cash-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Cash Company',
            'country' => 'Congo (RDC)',
            'email' => 'cash-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Cash Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        AccountingCurrency::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'code' => 'CDF',
            'name' => 'Franc congolais',
            'symbol' => 'FC',
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $method = AccountingPaymentMethod::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Caisse Principale',
            'type' => AccountingPaymentMethod::TYPE_CASH,
            'currency_code' => 'CDF',
            'status' => AccountingPaymentMethod::STATUS_ACTIVE,
            'is_default' => true,
        ]);

        $unit = AccountingStockUnit::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Piece',
            'symbol' => 'pc',
            'type' => AccountingStockUnit::TYPE_QUANTITY,
            'status' => 'active',
        ]);

        $warehouse = AccountingStockWarehouse::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Depot caisse',
            'code' => 'DEP-CASH',
            'status' => 'active',
            'is_default' => true,
        ]);

        $category = AccountingStockCategory::create([
            'company_site_id' => $site->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'name' => 'Articles caisse',
            'status' => AccountingStockCategory::STATUS_ACTIVE,
        ]);

        $item = AccountingStockItem::create([
            'company_site_id' => $site->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'name' => 'Clavier comptoir',
            'type' => AccountingStockItem::TYPE_PRODUCT,
            'sale_price' => 100,
            'current_stock' => 5,
            'currency' => 'CDF',
            'status' => 'active',
        ]);

        $route = route('main.accounting.cash-register', [$company, $site]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.cash_register'), false)
            ->assertDontSee(__('main.cash_register_subtitle'), false)
            ->assertSee(__('main.open_cash_register'), false);

        $session = AccountingCashRegisterSession::create([
            'company_site_id' => $site->id,
            'opened_by' => $admin->id,
            'reference' => 'CAI-ADMIN-001',
            'status' => AccountingCashRegisterSession::STATUS_OPEN,
            'opened_at' => now(),
            'opening_float' => 50,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee(__('main.close_cash_register'), false)
            ->assertSee('Clavier comptoir');

        $response = $this->actingAs($admin)->post(route('main.accounting.cash-register.store', [$company, $site]), [
            'sale_date' => '2026-05-08',
            'currency' => 'CDF',
            'payment_method_id' => $method->id,
            'payment_reference' => 'TICKET-001',
            'payment_received' => 250,
            'tax_rate' => 0,
            'lines' => [[
                'line_type' => AccountingSalesInvoiceLine::TYPE_ITEM,
                'item_id' => $item->id,
                'description' => 'Clavier comptoir',
                'quantity' => 2,
                'unit_price' => 100,
                'discount_type' => AccountingSalesInvoiceLine::DISCOUNT_FIXED,
                'discount_amount' => 0,
            ]],
        ]);

        $response
            ->assertRedirect($route)
            ->assertSessionHas('success', __('main.cash_register_sale_saved'));

        $invoice = AccountingSalesInvoice::query()->firstOrFail();

        $this->assertSame(AccountingSalesInvoice::TITLE_CASH_REGISTER, $invoice->title);
        $this->assertSame($session->id, $invoice->cash_register_session_id);
        $this->assertSame(AccountingSalesInvoice::STATUS_PAID, $invoice->status);
        $this->assertSame('200.00', (string) $invoice->total_ttc);
        $this->assertSame(1, AccountingSalesInvoicePayment::query()->count());
        $this->assertSame('250.00', (string) AccountingSalesInvoicePayment::query()->firstOrFail()->amount_received);
        $this->assertSame('50.00', (string) AccountingSalesInvoicePayment::query()->firstOrFail()->change_due);
        $this->assertSame(1, AccountingStockMovement::query()->where('type', AccountingStockMovement::TYPE_EXIT)->count());
        $this->assertSame(3.0, (float) $item->fresh()->current_stock);

        $this->actingAs($admin)->get($route.'?search=TICKET-001')
            ->assertOk()
            ->assertSee($invoice->reference)
            ->assertSee('200,00 CDF', false)
            ->assertSee(route('main.accounting.sales-invoices.print', [$company, $site, $invoice]), false);

        $cashier = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'cash user',
            'email' => 'cash-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $cashier->sites()->attach($site->id, [
            'module_permissions' => json_encode([CompanySite::MODULE_ACCOUNTING => [AccountingSalesInvoice::TITLE_CASH_REGISTER]]),
            'can_create' => true,
            'can_update' => true,
            'can_delete' => false,
        ]);

        $cashierSession = AccountingCashRegisterSession::create([
            'company_site_id' => $site->id,
            'opened_by' => $cashier->id,
            'reference' => 'CAI-USER-001',
            'status' => AccountingCashRegisterSession::STATUS_OPEN,
            'opened_at' => now()->addMinute(),
            'opening_float' => 10,
        ]);

        $cashierInvoice = AccountingSalesInvoice::create([
            'company_site_id' => $site->id,
            'cash_register_session_id' => $cashierSession->id,
            'client_id' => $invoice->client_id,
            'created_by' => $cashier->id,
            'reference' => 'FAC-USER-CASH',
            'title' => AccountingSalesInvoice::TITLE_CASH_REGISTER,
            'invoice_date' => '2026-05-08',
            'due_date' => '2026-05-08',
            'currency' => 'CDF',
            'status' => AccountingSalesInvoice::STATUS_PAID,
            'payment_terms' => AccountingProformaInvoice::PAYMENT_FULL_ORDER,
            'subtotal' => 25,
            'discount_total' => 0,
            'total_ht' => 25,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_ttc' => 25,
            'paid_total' => 25,
            'balance_due' => 0,
        ]);

        $this->actingAs($admin)->get($route)
            ->assertOk()
            ->assertSee($invoice->reference)
            ->assertSee($cashierInvoice->reference)
            ->assertSee('CAI-ADMIN-001')
            ->assertSee('CAI-USER-001');

        $this->actingAs($cashier)->get($route)
            ->assertOk()
            ->assertSee($cashierInvoice->reference)
            ->assertSee('CAI-USER-001')
            ->assertDontSee($invoice->reference)
            ->assertDontSee('CAI-ADMIN-001');

        $closeResponse = $this->actingAs($admin)->post(route('main.accounting.cash-register.close', [$company, $site, $session]), [
            'counted_cash_amount' => 250,
            'counted_other_amount' => 0,
        ]);

        $closeResponse
            ->assertRedirect($route)
            ->assertSessionHas('success', __('main.cash_register_session_closed'));

        $this->assertSame(AccountingCashRegisterSession::STATUS_CLOSED, $session->fresh()->status);
        $this->assertSame('250.00', (string) $session->fresh()->expected_total_amount);
        $this->assertSame('0.00', (string) $session->fresh()->difference_amount);

        $this->actingAs($admin)
            ->get(route('main.accounting.cash-register.report', [$company, $site, $session]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_accounting_delivery_notes_create_partial_delivery_release_stock_and_print_pdf(): void
    {
        $subscription = Subscription::create([
            'name' => 'Delivery Notes',
            'code' => 'DELIVERY_NOTES',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'delivery admin',
            'email' => 'delivery-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Delivery Company',
            'country' => 'Congo (RDC)',
            'email' => 'delivery-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Delivery Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $currency = AccountingCurrency::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'code' => 'CDF',
            'name' => 'Franc congolais',
            'symbol' => 'FC',
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $client = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Client Livraison',
            'address' => 'Kinshasa',
        ]);

        $unit = AccountingStockUnit::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Piece',
            'symbol' => 'pc',
            'type' => AccountingStockUnit::TYPE_QUANTITY,
            'status' => 'active',
        ]);

        $warehouse = AccountingStockWarehouse::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Entrepot livraison',
            'code' => 'DEP-LIV',
            'status' => 'active',
            'is_default' => true,
        ]);

        $category = AccountingStockCategory::create([
            'company_site_id' => $site->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'name' => 'Materiel livraison',
            'status' => 'active',
        ]);

        $item = AccountingStockItem::create([
            'company_site_id' => $site->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'name' => 'Serveur livraison',
            'type' => AccountingStockItem::TYPE_PRODUCT,
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'current_stock' => 10,
            'currency' => $currency->code,
            'status' => 'active',
        ]);

        $order = AccountingCustomerOrder::create([
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'title' => 'Commande livraison',
            'order_date' => '2026-05-07',
            'expected_delivery_date' => '2026-05-10',
            'currency' => 'CDF',
            'status' => AccountingCustomerOrder::STATUS_CONFIRMED,
            'subtotal' => 7500,
            'cost_total' => 5000,
            'margin_total' => 2500,
            'margin_rate' => 50,
            'discount_total' => 0,
            'total_ht' => 7500,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_ttc' => 7500,
        ]);

        $orderLine = AccountingCustomerOrderLine::create([
            'customer_order_id' => $order->id,
            'line_type' => AccountingCustomerOrderLine::TYPE_ITEM,
            'item_id' => $item->id,
            'description' => 'Serveur livraison',
            'quantity' => 5,
            'cost_price' => 1000,
            'unit_price' => 1500,
            'line_total' => 7500,
            'cost_total' => 5000,
            'margin_total' => 2500,
        ]);

        $indexRoute = route('main.accounting.delivery-notes', [$company, $site]);

        $this->actingAs($admin)->get($indexRoute)
            ->assertOk()
            ->assertSee(__('main.delivery_notes'), false)
            ->assertSee(__('main.new_delivery_note'), false)
            ->assertSee($order->reference, false);

        $this->actingAs($admin)
            ->get(route('main.accounting.delivery-notes.create', [$company, $site, 'order' => $order->id]))
            ->assertOk()
            ->assertSee(__('main.quantity_to_deliver'), false)
            ->assertSee(__('main.serial_numbers'), false)
            ->assertSee('Serveur livraison', false)
            ->assertSee('5,00', false);

        $this->actingAs($admin)->post(route('main.accounting.delivery-notes.store', [$company, $site]), [
            'customer_order_id' => $order->id,
            'title' => 'Livraison partielle',
            'delivery_date' => '2026-05-08',
            'status' => AccountingDeliveryNote::STATUS_PARTIAL,
            'delivered_by' => 'Livreur interne',
            'carrier' => 'Pickup',
            'notes' => 'Premiere livraison',
            'lines' => [
                [
                    'customer_order_line_id' => $orderLine->id,
                    'quantity' => 2,
                    'serial_numbers' => ['SN-LIV-001', 'SN-LIV-002'],
                ],
            ],
        ])->assertRedirect($indexRoute);

        $deliveryNote = AccountingDeliveryNote::query()->firstOrFail();

        $this->assertDatabaseHas('accounting_delivery_notes', [
            'id' => $deliveryNote->id,
            'reference' => 'BL-000001',
            'customer_order_id' => $order->id,
            'client_id' => $client->id,
            'status' => AccountingDeliveryNote::STATUS_PARTIAL,
        ]);

        $this->assertDatabaseHas('accounting_delivery_note_lines', [
            'delivery_note_id' => $deliveryNote->id,
            'customer_order_line_id' => $orderLine->id,
            'quantity' => 2,
            'line_total' => 3000,
        ]);

        $deliveryLine = AccountingDeliveryNoteLine::query()->firstOrFail();

        $this->assertDatabaseHas('accounting_delivery_note_serials', [
            'delivery_note_line_id' => $deliveryLine->id,
            'serial_number' => 'SN-LIV-001',
            'position' => 1,
        ]);

        $this->assertDatabaseHas('accounting_delivery_note_serials', [
            'delivery_note_line_id' => $deliveryLine->id,
            'serial_number' => 'SN-LIV-002',
            'position' => 2,
        ]);

        $this->assertDatabaseHas('accounting_stock_movements', [
            'company_site_id' => $site->id,
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'type' => AccountingStockMovement::TYPE_EXIT,
            'quantity' => 2,
        ]);

        $this->assertSame(8.0, $item->fresh()->current_stock);
        $this->assertSame(AccountingCustomerOrder::STATUS_IN_PROGRESS, $order->fresh()->status);

        $this->actingAs($admin)
            ->get(route('main.accounting.delivery-notes.print', [$company, $site, $deliveryNote]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)->post(route('main.accounting.delivery-notes.store', [$company, $site]), [
            'customer_order_id' => $order->id,
            'title' => 'Livraison finale',
            'delivery_date' => '2026-05-09',
            'status' => AccountingDeliveryNote::STATUS_DELIVERED,
            'lines' => [
                [
                    'customer_order_line_id' => $orderLine->id,
                    'quantity' => 3,
                ],
            ],
        ])->assertRedirect($indexRoute);

        $this->assertSame(5.0, $item->fresh()->current_stock);
        $this->assertSame(AccountingCustomerOrder::STATUS_DELIVERED, $order->fresh()->status);
        $this->assertSame(2, AccountingDeliveryNoteLine::query()->count());
        $this->assertSame(2, AccountingDeliveryNoteSerial::query()->count());
    }

    public function test_delivery_note_stock_error_keeps_order_lines_visible(): void
    {
        $subscription = Subscription::create([
            'name' => 'Delivery Stock Error',
            'code' => 'DELIVERY_STOCK_ERROR',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'stock error admin',
            'email' => 'stock-error-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Stock Error Company',
            'country' => 'Congo (RDC)',
            'email' => 'stock-error-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Stock Error Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $client = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Client Stock Error',
        ]);

        $unit = AccountingStockUnit::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Piece',
            'symbol' => 'pc',
            'type' => AccountingStockUnit::TYPE_QUANTITY,
            'status' => 'active',
        ]);

        $warehouse = AccountingStockWarehouse::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'name' => 'Entrepot insuffisant',
            'code' => 'DEP-INS',
            'status' => 'active',
            'is_default' => true,
        ]);

        $category = AccountingStockCategory::create([
            'company_site_id' => $site->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'name' => 'Stock insuffisant',
            'status' => 'active',
        ]);

        $item = AccountingStockItem::create([
            'company_site_id' => $site->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'name' => 'Serveur HP Proliant380 gen10',
            'type' => AccountingStockItem::TYPE_PRODUCT,
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'current_stock' => 0,
            'currency' => 'CDF',
            'status' => 'active',
        ]);

        $order = AccountingCustomerOrder::create([
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'order_date' => '2026-05-07',
            'currency' => 'CDF',
            'status' => AccountingCustomerOrder::STATUS_CONFIRMED,
            'subtotal' => 1500,
            'total_ht' => 1500,
            'total_ttc' => 1500,
        ]);

        $orderLine = AccountingCustomerOrderLine::create([
            'customer_order_id' => $order->id,
            'line_type' => AccountingCustomerOrderLine::TYPE_ITEM,
            'item_id' => $item->id,
            'description' => $item->name,
            'quantity' => 1,
            'unit_price' => 1500,
            'line_total' => 1500,
        ]);

        $createRoute = route('main.accounting.delivery-notes.create', [$company, $site, 'order' => $order->id]);

        $this->actingAs($admin)
            ->from($createRoute)
            ->post(route('main.accounting.delivery-notes.store', [$company, $site]), [
                'customer_order_id' => $order->id,
                'delivery_date' => '2026-05-08',
                'status' => AccountingDeliveryNote::STATUS_DELIVERED,
                'lines' => [
                    [
                        'customer_order_line_id' => $orderLine->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertRedirect($createRoute)
            ->assertSessionHasErrors('lines');

        $this->actingAs($admin)
            ->get($createRoute)
            ->assertOk()
            ->assertSee('Serveur HP Proliant380 gen10', false)
            ->assertSee('1,00', false)
            ->assertDontSee('value=\"0,00\"', false);
    }

    public function test_non_accounting_module_opens_under_development_page(): void
    {
        $subscription = Subscription::create([
            'name' => 'HR Module',
            'code' => 'HR_MODULE',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'hr admin',
            'email' => 'hr-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'HR Company',
            'country' => 'Congo (RDC)',
            'email' => 'hr-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'HR Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_HUMAN_RESOURCES],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('main.companies.sites.modules.show', [$company, $site, CompanySite::MODULE_HUMAN_RESOURCES]));

        $response->assertOk();
        $response->assertSee(__('main.module_human_resources'), false);
        $response->assertSee(__('main.module_human_resources_description'), false);
        $response->assertSee(__('main.module_under_development'), false);
        $response->assertSee(__('main.connected_as', ['name' => $admin->name]), false);
    }

    public function test_assigned_user_only_sees_site_modules_from_permissions(): void
    {
        $subscription = Subscription::create([
            'name' => 'Site Detail User',
            'code' => 'SITE_DETAIL_USER',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'assigned admin',
            'email' => 'assigned-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $user = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'assigned user',
            'email' => 'assigned-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Assigned Company',
            'country' => 'Congo (RDC)',
            'email' => 'assigned-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Assigned Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $user->sites()->sync([
            $site->id => [
                'module_permissions' => json_encode([
                    CompanySite::MODULE_ACCOUNTING => [
                        'can_create' => true,
                        'can_update' => false,
                        'can_delete' => false,
                    ],
                ]),
                'can_create' => true,
                'can_update' => false,
                'can_delete' => false,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('main.companies.sites.show', [$company, $site]));

        $response->assertOk();
        $response->assertSee(__('main.module_accounting'), false);
        $response->assertSee(route('main.companies.sites.modules.show', [$company, $site, CompanySite::MODULE_ACCOUNTING]), false);
        $response->assertDontSee(__('main.module_human_resources'), false);

        $this->actingAs($user)
            ->get(route('main.companies.sites.modules.show', [$company, $site, CompanySite::MODULE_HUMAN_RESOURCES]))
            ->assertRedirect(route('main.companies.sites.show', [$company, $site]));
    }

    public function test_simple_user_lands_directly_on_assigned_site_and_cannot_open_main_lists(): void
    {
        $subscription = Subscription::create([
            'name' => 'Simple User Site',
            'code' => 'SIMPLE_USER_SITE',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'simple admin',
            'email' => 'simple-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $user = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'simple user',
            'email' => 'simple-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Simple Company',
            'country' => 'Congo (RDC)',
            'email' => 'simple-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Simple Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $user->sites()->sync([
            $site->id => [
                'module_permissions' => json_encode([
                    CompanySite::MODULE_ACCOUNTING => [
                        'can_create' => true,
                        'can_update' => false,
                        'can_delete' => false,
                    ],
                ]),
                'can_create' => true,
                'can_update' => false,
                'can_delete' => false,
            ],
        ]);

        $siteRoute = route('main.companies.sites.show', [$company, $site]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'StrongPass@123',
        ])->assertRedirect($siteRoute);

        $this->assertAuthenticatedAs($user);

        $this->actingAs($user)->get(route('main'))->assertRedirect($siteRoute);
        $this->actingAs($user)->get(route('main.users'))->assertRedirect($siteRoute);
        $this->actingAs($user)->get(route('main.companies.sites', $company))->assertRedirect($siteRoute);

        $response = $this->actingAs($user)->get($siteRoute);

        $response->assertOk();
        $response->assertSee('Simple Site');
        $response->assertSee(__('main.module_accounting'), false);
        $response->assertDontSee(__('main.module_human_resources'), false);
        $response->assertDontSee('id="companyTable"', false);
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

    public function test_normal_user_can_only_be_responsible_for_one_site_while_admin_can_handle_many(): void
    {
        $subscription = Subscription::create([
            'name' => 'Responsible Rules',
            'code' => 'RESPONSIBLE_RULES',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'responsible admin',
            'email' => 'responsible-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $normalUser = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'responsible user',
            'email' => 'responsible-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Responsible Company',
            'country' => 'Congo (RDC)',
            'email' => 'responsible-company@example.test',
        ]);

        CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $normalUser->id,
            'name' => 'First User Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->from(route('main.companies.sites', $company))
            ->post(route('main.companies.sites.store', $company), [
                'name' => 'Second User Site',
                'type' => CompanySite::TYPE_OFFICE,
                'responsible_id' => $normalUser->id,
                'modules' => [CompanySite::MODULE_ACCOUNTING],
                'currency' => 'CDF',
                'status' => CompanySite::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors(['responsible_id']);

        $this->actingAs($admin)
            ->post(route('main.companies.sites.store', $company), [
                'name' => 'Second Admin Site',
                'type' => CompanySite::TYPE_OFFICE,
                'responsible_id' => $admin->id,
                'modules' => [CompanySite::MODULE_ACCOUNTING],
                'currency' => 'CDF',
                'status' => CompanySite::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('main.companies.sites', $company));
    }

    public function test_admin_can_manage_subscription_users_with_site_permissions(): void
    {
        $subscription = Subscription::create([
            'name' => 'Managed Users',
            'code' => 'MANAGED_USERS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'manager admin',
            'email' => 'manager-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Managed Company',
            'country' => 'Congo (RDC)',
            'email' => 'managed-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Managed Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'modules' => [CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('main.users'));

        $response->assertOk();
        $response->assertSee(__('main.users'), false);
        $response->assertSee(__('admin.new_user'), false);
        $response->assertSee('Managed Site - Managed Company', false);
        $response->assertSee('modulePermissionsBody', false);
        $response->assertDontSee('data-user-modules="[&amp;quot;', false);
        $response->assertSee('bi-clock-history', false);
        $response->assertSee(route('main.users.login-history', $admin), false);
        $response->assertDontSee('data-user-action="'.route('main.users.update', $admin).'"', false);
        $response->assertSee('autocomplete="new-password" data-required-message="'.__('admin.required_admin_password').'" data-password-rules-target="userPasswordRules"', false);
        $response->assertSee('id="userPasswordRules"', false);
        $response->assertSee('id="userPasswordConfirmation"', false);
        $this->assertLessThan(
            strpos($response->getContent(), 'id="userPasswordConfirmation"'),
            strpos($response->getContent(), 'id="userPasswordRules"'),
        );

        $this->actingAs($admin)
            ->post(route('main.users.store'), [
                'name' => 'Managed User',
                'email' => 'managed-user@example.test',
                'password' => 'StrongPass@123',
                'password_confirmation' => 'StrongPass@123',
                'role' => User::ROLE_USER,
                'site_id' => $site->id,
                'modules' => [CompanySite::MODULE_ACCOUNTING],
                'module_permissions' => [
                    CompanySite::MODULE_ACCOUNTING => [
                        'can_create' => '1',
                        'can_update' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('main.users'));

        $account = User::where('email', 'managed-user@example.test')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('main.users'));
        $response->assertSee("data-user-site-id=\"{$site->id}\"", false);
        $response->assertSee('data-user-modules=\'["accounting"]\'', false);
        $response->assertSee('data-user-module-permissions=\'{"accounting":{"can_create":true,"can_update":true,"can_delete":false}}\'', false);

        $this->assertDatabaseHas('company_site_user', [
            'company_site_id' => $site->id,
            'user_id' => $account->id,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => false,
        ]);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $account->id,
            'can_view' => true,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('main.users.update', $account), [
                'form_mode' => 'edit',
                'user_id' => $account->id,
                'name' => 'Managed User Updated',
                'email' => 'managed-user@example.test',
                'password' => '',
                'password_confirmation' => '',
                'role' => User::ROLE_USER,
                'site_id' => $site->id,
                'modules' => [CompanySite::MODULE_HUMAN_RESOURCES],
                'module_permissions' => [
                    CompanySite::MODULE_HUMAN_RESOURCES => [
                        'can_delete' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('main.users'));

        $this->assertDatabaseHas('company_site_user', [
            'company_site_id' => $site->id,
            'user_id' => $account->id,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('main.users.destroy', $account))
            ->assertRedirect(route('main.users'));

        $this->assertDatabaseMissing('users', ['id' => $account->id]);
    }

    public function test_admin_cannot_update_self_from_managed_users_page(): void
    {
        $subscription = Subscription::create([
            'name' => 'Self Protection',
            'code' => 'SELF_PROTECTION',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'self admin',
            'email' => 'self-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Self Protection Company',
            'country' => 'Congo (RDC)',
            'email' => 'self-protection-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Self Protection Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->put(route('main.users.update', $admin), [
                'form_mode' => 'edit',
                'user_id' => $admin->id,
                'name' => 'Changed Self Admin',
                'email' => 'changed-self-admin@example.test',
                'role' => User::ROLE_USER,
                'site_id' => $site->id,
                'modules' => [CompanySite::MODULE_ACCOUNTING],
            ])
            ->assertRedirect(route('main'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'self admin',
            'email' => 'self-admin@example.test',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_user_management_lists_current_admin_first_then_latest_user(): void
    {
        $subscription = Subscription::create([
            'name' => 'Ordered Users',
            'code' => 'ORDERED_USERS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'current admin',
            'email' => 'current-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        User::create([
            'subscription_id' => $subscription->id,
            'name' => 'old user',
            'email' => 'old-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        User::create([
            'subscription_id' => $subscription->id,
            'name' => 'latest user',
            'email' => 'latest-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Ordered Company',
            'country' => 'Congo (RDC)',
            'email' => 'ordered-company@example.test',
        ]);

        CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Ordered Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('main.users'));
        $content = $response->getContent();

        $response->assertOk();
        $this->assertLessThan(strpos($content, 'latest user'), strpos($content, 'current admin'));
        $this->assertLessThan(strpos($content, 'old user'), strpos($content, 'latest user'));
    }

    public function test_admin_can_view_paginated_user_login_history(): void
    {
        $subscription = Subscription::create([
            'name' => 'Login History',
            'code' => 'LOGIN_HISTORY',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'history admin',
            'email' => 'history-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $account = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'history user',
            'email' => 'history-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        foreach (range(1, 6) as $index) {
            UserLoginHistory::create([
                'user_id' => $account->id,
                'device' => 'Edge on Windows',
                'ip_address' => '196.250.72.'.$index,
                'user_agent' => 'Mozilla/5.0 Edg/147.0.0.0 Windows',
                'logged_in_at' => now()->subMinutes($index),
            ]);
        }

        $response = $this->actingAs($admin)
            ->getJson(route('main.users.login-history', ['account' => $account, 'page' => 2]));

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 6)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.device', 'Edge on Windows');
    }

    public function test_superadmin_can_view_paginated_user_login_history(): void
    {
        $superadmin = User::create([
            'name' => 'history superadmin',
            'email' => 'history-superadmin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $account = User::create([
            'name' => 'global history user',
            'email' => 'global-history-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        foreach (range(1, 6) as $index) {
            UserLoginHistory::create([
                'user_id' => $account->id,
                'device' => 'Edge on Windows',
                'ip_address' => '196.250.88.'.$index,
                'user_agent' => 'Mozilla/5.0 Edg/147.0.0.0 Windows',
                'logged_in_at' => now()->subMinutes($index),
            ]);
        }

        $response = $this->actingAs($superadmin)
            ->getJson(route('admin.users.login-history', ['account' => $account, 'page' => 2]));

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 6)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.device', 'Edge on Windows');
    }

    public function test_managed_admin_user_receives_all_site_module_permissions(): void
    {
        $subscription = Subscription::create([
            'name' => 'Managed Admin Users',
            'code' => 'MANAGED_ADMIN_USERS',
            'type' => 'business',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'root admin',
            'email' => 'root-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
        ]);

        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Admin Permission Company',
            'country' => 'Congo (RDC)',
            'email' => 'admin-permission-company@example.test',
        ]);

        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Admin Permission Site',
            'type' => CompanySite::TYPE_PRODUCTION,
            'modules' => [CompanySite::MODULE_ACCOUNTING, CompanySite::MODULE_HUMAN_RESOURCES],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $secondCompany = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Second Admin Permission Company',
            'country' => 'Congo (RDC)',
            'email' => 'second-admin-permission-company@example.test',
        ]);

        $secondSite = CompanySite::create([
            'company_id' => $secondCompany->id,
            'responsible_id' => $admin->id,
            'name' => 'Second Admin Permission Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ARCHIVING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->post(route('main.users.store'), [
                'name' => 'Managed Admin',
                'email' => 'managed-admin@example.test',
                'password' => 'StrongPass@123',
                'password_confirmation' => 'StrongPass@123',
                'role' => User::ROLE_ADMIN,
            ])
            ->assertRedirect(route('main.users'));

        $account = User::where('email', 'managed-admin@example.test')->firstOrFail();
        $this->assertCount(2, $account->sites()->get());

        $assignedSite = $account->sites()->whereKey($site->id)->firstOrFail();
        $permissions = json_decode($assignedSite->pivot->module_permissions, true);

        $this->assertSame([
            CompanySite::MODULE_ACCOUNTING,
            CompanySite::MODULE_HUMAN_RESOURCES,
        ], array_keys($permissions));
        $this->assertTrue($permissions[CompanySite::MODULE_ACCOUNTING]['can_create']);
        $this->assertTrue($permissions[CompanySite::MODULE_ACCOUNTING]['can_update']);
        $this->assertTrue($permissions[CompanySite::MODULE_ACCOUNTING]['can_delete']);
        $this->assertTrue($permissions[CompanySite::MODULE_HUMAN_RESOURCES]['can_create']);
        $this->assertTrue($permissions[CompanySite::MODULE_HUMAN_RESOURCES]['can_update']);
        $this->assertTrue($permissions[CompanySite::MODULE_HUMAN_RESOURCES]['can_delete']);

        $this->assertDatabaseHas('company_site_user', [
            'company_site_id' => $site->id,
            'user_id' => $account->id,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);

        $this->assertDatabaseHas('company_site_user', [
            'company_site_id' => $secondSite->id,
            'user_id' => $account->id,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);
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
        $response->assertSee('bi-clock-history', false);
        $response->assertSee(route('admin.users.login-history', $superadmin), false);
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
        $response->assertDontSee('CÃƒ', false);
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

    public function test_authenticated_users_can_open_profile_page(): void
    {
        $user = User::create([
            'name' => 'Profile User',
            'email' => 'profile-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee(__('profile.title'), false);
        $response->assertSee(route('profile.information.update'), false);
        $response->assertSee(route('profile.photo.update'), false);
        $response->assertSee(route('profile.email.update'), false);
        $response->assertSee(route('profile.password.update'), false);
        $response->assertSee('profileCropModal', false);
        $response->assertSee('cropper.min.js', false);
        $response->assertSee('const themeButton = document.getElementById', false);
        $response->assertDontSee('/js/main.js', false);
    }

    public function test_superadmin_profile_page_uses_admin_sidebar_navigation(): void
    {
        $superadmin = User::create([
            'name' => 'Profile Superadmin',
            'email' => 'profile-superadmin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($superadmin)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('dashboard-shell main-shell', false);
        $response->assertSee('dashboard-sidebar', false);
        $response->assertSee(route('admin.dashboard'), false);
        $response->assertSee(route('admin.subscriptions'), false);
        $response->assertSee(route('admin.users'), false);
        $response->assertSee(route('admin.companies'), false);
        $response->assertSee('id="sidebarToggle"', false);
    }

    public function test_normal_user_profile_page_keeps_simple_navigation(): void
    {
        $user = User::create([
            'name' => 'Simple Profile User',
            'email' => 'simple-profile-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('class="main-shell"', false);
        $response->assertDontSee('<aside class="dashboard-sidebar"', false);
    }

    public function test_profile_photo_is_rendered_in_navigation_and_user_lists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/avatar.jpg', 'avatar');

        $subscription = Subscription::create([
            'name' => 'Avatar subscription',
            'code' => 'AVATAR',
            'status' => 'active',
        ]);

        $admin = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'Avatar Admin',
            'email' => 'avatar-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_ADMIN,
            'profile_photo_path' => 'profile-photos/avatar.jpg',
        ]);

        $worker = User::create([
            'subscription_id' => $subscription->id,
            'name' => 'Avatar Worker',
            'email' => 'avatar-worker@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
            'profile_photo_path' => 'profile-photos/avatar.jpg',
        ]);

        Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Avatar Company',
            'country' => 'Congo (RDC)',
            'email' => 'avatar-company@example.test',
        ]);

        $mainResponse = $this->actingAs($admin)->get(route('main'));
        $mainResponse->assertOk();
        $mainResponse->assertSee('profile-photos/avatar.jpg', false);

        $usersResponse = $this->actingAs($admin)->get(route('main.users'));
        $usersResponse->assertOk();
        $usersResponse->assertSee('profile-photos/avatar.jpg', false);
        $usersResponse->assertSee('alt="'.$worker->name.'"', false);

        $superadmin = User::create([
            'name' => 'Avatar Superadmin',
            'email' => 'avatar-superadmin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
            'profile_photo_path' => 'profile-photos/avatar.jpg',
        ]);

        $adminResponse = $this->actingAs($superadmin)->get(route('admin.dashboard'));
        $adminResponse->assertOk();
        $adminResponse->assertSee('profile-photos/avatar.jpg', false);
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::create([
            'name' => 'Old Name',
            'email' => 'profile-info@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.information.update'), [
                'name' => 'New Name',
                'phone_number' => '+243810000000',
                'grade' => 'Manager',
                'address' => 'Kinshasa',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'phone_number' => '+243810000000',
            'grade' => 'Manager',
            'address' => 'Kinshasa',
        ]);
    }

    public function test_user_can_change_email_with_current_password(): void
    {
        $user = User::create([
            'name' => 'Email User',
            'email' => 'old-email@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.email.update'), [
                'email' => 'new-email@example.test',
                'current_password' => 'StrongPass@123',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new-email@example.test',
        ]);
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = User::create([
            'name' => 'Password User',
            'email' => 'password-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), [
                'current_password' => 'StrongPass@123',
                'password' => 'EvenStronger@456',
                'password_confirmation' => 'EvenStronger@456',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('EvenStronger@456', $user->fresh()->password));
    }

    public function test_user_can_update_profile_photo_from_cropped_image(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Photo User',
            'email' => 'photo-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $onePixelPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.photo.update'), [
                'cropped_photo' => $onePixelPng,
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_user_can_enable_confirm_and_disable_two_factor_authentication(): void
    {
        $user = User::create([
            'name' => 'Two Factor User',
            'email' => 'two-factor-user@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.two-factor.enable'))
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.two-factor.confirm'), ['code' => $code])
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.two-factor.disable'), ['current_password' => 'StrongPass@123'])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_two_factor_users_are_challenged_after_password_login(): void
    {
        $user = User::create([
            'name' => 'Two Factor Superadmin',
            'email' => 'two-factor-superadmin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $secret = app(Google2FA::class)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode([])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post(route('login'), [
            'email' => 'two-factor-superadmin@example.test',
            'password' => 'StrongPass@123',
        ])->assertRedirect(route('two-factor.login'));

        $this->assertGuest();

        $this->post(route('two-factor.login.store'), [
            'code' => '000000',
        ])->assertRedirect(route('two-factor.login'));

        $this->assertGuest();

        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->post(route('two-factor.login.store'), [
            'code' => $code,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}

