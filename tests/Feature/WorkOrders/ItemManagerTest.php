<?php

use App\Livewire\WorkOrders\ItemManager;
use App\Models\Customer;
use App\Models\WorkOrder;
use Livewire\Livewire;

test('a user can add an item to a work order and the total is computed', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create(['company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    Livewire::test(ItemManager::class, ['workOrder' => $workOrder])
        ->call('addNew')
        ->set('description', 'Instalação de roteador')
        ->set('quantity', 2)
        ->set('unit_price', 150)
        ->call('save')
        ->assertHasNoErrors();

    $item = $workOrder->items()->first();

    expect($item)->not->toBeNull()
        ->and($item->total_price)->toEqual(300);
});

test('description is required', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create(['company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    Livewire::test(ItemManager::class, ['workOrder' => $workOrder])
        ->call('addNew')
        ->set('description', '')
        ->call('save')
        ->assertHasErrors(['description' => 'required']);
});

test('a user without work_orders.update permission cannot add an item', function () {
    $user = actingAsCompanyUser(['support']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create(['company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    Livewire::test(ItemManager::class, ['workOrder' => $workOrder])
        ->call('addNew')
        ->assertForbidden();
});

test('a user can delete an item', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $workOrder = WorkOrder::create(['company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);
    $item = $workOrder->items()->create(['description' => 'Item X', 'quantity' => 1, 'unit_price' => 10, 'total_price' => 10]);

    Livewire::test(ItemManager::class, ['workOrder' => $workOrder])
        ->call('delete', $item->id);

    expect($workOrder->items()->count())->toBe(0);
});
