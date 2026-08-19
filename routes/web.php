<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Customers\Form as CustomerForm;
use App\Livewire\Customers\Index as CustomerIndex;
use App\Livewire\Customers\Show as CustomerShow;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard.index')->name('dashboard');
    Route::view('/profile', 'profile.edit')->name('profile.edit');
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', CustomerIndex::class)->name('index');
        Route::get('/create', CustomerForm::class)->name('create');
        Route::get('/{customer}', CustomerShow::class)->name('show');
        Route::get('/{customer}/edit', CustomerForm::class)->name('edit');
    });
});
