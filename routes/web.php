<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

Route::controller(MainController::class)->group(function (): void {
    Route::get('/', 'root');

    Route::middleware('auth')->group(function (): void {
        Route::get('/main', 'index')->name('main');
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
        Route::get('/companies', 'companies')->name('companies');
        Route::get('/companies/create', 'createCompany')->name('companies.create');
        Route::post('/companies', 'storeCompany')->name('companies.store');
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