<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

Route::controller(MainController::class)->group(function (): void {
    Route::get('/', 'root');

    Route::middleware('auth')->group(function (): void {
        Route::get('/main', 'index')->name('main');
        Route::get('/admin/dashboard', 'adminDashboard')
            ->middleware('superadmin')
            ->name('admin.dashboard');
        Route::get('/admin/subscriptions', 'adminSubscriptions')
            ->middleware('superadmin')
            ->name('admin.subscriptions');
        Route::post('/admin/subscriptions', 'storeSubscription')
            ->middleware('superadmin')
            ->name('admin.subscriptions.store');
        Route::put('/admin/subscriptions/{subscription}', 'updateSubscription')
            ->middleware('superadmin')
            ->name('admin.subscriptions.update');
    });
});

Route::controller(LanguageController::class)->group(function (): void {
    Route::get('/lang/{locale}', 'switch')->name('locale.switch');
});
