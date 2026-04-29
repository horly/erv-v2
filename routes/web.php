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
