<?php

use App\Livewire\Customers\Show;
use App\Models\Customer;
use Livewire\Livewire;

test('a user can view a customer from their own company', function () {
    $user = actingAsCompanyUser(['admin']);

    $customer = Customer::factory()->create(['company_id' => $user->company_id, 'name' => 'Cliente Teste']);

    Livewire::test(Show::class, ['customer' => $customer])
        ->assertSee('Cliente Teste')
        ->assertOk();
});

test('a user cannot view a customer from another company', function () {
    actingAsCompanyUser(['admin']);

    $foreignCustomer = Customer::factory()->create();
    $foreignCustomer = Customer::withoutCompanyScope()->find($foreignCustomer->id);

    Livewire::test(Show::class, ['customer' => $foreignCustomer])->assertForbidden();
});

test('the show route 404s for a customer from another company via route model binding', function () {
    actingAsCompanyUser(['admin']);

    $foreignCustomer = Customer::factory()->create();

    $this->get(route('customers.show', $foreignCustomer))->assertNotFound();
});

test('tabs switch between customer sections', function () {
    $user = actingAsCompanyUser(['admin']);

    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Show::class, ['customer' => $customer])
        ->assertSet('activeTab', 'resumo')
        ->call('setTab', 'enderecos')
        ->assertSet('activeTab', 'enderecos');
});
