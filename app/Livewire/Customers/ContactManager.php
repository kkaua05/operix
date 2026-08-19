<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ContactManager extends Component
{
    public Customer $customer;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $role = '';

    public string $email = '';

    public string $phone = '';

    public bool $is_primary = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_primary' => ['boolean'],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('update', $this->customer);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $contactId): void
    {
        $this->authorize('update', $this->customer);

        $contact = $this->customer->contacts()->findOrFail($contactId);

        $this->editingId = $contact->id;
        $this->name = $contact->name;
        $this->role = (string) $contact->role;
        $this->email = (string) $contact->email;
        $this->phone = (string) $contact->phone;
        $this->is_primary = $contact->is_primary;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->customer);

        $validated = $this->validate();

        if ($this->is_primary) {
            $this->customer->contacts()->update(['is_primary' => false]);
        }

        if ($this->editingId) {
            $this->customer->contacts()->whereKey($this->editingId)->update($validated);
        } else {
            $this->customer->contacts()->create($validated);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $contactId): void
    {
        $this->authorize('update', $this->customer);

        $this->customer->contacts()->whereKey($contactId)->delete();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'role', 'email', 'phone', 'is_primary']);
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.customers.contact-manager', [
            'contacts' => $this->customer->contacts()->orderByDesc('is_primary')->get(),
        ]);
    }
}
