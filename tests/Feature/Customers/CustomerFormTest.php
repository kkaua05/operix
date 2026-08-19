<?php

use App\Livewire\Customers\Form;
use App\Models\Customer;
use Livewire\Livewire;

test('a user with customers.create permission can create a customer', function () {
    $user = actingAsCompanyUser(['support']);

    Livewire::test(Form::class)
        ->set('type', 'individual')
        ->set('name', 'João da Silva')
        ->set('document', '123.456.789-00')
        ->set('email', 'joao@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $customer = Customer::where('name', 'João da Silva')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->company_id)->toBe($user->company_id)
        ->and($customer->email)->toBe('joao@example.com');
});

test('name is required to create a customer', function () {
    actingAsCompanyUser(['support']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('document must be unique within the same company', function () {
    $user = actingAsCompanyUser(['support']);

    Customer::factory()->create(['company_id' => $user->company_id, 'document' => '111.111.111-11']);

    Livewire::test(Form::class)
        ->set('name', 'Outro Cliente')
        ->set('document', '111.111.111-11')
        ->call('save')
        ->assertHasErrors(['document' => 'unique']);
});

test('the same document can be used by a customer in a different company', function () {
    $user = actingAsCompanyUser(['support']);

    Customer::factory()->create(['document' => '222.222.222-22']);

    Livewire::test(Form::class)
        ->set('name', 'Cliente Novo')
        ->set('document', '222.222.222-22')
        ->call('save')
        ->assertHasNoErrors();

    expect(Customer::where('company_id', $user->company_id)->where('document', '222.222.222-22')->exists())->toBeTrue();
});

test('a user without customers.create permission cannot access the create form', function () {
    actingAsCompanyUser(['technician']);

    Livewire::test(Form::class)->assertForbidden();
});

test('a user with customers.update permission can edit a customer', function () {
    $user = actingAsCompanyUser(['admin']);

    $customer = Customer::factory()->create(['company_id' => $user->company_id, 'name' => 'Nome Antigo']);

    Livewire::test(Form::class, ['customer' => $customer])
        ->set('name', 'Nome Novo')
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->fresh()->name)->toBe('Nome Novo');
});

test('a user cannot edit a customer from another company', function () {
    actingAsCompanyUser(['admin']);

    $foreignCustomer = Customer::factory()->create();
    $foreignCustomer = Customer::withoutCompanyScope()->find($foreignCustomer->id);

    Livewire::test(Form::class, ['customer' => $foreignCustomer])->assertForbidden();
});
