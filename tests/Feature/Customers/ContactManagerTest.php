<?php

use App\Livewire\Customers\ContactManager;
use App\Models\Customer;
use Livewire\Livewire;

test('a user can add a contact to a customer', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(ContactManager::class, ['customer' => $customer])
        ->call('addNew')
        ->set('name', 'Maria Souza')
        ->set('email', 'maria@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->contacts()->count())->toBe(1)
        ->and($customer->contacts()->first()->name)->toBe('Maria Souza');
});

test('name is required for a contact', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(ContactManager::class, ['customer' => $customer])
        ->call('addNew')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('a user can edit an existing contact', function () {
    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $contact = $customer->contacts()->create(['name' => 'Nome Antigo']);

    Livewire::test(ContactManager::class, ['customer' => $customer])
        ->call('edit', $contact->id)
        ->set('name', 'Nome Novo')
        ->call('save');

    expect($contact->fresh()->name)->toBe('Nome Novo');
});

test('a user without customers.update permission cannot manage contacts', function () {
    $user = actingAsCompanyUser(['technician']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(ContactManager::class, ['customer' => $customer])
        ->call('addNew')
        ->assertForbidden();
});
