<?php

use App\Livewire\Customers\Index;
use App\Models\Customer;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('customers.index'))->assertRedirect('/login');
});

test('a user with a role granting customers.view can access the list', function () {
    actingAsCompanyUser(['technician']);

    $this->get(route('customers.index'))->assertOk();
});

test('a user with no roles at all is forbidden', function () {
    actingAsCompanyUser([]);

    $this->get(route('customers.index'))->assertForbidden();
});

test('it lists only the current company\'s customers', function () {
    $user = actingAsCompanyUser(['admin']);

    Customer::factory()->count(3)->create(['company_id' => $user->company_id]);

    $otherCompanyCustomer = Customer::factory()->create();

    Livewire::test(Index::class)
        ->assertSee(Customer::where('company_id', $user->company_id)->first()->name)
        ->assertDontSee($otherCompanyCustomer->name);
});

test('it shows an empty state when there are no customers', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Index::class)
        ->assertSee('Nenhum cliente encontrado');
});

test('it searches customers by name', function () {
    $user = actingAsCompanyUser(['admin']);

    $match = Customer::factory()->create(['company_id' => $user->company_id, 'name' => 'Zebra Tecnologia']);
    $other = Customer::factory()->create(['company_id' => $user->company_id, 'name' => 'Acme Corp']);

    Livewire::test(Index::class)
        ->set('search', 'Zebra')
        ->assertSee($match->name)
        ->assertDontSee($other->name);
});

test('a user with customers.delete permission can delete a customer', function () {
    $user = actingAsCompanyUser(['admin']);

    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $customer->id)
        ->call('delete');

    expect(Customer::find($customer->id))->toBeNull();
});

test('a user without customers.delete permission cannot delete a customer', function () {
    $user = actingAsCompanyUser(['technician']);

    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $customer->id)
        ->call('delete')
        ->assertForbidden();

    expect(Customer::find($customer->id))->not->toBeNull();
});
