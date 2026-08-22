<?php

namespace App\Livewire\Financial;

use App\Enums\FinancialTransactionType;
use App\Models\FinancialTransaction;
use App\Services\FinancialService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Financeiro — Operix'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'tipo')]
    public string $type = '';

    #[Url(as: 'de')]
    public string $from = '';

    #[Url(as: 'ate')]
    public string $to = '';

    public bool $showForm = false;

    public string $form_type = 'cost';

    public ?string $form_category = null;

    public string $form_description = '';

    public float $form_amount = 0;

    public string $form_occurred_at = '';

    public function mount(): void
    {
        $this->authorize('viewAny', FinancialTransaction::class);
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    protected function formRules(): array
    {
        return [
            'form_type' => ['required', Rule::in(array_column(FinancialTransactionType::cases(), 'value'))],
            'form_category' => ['nullable', 'string', 'max:100'],
            'form_description' => ['required', 'string', 'max:255'],
            'form_amount' => ['required', 'numeric', 'min:0.01'],
            'form_occurred_at' => ['required', 'date'],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('create', FinancialTransaction::class);

        $this->reset(['form_category', 'form_description', 'form_amount']);
        $this->form_type = 'cost';
        $this->form_occurred_at = now()->toDateString();
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', FinancialTransaction::class);

        $validated = $this->validate($this->formRules());

        FinancialTransaction::create([
            'type' => $validated['form_type'],
            'category' => $validated['form_category'],
            'description' => $validated['form_description'],
            'amount' => $validated['form_amount'],
            'occurred_at' => $validated['form_occurred_at'],
            'created_by' => auth()->id(),
        ]);

        $this->reset(['form_category', 'form_description', 'form_amount']);
        $this->form_type = 'cost';
        $this->form_occurred_at = now()->toDateString();
        $this->resetErrorBag();
        $this->showForm = false;
        $this->resetPage();
    }

    public function delete(int $transactionId): void
    {
        $transaction = FinancialTransaction::findOrFail($transactionId);

        $this->authorize('delete', $transaction);

        $transaction->delete();
    }

    public function cancel(): void
    {
        $this->reset(['form_category', 'form_description', 'form_amount']);
        $this->form_type = 'cost';
        $this->form_occurred_at = now()->toDateString();
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function render(FinancialService $financialService): View
    {
        $transactions = FinancialTransaction::query()
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->when($this->from !== '', fn ($query) => $query->whereDate('occurred_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('occurred_at', '<=', $this->to))
            ->with(['workOrder', 'customer', 'createdBy'])
            ->orderByDesc('occurred_at')
            ->paginate(20);

        $totals = $financialService->ledgerTotals(
            (int) auth()->user()->company_id,
            $this->from !== '' ? Carbon::parse($this->from) : null,
            $this->to !== '' ? Carbon::parse($this->to) : null,
        );

        return view('livewire.financial.index', [
            'transactions' => $transactions,
            'totals' => $totals,
            'types' => FinancialTransactionType::cases(),
        ]);
    }
}
