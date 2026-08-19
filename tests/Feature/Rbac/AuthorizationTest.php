<?php

use App\Actions\SeedDefaultCompanyRoles;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Support\CurrentCompany;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->company = Company::factory()->create();
    (new SeedDefaultCompanyRoles)->handle($this->company);

    CurrentCompany::set($this->company->id);
});

afterEach(function () {
    CurrentCompany::clear();
});

test('a user without the required permission is denied', function () {
    $user = User::factory()->create(['company_id' => $this->company->id]);
    $user->assignRole('technician');

    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    expect($user->can('customers.delete'))->toBeFalse()
        ->and($user->can('delete', $customer))->toBeFalse();
});

test('a user with the required permission via their role is allowed', function () {
    $user = User::factory()->create(['company_id' => $this->company->id]);
    $user->assignRole('support');

    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    expect($user->can('customers.create'))->toBeTrue()
        ->and($user->can('view', $customer))->toBeTrue();
});

test('a policy denies access to another company\'s resource even with the right permission', function () {
    $otherCompany = Company::factory()->create();
    (new SeedDefaultCompanyRoles)->handle($otherCompany);

    $user = User::factory()->create(['company_id' => $this->company->id]);
    $user->assignRole('admin');

    $foreignCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);

    // withoutCompanyScope so the model can be loaded at all for this
    // negative test — under the global scope it simply wouldn't resolve.
    $foreignCustomer = Customer::withoutCompanyScope()->find($foreignCustomer->id);

    expect($user->can('update', $foreignCustomer))->toBeFalse();
});

test('a super admin bypasses every permission check regardless of role', function () {
    $user = User::factory()->create([
        'company_id' => $this->company->id,
        'is_super_admin' => true,
    ]);

    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    expect($user->can('customers.delete'))->toBeTrue()
        ->and($user->can('delete', $customer))->toBeTrue()
        ->and($user->can('anything.not-a-real-permission'))->toBeTrue();
});

test('a super admin bypasses tenant isolation in policies too', function () {
    $otherCompany = Company::factory()->create();

    $superAdmin = User::factory()->create([
        'company_id' => $this->company->id,
        'is_super_admin' => true,
    ]);

    $foreignCustomer = Customer::withoutCompanyScope()->create(['company_id' => $otherCompany->id, 'type' => 'individual', 'name' => 'Foreign']);

    expect($superAdmin->can('update', $foreignCustomer))->toBeTrue();
});
