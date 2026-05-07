<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::controller(MainController::class)->group(function (): void {
    Route::get('/', 'root');

    Route::middleware('auth')->group(function (): void {
        Route::get('/main', 'index')->name('main');
        Route::get('/main/companies/create', 'createCompany')->name('main.companies.create');
        Route::post('/main/companies', 'storeCompany')->name('main.companies.store');
        Route::get('/main/companies/{company}/edit', 'editCompany')->name('main.companies.edit');
        Route::put('/main/companies/{company}', 'updateCompany')->name('main.companies.update');
        Route::delete('/main/companies/{company}', 'destroyCompany')->name('main.companies.destroy');
        Route::get('/main/companies/{company}/sites', 'companySites')->name('main.companies.sites');
        Route::post('/main/companies/{company}/sites', 'storeCompanySite')->name('main.companies.sites.store');
        Route::get('/main/companies/{company}/sites/{site}', 'showCompanySite')->name('main.companies.sites.show');
        Route::get('/main/companies/{company}/sites/{site}/modules/{module}', 'showSiteModule')->name('main.companies.sites.modules.show');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/clients', 'accountingClients')->name('main.accounting.clients');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/clients', 'storeAccountingClient')->name('main.accounting.clients.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/clients/{client}', 'updateAccountingClient')->name('main.accounting.clients.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/clients/{client}', 'destroyAccountingClient')->name('main.accounting.clients.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/prospects', 'accountingProspects')->name('main.accounting.prospects');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/prospects', 'storeAccountingProspect')->name('main.accounting.prospects.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/prospects/{prospect}', 'updateAccountingProspect')->name('main.accounting.prospects.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/prospects/{prospect}', 'destroyAccountingProspect')->name('main.accounting.prospects.destroy');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/prospects/{prospect}/convert', 'convertAccountingProspect')->name('main.accounting.prospects.convert');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/suppliers', 'accountingSuppliers')->name('main.accounting.suppliers');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/suppliers', 'storeAccountingSupplier')->name('main.accounting.suppliers.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/suppliers/{supplier}', 'updateAccountingSupplier')->name('main.accounting.suppliers.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/suppliers/{supplier}', 'destroyAccountingSupplier')->name('main.accounting.suppliers.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/creditors', 'accountingCreditors')->name('main.accounting.creditors');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/creditors', 'storeAccountingCreditor')->name('main.accounting.creditors.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/creditors/{creditor}', 'updateAccountingCreditor')->name('main.accounting.creditors.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/creditors/{creditor}', 'destroyAccountingCreditor')->name('main.accounting.creditors.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/debtors', 'accountingDebtors')->name('main.accounting.debtors');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/debtors', 'storeAccountingDebtor')->name('main.accounting.debtors.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/debtors/{debtor}', 'updateAccountingDebtor')->name('main.accounting.debtors.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/debtors/{debtor}', 'destroyAccountingDebtor')->name('main.accounting.debtors.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/partners', 'accountingPartners')->name('main.accounting.partners');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/partners', 'storeAccountingPartner')->name('main.accounting.partners.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/partners/{partner}', 'updateAccountingPartner')->name('main.accounting.partners.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/partners/{partner}', 'destroyAccountingPartner')->name('main.accounting.partners.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/sales-representatives', 'accountingSalesRepresentatives')->name('main.accounting.sales-representatives');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/sales-representatives', 'storeAccountingSalesRepresentative')->name('main.accounting.sales-representatives.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/sales-representatives/{representative}', 'updateAccountingSalesRepresentative')->name('main.accounting.sales-representatives.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/sales-representatives/{representative}', 'destroyAccountingSalesRepresentative')->name('main.accounting.sales-representatives.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/currencies', 'accountingCurrencies')->name('main.accounting.currencies');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/currencies', 'storeAccountingCurrency')->name('main.accounting.currencies.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/currencies/{currency}', 'updateAccountingCurrency')->name('main.accounting.currencies.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/currencies/{currency}', 'destroyAccountingCurrency')->name('main.accounting.currencies.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/payment-methods', 'accountingPaymentMethods')->name('main.accounting.payment-methods');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/payment-methods', 'storeAccountingPaymentMethod')->name('main.accounting.payment-methods.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/payment-methods/{method}', 'updateAccountingPaymentMethod')->name('main.accounting.payment-methods.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/payment-methods/{method}', 'destroyAccountingPaymentMethod')->name('main.accounting.payment-methods.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices', 'accountingProformaInvoices')->name('main.accounting.proforma-invoices');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices/create', 'createAccountingProformaInvoice')->name('main.accounting.proforma-invoices.create');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices/import-quote', 'importAccountingProformaQuote')->name('main.accounting.proforma-invoices.import-quote');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices', 'storeAccountingProformaInvoice')->name('main.accounting.proforma-invoices.store');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices/{proforma}/edit', 'editAccountingProformaInvoice')->name('main.accounting.proforma-invoices.edit');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices/{proforma}/print', 'printAccountingProformaInvoice')->name('main.accounting.proforma-invoices.print');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices/{proforma}/convert-to-order', 'convertAccountingProformaToCustomerOrder')->name('main.accounting.proforma-invoices.convert-to-order');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices/{proforma}', 'updateAccountingProformaInvoice')->name('main.accounting.proforma-invoices.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/proforma-invoices/{proforma}', 'destroyAccountingProformaInvoice')->name('main.accounting.proforma-invoices.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/customer-orders', 'accountingCustomerOrders')->name('main.accounting.customer-orders');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/customer-orders/create', 'createAccountingCustomerOrder')->name('main.accounting.customer-orders.create');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/customer-orders', 'storeAccountingCustomerOrder')->name('main.accounting.customer-orders.store');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/customer-orders/{order}/edit', 'editAccountingCustomerOrder')->name('main.accounting.customer-orders.edit');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/customer-orders/{order}', 'updateAccountingCustomerOrder')->name('main.accounting.customer-orders.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/customer-orders/{order}', 'destroyAccountingCustomerOrder')->name('main.accounting.customer-orders.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/stock/{resource}', 'accountingStockIndex')->name('main.accounting.stock.index');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/stock/{resource}', 'storeAccountingStockResource')->name('main.accounting.stock.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/stock/{resource}/{record}', 'updateAccountingStockResource')->name('main.accounting.stock.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/stock/{resource}/{record}', 'destroyAccountingStockResource')->name('main.accounting.stock.destroy');
        Route::get('/main/companies/{company}/sites/{site}/modules/accounting/services/{resource}', 'accountingServiceIndex')->name('main.accounting.services.index');
        Route::post('/main/companies/{company}/sites/{site}/modules/accounting/services/{resource}', 'storeAccountingServiceResource')->name('main.accounting.services.store');
        Route::put('/main/companies/{company}/sites/{site}/modules/accounting/services/{resource}/{record}', 'updateAccountingServiceResource')->name('main.accounting.services.update');
        Route::delete('/main/companies/{company}/sites/{site}/modules/accounting/services/{resource}/{record}', 'destroyAccountingServiceResource')->name('main.accounting.services.destroy');
        Route::put('/main/companies/{company}/sites/{site}', 'updateCompanySite')->name('main.companies.sites.update');
        Route::delete('/main/companies/{company}/sites/{site}', 'destroyCompanySite')->name('main.companies.sites.destroy');
        Route::get('/main/users', 'users')->name('main.users');
        Route::post('/main/users', 'storeUser')->name('main.users.store');
        Route::get('/main/users/{account}/login-history', 'userLoginHistory')->name('main.users.login-history');
        Route::put('/main/users/{account}', 'updateUser')->name('main.users.update');
        Route::delete('/main/users/{account}', 'destroyUser')->name('main.users.destroy');
    });
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'superadmin'])
    ->controller(AdminController::class)
    ->group(function (): void {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        Route::get('/subscriptions', 'subscriptions')->name('subscriptions');
        Route::get('/users', 'users')->name('users');
        Route::get('/users/{account}/login-history', 'userLoginHistory')->name('users.login-history');
        Route::get('/companies', 'companies')->name('companies');
        Route::get('/companies/create', 'createCompany')->name('companies.create');
        Route::post('/companies', 'storeCompany')->name('companies.store');
        Route::get('/companies/{company}/edit', 'editCompany')->name('companies.edit');
        Route::put('/companies/{company}', 'updateCompany')->name('companies.update');
        Route::delete('/companies/{company}', 'destroyCompany')->name('companies.destroy');
        Route::post('/users', 'storeUser')->name('users.store');
        Route::put('/users/{account}', 'updateUser')->name('users.update');
        Route::delete('/users/{account}', 'destroyUser')->name('users.destroy');
        Route::post('/admins', 'storeAdmin')->name('admins.store');
        Route::post('/subscriptions', 'storeSubscription')->name('subscriptions.store');
        Route::put('/subscriptions/{subscription}', 'updateSubscription')->name('subscriptions.update');
        Route::delete('/subscriptions/{subscription}', 'destroySubscription')->name('subscriptions.destroy');
    });

Route::controller(LanguageController::class)->group(function (): void {
    Route::get('/lang/{locale}', 'switch')->name('locale.switch');
});

Route::middleware('auth')->controller(ProfileController::class)->group(function (): void {
    Route::get('/profile', 'edit')->name('profile.edit');
    Route::put('/profile/photo', 'updatePhoto')->name('profile.photo.update');
    Route::put('/profile/information', 'updateInformation')->name('profile.information.update');
    Route::put('/profile/email', 'updateEmail')->name('profile.email.update');
    Route::put('/profile/password', 'updatePassword')->name('profile.password.update');
    Route::post('/profile/two-factor', 'enableTwoFactor')->name('profile.two-factor.enable');
    Route::post('/profile/two-factor/confirm', 'confirmTwoFactor')->name('profile.two-factor.confirm');
    Route::delete('/profile/two-factor', 'disableTwoFactor')->name('profile.two-factor.disable');
});
