<?php

use App\Actions\GenerateWorkOrderNumber;
use App\Models\Company;
use App\Models\Customer;
use App\Models\WorkOrder;

test('it generates the first sequential number for a company', function () {
    $company = Company::factory()->create();

    $number = (new GenerateWorkOrderNumber)->handle($company->id);

    expect($number)->toBe('OS-00001');
});

test('it increments from the last number for that company', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);

    WorkOrder::create([
        'company_id' => $company->id,
        'number' => 'OS-00007',
        'customer_id' => $customer->id,
    ]);

    $number = (new GenerateWorkOrderNumber)->handle($company->id);

    expect($number)->toBe('OS-00008');
});

test('numbering is independent per company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $customerA = Customer::factory()->create(['company_id' => $companyA->id]);

    WorkOrder::create([
        'company_id' => $companyA->id,
        'number' => 'OS-00050',
        'customer_id' => $customerA->id,
    ]);

    expect((new GenerateWorkOrderNumber)->handle($companyA->id))->toBe('OS-00051')
        ->and((new GenerateWorkOrderNumber)->handle($companyB->id))->toBe('OS-00001');
});
