<?php

use App\Livewire\Inventory\Suppliers\Form;
use App\Livewire\Inventory\Suppliers\Index;
use App\Models\Supplier;
use Livewire\Livewire;

test('a user without inventory.view is forbidden from the supplier list', function () {
    actingAsCompanyUser(['dispatcher']);

    Livewire::test(Index::class)->assertForbidden();
});

test('it lists only the current company suppliers and filters by search', function () {
    $user = actingAsCompanyUser(['manager']);
    $mine = Supplier::factory()->create(['company_id' => $user->company_id, 'name' => 'Fornecedor Alfa']);
    $other = Supplier::factory()->create(['name' => 'Fornecedor Beta']);

    Livewire::test(Index::class)
        ->assertSee('Fornecedor Alfa')
        ->assertDontSee('Fornecedor Beta');
});

test('a user with inventory.manage can create and edit a supplier', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Form::class)
        ->set('name', 'Nova Distribuidora Ltda')
        ->set('email', 'contato@novadist.com')
        ->call('save')
        ->assertHasNoErrors();

    $supplier = Supplier::where('name', 'Nova Distribuidora Ltda')->firstOrFail();

    Livewire::test(Form::class, ['supplier' => $supplier])
        ->set('status', 'inactive')
        ->call('save')
        ->assertHasNoErrors();

    expect($supplier->fresh()->status)->toBe('inactive');
});

test('a user without inventory.manage cannot create a supplier', function () {
    actingAsCompanyUser(['manager']);

    Livewire::test(Form::class)->assertForbidden();
});
