<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Customers\Form as CustomerForm;
use App\Livewire\Customers\Index as CustomerIndex;
use App\Livewire\Customers\Show as CustomerShow;
use App\Livewire\Dispatch\Center as DispatchCenter;
use App\Livewire\Financial\Index as FinancialIndex;
use App\Livewire\Inventory\Categories\Manager as CategoryManager;
use App\Livewire\Inventory\Products\Form as ProductForm;
use App\Livewire\Inventory\Products\Index as ProductIndex;
use App\Livewire\Inventory\Products\Show as ProductShow;
use App\Livewire\Inventory\Suppliers\Form as SupplierForm;
use App\Livewire\Inventory\Suppliers\Index as SupplierIndex;
use App\Livewire\Portal\MyWorkOrders;
use App\Livewire\Portal\WorkOrderDetail as PortalWorkOrderDetail;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Scheduling\Agenda;
use App\Livewire\Scheduling\Form as AppointmentForm;
use App\Livewire\Settings\ApiTokens;
use App\Livewire\Settings\Notifications as NotificationSettings;
use App\Livewire\Teams\Form as TeamForm;
use App\Livewire\Teams\Index as TeamIndex;
use App\Livewire\Teams\Show as TeamShow;
use App\Livewire\Technicians\Form as TechnicianForm;
use App\Livewire\Technicians\Index as TechnicianIndex;
use App\Livewire\Technicians\Show as TechnicianShow;
use App\Livewire\Users\Form as UserForm;
use App\Livewire\Users\Index as UserIndex;
use App\Livewire\WorkOrders\Form as WorkOrderForm;
use App\Livewire\WorkOrders\Index as WorkOrderIndex;
use App\Livewire\WorkOrders\Show as WorkOrderShow;
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

    Route::prefix('technicians')->name('technicians.')->group(function () {
        Route::get('/', TechnicianIndex::class)->name('index');
        Route::get('/create', TechnicianForm::class)->name('create');
        Route::get('/{technician}', TechnicianShow::class)->name('show');
        Route::get('/{technician}/edit', TechnicianForm::class)->name('edit');
    });

    Route::prefix('teams')->name('teams.')->group(function () {
        Route::get('/', TeamIndex::class)->name('index');
        Route::get('/create', TeamForm::class)->name('create');
        Route::get('/{team}', TeamShow::class)->name('show');
        Route::get('/{team}/edit', TeamForm::class)->name('edit');
    });

    Route::prefix('work-orders')->name('work-orders.')->group(function () {
        Route::get('/', WorkOrderIndex::class)->name('index');
        Route::get('/create', WorkOrderForm::class)->name('create');
        Route::get('/{workOrder}', WorkOrderShow::class)->name('show');
        Route::get('/{workOrder}/edit', WorkOrderForm::class)->name('edit');
    });

    Route::prefix('scheduling')->name('scheduling.')->group(function () {
        Route::get('/', Agenda::class)->name('index');
        Route::get('/create', AppointmentForm::class)->name('create');
        Route::get('/{appointment}/edit', AppointmentForm::class)->name('edit');
    });

    Route::get('/dispatch', DispatchCenter::class)->name('dispatch.index');

    Route::get('/financial', FinancialIndex::class)->name('financial.index');

    Route::get('/reports', ReportsIndex::class)->name('reports.index');

    Route::get('/settings/notifications', NotificationSettings::class)->name('settings.notifications');
    Route::get('/settings/api-tokens', ApiTokens::class)->name('settings.api-tokens');

    Route::get('/audit', AuditIndex::class)->name('audit.index');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', UserIndex::class)->name('index');
        Route::get('/create', UserForm::class)->name('create');
        Route::get('/{user}/edit', UserForm::class)->name('edit');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/categories', CategoryManager::class)->name('categories.index');

        Route::prefix('suppliers')->name('suppliers.')->group(function () {
            Route::get('/', SupplierIndex::class)->name('index');
            Route::get('/create', SupplierForm::class)->name('create');
            Route::get('/{supplier}/edit', SupplierForm::class)->name('edit');
        });

        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', ProductIndex::class)->name('index');
            Route::get('/create', ProductForm::class)->name('create');
            Route::get('/{product}', ProductShow::class)->name('show');
            Route::get('/{product}/edit', ProductForm::class)->name('edit');
        });
    });

    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', MyWorkOrders::class)->name('index');
        Route::get('/work-orders/{workOrder}', PortalWorkOrderDetail::class)->name('work-orders.show');
    });
});
