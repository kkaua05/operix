<?php

namespace App\Livewire\WorkOrders;

use App\Enums\FinancialTransactionType;
use App\Models\FinancialTransaction;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\FinancialService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * The "Financeiro" tab on a work order (§35): shows the computed margin
 * (billed items minus consumed materials, plus any manual entries) and
 * lets a financial.manage user register ad-hoc revenue/cost entries tied
 * to this OS (e.g. "comissão do técnico", "taxa de deslocamento").
 */
class FinancialManager extends Component
{
    public WorkOrder $workOrder;

    public bool $showForm = false;

    public string $type = 'cost';

    public ?string $category = null;

    public string $description = '';

    public float $amount = 0;

    public string $occurred_at = '';

    public function mount(): void
    {
        $this->authorize('viewAny', FinancialTransaction::class);
        $this->occurred_at = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_column(FinancialTransactionType::cases(), 'value'))],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_at' => ['required', 'date'],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('create', FinancialTransaction::class);

        $this->reset(['category', 'description', 'amount']);
        $this->type = 'cost';
        $this->occurred_at = now()->toDateString();
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(AuditService $auditService): void
    {
        $this->authorize('create', FinancialTransaction::class);

        $validated = $this->validate();

        $transaction = $this->workOrder->financialTransactions()->create([
            ...$validated,
            'customer_id' => $this->workOrder->customer_id,
            'created_by' => auth()->id(),
        ]);

        $auditService->log('financial.transaction_created', $transaction, null, [
            'work_order' => $this->workOrder->number,
            'type' => $transaction->type->value,
            'amount' => (float) $transaction->amount,
        ], auth()->user());

        $this->reset(['category', 'description', 'amount']);
        $this->type = 'cost';
        $this->occurred_at = now()->toDateString();
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function delete(int $transactionId, AuditService $auditService): void
    {
        $transaction = $this->workOrder->financialTransactions()->findOrFail($transactionId);

        $this->authorize('delete', $transaction);

        $auditService->log('financial.transaction_deleted', $transaction, [
            'work_order' => $this->workOrder->number,
            'type' => $transaction->type->value,
            'amount' => (float) $transaction->amount,
        ], null, auth()->user());

        $transaction->delete();
    }

    public function cancel(): void
    {
        $this->reset(['category', 'description', 'amount']);
        $this->type = 'cost';
        $this->occurred_at = now()->toDateString();
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function render(FinancialService $financialService): View
    {
        return view('livewire.work-orders.financial-manager', [
            'summary' => $financialService->summaryForWorkOrder($this->workOrder),
            'transactions' => $this->workOrder->financialTransactions()->with('createdBy')->latest('occurred_at')->get(),
        ]);
    }
}
