<?php

use App\Models\Company;
use App\Support\CurrentCompany;
use Spatie\Permission\PermissionRegistrar;

afterEach(function () {
    CurrentCompany::clear();
});

test('the permissions team resolver defaults to the current tenant', function () {
    $company = Company::factory()->create();

    CurrentCompany::set($company->id);

    $resolver = app(PermissionRegistrar::class)->getPermissionsTeamId();

    expect($resolver)->toBe($company->id);
});

test('the permissions team resolver returns null with no tenant context', function () {
    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBeNull();
});

test('an explicit setPermissionsTeamId call overrides the current tenant', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    CurrentCompany::set($companyA->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($companyB->id);

    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe($companyB->id);
});
