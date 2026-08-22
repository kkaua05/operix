<?php

namespace App\Livewire\Inventory\Suppliers;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Supplier $supplier = null;

    public string $name = '';

    public ?string $document = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $notes = null;

    public string $status = 'active';

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier?->exists) {
            $this->authorize('update', $supplier);

            $this->supplier = $supplier;
            $this->name = $supplier->name;
            $this->document = $supplier->document;
            $this->email = $supplier->email;
            $this->phone = $supplier->phone;
            $this->notes = $supplier->notes;
            $this->status = $supplier->status;
        } else {
            $this->authorize('create', Supplier::class);
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => [
                'nullable', 'string', 'max:30',
                Rule::unique('suppliers', 'document')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->supplier?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->supplier) {
            $this->supplier->update($validated);
            session()->flash('status', 'Fornecedor atualizado com sucesso.');
        } else {
            $this->supplier = Supplier::create($validated);
            session()->flash('status', 'Fornecedor cadastrado com sucesso.');
        }

        $this->redirectRoute('inventory.suppliers.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.inventory.suppliers.form');
    }
}
