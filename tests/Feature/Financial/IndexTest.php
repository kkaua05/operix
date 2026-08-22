<?php

use App\Livewire\Financial\Index;
use App\Models\FinancialTransaction;
use Livewire\Livewire;

test('a user without financial.view is forbidden from the financial ledger', function () {
    actingAsCompanyUser(['dispatcher']);

    Livewire::test(Index::class)->assertForbidden();
});

test('it lists only the current company transactions and filters by type', function () {
    $user = actingAsCompanyUser(['financial']);
    FinancialTransaction::factory()->create(['company_id' => $user->company_id, 'type' => 'revenue', 'description' => 'Venda de peça']);
    FinancialTransaction::factory()->create(['company_id' => $user->company_id, 'type' => 'cost', 'description' => 'Combustível']);
    FinancialTransaction::factory()->create(['description' => 'De outra empresa']);

    Livewire::test(Index::class)
        ->assertSee('Venda de peça')
        ->assertSee('Combustível')
        ->assertDontSee('De outra empresa')
        ->set('type', 'revenue')
        ->assertSee('Venda de peça')
        ->assertDontSee('Combustível');
});

test('a user with financial.manage can create a standalone transaction', function () {
    actingAsCompanyUser(['financial']);

    Livewire::test(Index::class)
        ->call('addNew')
        ->set('form_type', 'revenue')
        ->set('form_description', 'Consultoria avulsa')
        ->set('form_amount', 500)
        ->set('form_occurred_at', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(FinancialTransaction::where('description', 'Consultoria avulsa')->exists())->toBeTrue();
});

test('a user with only financial.view cannot create a transaction', function () {
    actingAsCompanyUser(['manager']);

    Livewire::test(Index::class)->assertOk();

    Livewire::test(Index::class)
        ->set('form_description', 'Não permitido')
        ->set('form_amount', 10)
        ->set('form_occurred_at', now()->toDateString())
        ->call('save')
        ->assertForbidden();
});
