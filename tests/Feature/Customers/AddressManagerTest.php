<?php

use App\Livewire\Customers\AddressManager;
use App\Models\Customer;
use Livewire\Livewire;

test('a user can add an address to a customer', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(AddressManager::class, ['customer' => $customer])
        ->call('addNew')
        ->set('street', 'Rua das Flores')
        ->set('city', 'São Paulo')
        ->set('state', 'SP')
        ->set('is_primary', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->addresses()->count())->toBe(1)
        ->and($customer->addresses()->first()->is_primary)->toBeTrue();
});

test('marking a new address as primary unsets the previous primary address', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $customer->addresses()->create([
        'street' => 'Antigo', 'city' => 'SP', 'state' => 'SP', 'is_primary' => true,
    ]);

    Livewire::test(AddressManager::class, ['customer' => $customer])
        ->call('addNew')
        ->set('street', 'Novo')
        ->set('city', 'SP')
        ->set('state', 'SP')
        ->set('is_primary', true)
        ->call('save');

    expect($customer->addresses()->where('is_primary', true)->count())->toBe(1)
        ->and($customer->addresses()->where('street', 'Novo')->first()->is_primary)->toBeTrue();
});

test('street, city and state are required', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(AddressManager::class, ['customer' => $customer])
        ->call('addNew')
        ->set('street', '')
        ->set('city', '')
        ->set('state', '')
        ->call('save')
        ->assertHasErrors(['street', 'city', 'state']);
});

test('a user without customers.update permission cannot add an address', function () {
    $user = actingAsCompanyUser(['technician']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(AddressManager::class, ['customer' => $customer])
        ->call('addNew')
        ->assertForbidden();
});

test('a user can delete an address', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $address = $customer->addresses()->create(['street' => 'Rua X', 'city' => 'SP', 'state' => 'SP']);

    Livewire::test(AddressManager::class, ['customer' => $customer])
        ->call('delete', $address->id);

    expect($customer->addresses()->count())->toBe(0);
});
