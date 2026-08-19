<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Support\CurrentCompany;

afterEach(function () {
    CurrentCompany::clear();
});

test('a company never sees another company\'s customers when a tenant context is set', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Customer::factory()->count(3)->create(['company_id' => $companyA->id]);
    Customer::factory()->count(2)->create(['company_id' => $companyB->id]);

    CurrentCompany::set($companyA->id);

    expect(Customer::count())->toBe(3)
        ->and(Customer::pluck('company_id')->unique()->all())->toBe([$companyA->id]);

    CurrentCompany::set($companyB->id);

    expect(Customer::count())->toBe(2);
});

test('a scoped user cannot load another company\'s customer by id', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $customerB = Customer::factory()->create(['company_id' => $companyB->id]);

    CurrentCompany::set($companyA->id);

    expect(Customer::find($customerB->id))->toBeNull();
});

test('creating a customer auto-fills company_id from the current tenant context', function () {
    $company = Company::factory()->create();

    CurrentCompany::set($company->id);

    $customer = Customer::factory()->make(['company_id' => null]);
    $customer->save();

    expect($customer->fresh()->company_id)->toBe($company->id);
});

test('the global scope is a no-op when no tenant context is set', function () {
    Company::factory()->count(2)->create();
    Customer::factory()->count(2)->create();
    Customer::factory()->count(3)->create();

    expect(CurrentCompany::id())->toBeNull()
        ->and(Customer::count())->toBe(5);
});

test('the login/dashboard flow resolves the tenant context from the authenticated user', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)->get('/dashboard');

    expect(CurrentCompany::id())->toBe($company->id);
});

test('a super admin with no company sees no tenant restriction after login', function () {
    $user = User::factory()->create(['company_id' => null]);

    $this->actingAs($user)->get('/dashboard');

    expect(CurrentCompany::id())->toBeNull();
});
