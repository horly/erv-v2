<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MainController::class, 'root']);

Route::get('/main', [MainController::class, 'index'])
    ->middleware('auth')
    ->name('main');

Route::get('/admin/dashboard', [MainController::class, 'adminDashboard'])
    ->middleware('auth')
    ->name('admin.dashboard');

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])
    ->name('locale.switch');
