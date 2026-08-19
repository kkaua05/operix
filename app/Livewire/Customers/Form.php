<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Customer $customer = null;

    public string $type = 'individual';

    public string $name = '';

    public ?string $legal_name = null;

    public ?string $trading_name = null;

    public ?string $document = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $mobile_phone = null;

    public ?string $notes = null;

    public string $status = 'active';

    public function mount(?Customer $customer = null): void
    {
        if ($customer?->exists) {
            $this->authorize('update', $customer);

            $this->customer = $customer;
            $this->type = $customer->type;
            $this->name = $customer->name;
            $this->legal_name = $customer->legal_name;
            $this->trading_name = $customer->trading_name;
            $this->document = $customer->document;
            $this->email = $customer->email;
            $this->phone = $customer->phone;
            $this->mobile_phone = $customer->mobile_phone;
            $this->notes = $customer->notes;
            $this->status = $customer->status;
        } else {
            $this->authorize('create', Customer::class);
        }
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['individual', 'company'])],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'document' => [
                'nullable', 'string', 'max:20',
                Rule::unique('customers', 'document')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->customer?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->customer) {
            $this->customer->update($validated);
            session()->flash('status', 'Cliente atualizado com sucesso.');
        } else {
            $this->customer = Customer::create($validated);
            session()->flash('status', 'Cliente criado com sucesso.');
        }

        $this->redirectRoute('customers.show', $this->customer, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.customers.form');
    }
}
