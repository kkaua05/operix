<?php

use App\Livewire\WorkOrders\FinancialManager;
use Livewire\Livewire;

test('a user without financial.view is forbidden from the work order financial tab', function () {
    $user = actingAsCompanyUser(['dispatcher']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(FinancialManager::class, ['workOrder' => $workOrder])->assertForbidden();
});

test('a user with financial.manage can register a manual entry tied to the work order', function () {
    $user = actingAsCompanyUser(['financial']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(FinancialManager::class, ['workOrder' => $workOrder])
        ->set('type', 'cost')
        ->set('description', 'Pedágio')
        ->set('amount', 25)
        ->set('occurred_at', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $transaction = $workOrder->financialTransactions()->firstOrFail();
    expect($transaction->description)->toBe('Pedágio')
        ->and((float) $transaction->amount)->toBe(25.0)
        ->and($transaction->customer_id)->toBe($workOrder->customer_id);
});

test('a user with only financial.view cannot register a manual entry', function () {
    $user = actingAsCompanyUser(['manager']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(FinancialManager::class, ['workOrder' => $workOrder])
        ->set('description', 'Teste')
        ->set('amount', 10)
        ->set('occurred_at', now()->toDateString())
        ->call('save')
        ->assertForbidden();

    expect($workOrder->financialTransactions()->count())->toBe(0);
});

test('deleting a manual entry removes it', function () {
    $user = actingAsCompanyUser(['financial']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    $component = Livewire::test(FinancialManager::class, ['workOrder' => $workOrder])
        ->set('description', 'Comissão')
        ->set('amount', 60)
        ->set('occurred_at', now()->toDateString())
        ->call('save');

    $transaction = $workOrder->financialTransactions()->firstOrFail();

    $component->call('delete', $transaction->id);

    expect($workOrder->financialTransactions()->count())->toBe(0);
});
