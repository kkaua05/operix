<?php

use App\Livewire\WorkOrders\Form;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Technician;
use App\Models\WorkOrder;
use Livewire\Livewire;

test('a user with work_orders.create permission can create a work order with an auto-generated number', function () {
    $user = actingAsCompanyUser(['support']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->set('description', 'Cliente sem sinal de internet')
        ->set('priority', 'high')
        ->call('save')
        ->assertHasNoErrors();

    $workOrder = WorkOrder::where('customer_id', $customer->id)->first();

    expect($workOrder)->not->toBeNull()
        ->and($workOrder->number)->toBe('OS-00001')
        ->and($workOrder->company_id)->toBe($user->company_id)
        ->and($workOrder->status->value)->toBe('new')
        ->and($workOrder->statusHistory()->count())->toBe(1);
});

test('customer_id is required', function () {
    actingAsCompanyUser(['support']);

    Livewire::test(Form::class)
        ->set('customer_id', '')
        ->call('save')
        ->assertHasErrors(['customer_id' => 'required']);
});

test('customer_id must belong to the same company', function () {
    actingAsCompanyUser(['support']);

    $foreignCustomer = Customer::factory()->create();

    Livewire::test(Form::class)
        ->set('customer_id', $foreignCustomer->id)
        ->call('save')
        ->assertHasErrors(['customer_id']);
});

test('the address and equipment options are scoped to the selected customer', function () {
    $user = actingAsCompanyUser(['support']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $otherCustomer = Customer::factory()->create(['company_id' => $user->company_id]);

    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'street' => 'Rua do Cliente']);
    CustomerAddress::factory()->create(['customer_id' => $otherCustomer->id, 'street' => 'Rua de Outro Cliente']);

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->assertSee('Rua do Cliente')
        ->assertDontSee('Rua de Outro Cliente');
});

test('changing the customer resets the previously selected address and equipment', function () {
    $user = actingAsCompanyUser(['support']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);
    $otherCustomer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->set('customer_address_id', $address->id)
        ->set('customer_id', $otherCustomer->id)
        ->assertSet('customer_address_id', null);
});

test('technician_id must belong to the same company', function () {
    $user = actingAsCompanyUser(['support']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $foreignTechnician = Technician::factory()->create();

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->set('technician_id', $foreignTechnician->id)
        ->call('save')
        ->assertHasErrors(['technician_id']);
});

test('a user without work_orders.create permission cannot access the create form', function () {
    actingAsCompanyUser(['financial']);

    Livewire::test(Form::class)->assertForbidden();
});

test('a user can edit a work order', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create([
        'company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'description' => 'Descrição antiga',
    ]);

    Livewire::test(Form::class, ['workOrder' => $workOrder])
        ->set('description', 'Descrição nova')
        ->call('save')
        ->assertHasNoErrors();

    expect($workOrder->fresh()->description)->toBe('Descrição nova');
});

test('a user cannot edit a work order from another company', function () {
    actingAsCompanyUser(['admin']);

    $foreignOrder = WorkOrder::factory()->create();
    $foreignOrder = WorkOrder::withoutCompanyScope()->find($foreignOrder->id);

    Livewire::test(Form::class, ['workOrder' => $foreignOrder])->assertForbidden();
});
