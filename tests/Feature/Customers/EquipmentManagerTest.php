<?php

use App\Livewire\Customers\EquipmentManager;
use App\Models\Customer;
use Livewire\Livewire;

test('a user with equipment.manage permission can add equipment to a customer', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(EquipmentManager::class, ['customer' => $customer])
        ->call('addNew')
        ->set('type', 'Roteador')
        ->set('serial_number', 'SN-001')
        ->set('installed_at', '2026-01-15')
        ->call('save')
        ->assertHasNoErrors();

    $equipment = $customer->equipment()->first();

    expect($equipment)->not->toBeNull()
        ->and($equipment->type)->toBe('Roteador')
        ->and($equipment->company_id)->toBe($user->company_id)
        ->and($equipment->installed_at->format('Y-m-d'))->toBe('2026-01-15');
});

test('type is required for equipment', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(EquipmentManager::class, ['customer' => $customer])
        ->call('addNew')
        ->set('type', '')
        ->call('save')
        ->assertHasErrors(['type' => 'required']);
});

test('a user without equipment.manage permission cannot add equipment', function () {
    $user = actingAsCompanyUser(['support']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(EquipmentManager::class, ['customer' => $customer])
        ->call('addNew')
        ->assertForbidden();
});

test('a user with equipment.view but not manage cannot add equipment', function () {
    $user = actingAsCompanyUser(['technician']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(EquipmentManager::class, ['customer' => $customer])
        ->call('addNew')
        ->assertForbidden();
});

test('a user can delete equipment', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $equipment = $customer->equipment()->create(['company_id' => $user->company_id, 'type' => 'Modem']);

    Livewire::test(EquipmentManager::class, ['customer' => $customer])
        ->call('delete', $equipment->id);

    expect($customer->equipment()->count())->toBe(0);
});
