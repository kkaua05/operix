<?php

use App\Actions\SeedDefaultCompanyRoles;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\CurrentCompany;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Creates a company + a user with the given roles assigned (permissions
 * seeded and default company roles created), and sets CurrentCompany so
 * role/permission lookups resolve correctly outside of an HTTP request.
 * Returns the User; access ->company via $user->company.
 *
 * @param  array<int, string>  $roles
 */
function actingAsCompanyUser(array $roles = [], array $userAttributes = []): User
{
    (new PermissionSeeder)->run();

    $company = Company::factory()->create();
    (new SeedDefaultCompanyRoles)->handle($company);

    CurrentCompany::set($company->id);

    $user = User::factory()->create(array_merge(['company_id' => $company->id], $userAttributes));

    foreach ($roles as $role) {
        $user->assignRole($role);
    }

    test()->actingAs($user);

    return $user;
}

/**
 * Creates a company + a technician + a User account linked to that
 * technician (Technician Portal §26 requires this link) and logs in as
 * that user. Returns the Technician; access ->user for the User account.
 */
function actingAsTechnicianUser(array $technicianAttributes = []): Technician
{
    (new PermissionSeeder)->run();

    $company = Company::factory()->create();
    (new SeedDefaultCompanyRoles)->handle($company);

    CurrentCompany::set($company->id);

    $user = User::factory()->create(['company_id' => $company->id]);
    $user->assignRole('technician');

    $technician = Technician::factory()->create(array_merge([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ], $technicianAttributes));

    test()->actingAs($user);

    return $technician;
}

/**
 * WorkOrderFactory picks an unrelated random company for customer_id via
 * Customer::factory() — fine when the work order's own tenant doesn't
 * matter, but wrong whenever the test later touches $workOrder->customer
 * under a specific CurrentCompany context (the BelongsToCompany scope
 * will hide a customer from a different company, returning null). This
 * always creates a same-company customer first.
 *
 * @param  array<string, mixed>  $attributes
 */
function createWorkOrderForCompany(int $companyId, array $attributes = []): WorkOrder
{
    $customer = Customer::factory()->create(['company_id' => $companyId]);

    return WorkOrder::factory()->create(array_merge([
        'company_id' => $companyId,
        'customer_id' => $customer->id,
    ], $attributes));
}
