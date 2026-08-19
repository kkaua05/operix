<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddressManager extends Component
{
    public Customer $customer;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $type = 'service';

    public string $zip_code = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    public bool $is_primary = false;

    protected function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:service,billing,other'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'is_primary' => ['boolean'],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('update', $this->customer);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $addressId): void
    {
        $this->authorize('update', $this->customer);

        $address = $this->customer->addresses()->findOrFail($addressId);

        $this->editingId = $address->id;
        $this->label = (string) $address->label;
        $this->type = $address->type;
        $this->zip_code = (string) $address->zip_code;
        $this->street = $address->street;
        $this->number = (string) $address->number;
        $this->complement = (string) $address->complement;
        $this->neighborhood = (string) $address->neighborhood;
        $this->city = $address->city;
        $this->state = $address->state;
        $this->is_primary = $address->is_primary;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->customer);

        $validated = $this->validate();

        if ($this->is_primary) {
            $this->customer->addresses()->update(['is_primary' => false]);
        }

        if ($this->editingId) {
            $this->customer->addresses()->whereKey($this->editingId)->update($validated);
        } else {
            $this->customer->addresses()->create($validated);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $addressId): void
    {
        $this->authorize('update', $this->customer);

        $this->customer->addresses()->whereKey($addressId)->delete();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'label', 'zip_code', 'street', 'number',
            'complement', 'neighborhood', 'city', 'state', 'is_primary',
        ]);
        $this->type = 'service';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.customers.address-manager', [
            'addresses' => $this->customer->addresses()->orderByDesc('is_primary')->get(),
        ]);
    }
}
