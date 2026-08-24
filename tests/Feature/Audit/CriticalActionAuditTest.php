<?php

use App\Livewire\Customers\Index as CustomerIndex;
use App\Livewire\Financial\Index as FinancialIndex;
use App\Livewire\Settings\Notifications as NotificationSettings;
use App\Livewire\WorkOrders\FinancialManager;
use App\Livewire\WorkOrders\Index as WorkOrderIndex;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use Livewire\Livewire;

test('deleting a customer is audited', function () {
    $admin = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $admin->company_id]);

    Livewire::test(CustomerIndex::class)
        ->call('confirmDelete', $customer->id)
        ->call('delete');

    expect(AuditLog::where('action', 'customer.deleted')->where('auditable_id', $customer->id)->exists())->toBeTrue();
});

test('deleting a work order is audited', function () {
    $admin = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($admin->company_id);

    Livewire::test(WorkOrderIndex::class)
        ->call('confirmDelete', $workOrder->id)
        ->call('delete');

    expect(AuditLog::where('action', 'work_order.deleted')->where('auditable_id', $workOrder->id)->exists())->toBeTrue();
});

test('creating and deleting a standalone financial transaction is audited', function () {
    actingAsCompanyUser(['financial']);

    Livewire::test(FinancialIndex::class)
        ->call('addNew')
        ->set('form_description', 'Lançamento auditado')
        ->set('form_amount', 99)
        ->set('form_occurred_at', now()->toDateString())
        ->call('save');

    $transaction = FinancialTransaction::where('description', 'Lançamento auditado')->firstOrFail();
    expect(AuditLog::where('action', 'financial.transaction_created')->where('auditable_id', $transaction->id)->exists())->toBeTrue();

    Livewire::test(FinancialIndex::class)->call('delete', $transaction->id);

    expect(AuditLog::where('action', 'financial.transaction_deleted')->where('auditable_id', $transaction->id)->exists())->toBeTrue();
});

test('creating a financial transaction on a work order is audited', function () {
    $user = actingAsCompanyUser(['financial']);
    $workOrder = createWorkOrderForCompany($user->company_id);

    Livewire::test(FinancialManager::class, ['workOrder' => $workOrder])
        ->set('description', 'Comissão')
        ->set('amount', 40)
        ->set('occurred_at', now()->toDateString())
        ->call('save');

    $transaction = $workOrder->financialTransactions()->firstOrFail();
    expect(AuditLog::where('action', 'financial.transaction_created')->where('auditable_id', $transaction->id)->exists())->toBeTrue();
});

test('updating the webhook url is audited', function () {
    $admin = actingAsCompanyUser(['admin']);

    Livewire::test(NotificationSettings::class)
        ->set('webhook_url', 'https://example.com/hooks/operix')
        ->call('save');

    $log = AuditLog::where('action', 'settings.webhook_updated')->where('auditable_id', $admin->company_id)->first();

    expect($log)->not->toBeNull()
        ->and($log->new_values['webhook_url'])->toBe('https://example.com/hooks/operix');
});
