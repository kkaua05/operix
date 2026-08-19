<?php

use App\Actions\SeedDefaultCompanyRoles;
use App\Models\Company;
use App\Support\CurrentCompany;
use App\Support\Permissions;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

afterEach(function () {
    CurrentCompany::clear();
});

test('it creates every default role scoped to the company with its permissions synced', function () {
    $this->seed(PermissionSeeder::class);

    $company = Company::factory()->create();

    (new SeedDefaultCompanyRoles)->handle($company);

    foreach (Permissions::ROLE_PERMISSIONS as $roleName => $permissions) {
        $role = Role::where('name', $roleName)->where('company_id', $company->id)->first();

        expect($role)->not->toBeNull("role [$roleName] was not created")
            ->and($role->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($permissions)->sort()->values()->all());
    }
});

test('roles are isolated per company', function () {
    $this->seed(PermissionSeeder::class);

    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    (new SeedDefaultCompanyRoles)->handle($companyA);
    (new SeedDefaultCompanyRoles)->handle($companyB);

    $rolesForA = Role::where('company_id', $companyA->id)->pluck('name');
    $rolesForB = Role::where('company_id', $companyB->id)->pluck('name');

    expect($rolesForA)->toHaveCount(count(Permissions::ROLE_PERMISSIONS))
        ->and($rolesForB)->toHaveCount(count(Permissions::ROLE_PERMISSIONS))
        ->and(Role::count())->toBe(count(Permissions::ROLE_PERMISSIONS) * 2);
});

test('it does not seed a super_admin role since that is a platform-wide user flag', function () {
    $this->seed(PermissionSeeder::class);

    $company = Company::factory()->create();

    (new SeedDefaultCompanyRoles)->handle($company);

    expect(Role::where('name', 'super_admin')->exists())->toBeFalse();
});
