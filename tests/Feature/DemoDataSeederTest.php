<?php

use App\Enums\WorkOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Team;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\CurrentCompany;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

test('it creates a fully populated demo company across every module', function () {
    (new PermissionSeeder)->run();
    (new DemoDataSeeder)->run();

    $company = Company::where('trading_name', 'Operix Demo')->firstOrFail();
    CurrentCompany::set($company->id);

    expect(User::count())->toBe(6)
        ->and(Technician::count())->toBe(4)
        ->and(Team::count())->toBe(1)
        ->and(Customer::count())->toBe(12)
        ->and(Product::count())->toBe(5)
        ->and(WorkOrder::count())->toBe(9)
        ->and(FinancialTransaction::count())->toBeGreaterThanOrEqual(3)
        ->and(Rating::count())->toBe(2);
});

test('every demo role can log in with the documented password and has its role assigned', function () {
    (new PermissionSeeder)->run();
    (new DemoDataSeeder)->run();

    $company = Company::where('trading_name', 'Operix Demo')->firstOrFail();
    CurrentCompany::set($company->id);

    foreach (['admin', 'manager', 'dispatcher', 'technician', 'financial', 'support'] as $role) {
        $user = User::where('email', "{$role}@demo.operix.test")->firstOrFail();

        expect(Hash::check(DemoDataSeeder::DEMO_PASSWORD, $user->password))->toBeTrue()
            ->and($user->hasRole($role))->toBeTrue()
            ->and($user->status)->toBe('active');
    }
});

test('the demo work orders cover every status in the lifecycle', function () {
    (new PermissionSeeder)->run();
    (new DemoDataSeeder)->run();

    $company = Company::where('trading_name', 'Operix Demo')->firstOrFail();
    CurrentCompany::set($company->id);

    $statuses = WorkOrder::query()->get()->map(fn (WorkOrder $wo) => $wo->status->value)->unique()->sort()->values();

    expect($statuses->all())->toBe([
        WorkOrderStatus::Assigned->value,
        WorkOrderStatus::Cancelled->value,
        WorkOrderStatus::Completed->value,
        WorkOrderStatus::InProgress->value,
        WorkOrderStatus::New->value,
        WorkOrderStatus::Resolved->value,
        WorkOrderStatus::Scheduled->value,
        WorkOrderStatus::WaitingMaterial->value,
    ]);
});

test('at least one product is left below its minimum stock for the critical-stock demo', function () {
    (new PermissionSeeder)->run();
    (new DemoDataSeeder)->run();

    $company = Company::where('trading_name', 'Operix Demo')->firstOrFail();
    CurrentCompany::set($company->id);

    expect(Product::query()->whereColumn('stock_quantity', '<', 'min_stock')->count())->toBeGreaterThan(0);
});
