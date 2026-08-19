<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Equipment;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EquipmentManager extends Component
{
    public Customer $customer;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $type = '';

    public string $manufacturer = '';

    public string $model = '';

    public string $serial_number = '';

    public string $asset_tag = '';

    public ?string $installed_at = null;

    public ?string $warranty_expires_at = null;

    public string $status = 'active';

    public string $notes = '';

    protected function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'installed_at' => ['nullable', 'date'],
            'warranty_expires_at' => ['nullable', 'date'],
            'status' => [Rule::in(['active', 'inactive', 'removed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('create', Equipment::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $equipmentId): void
    {
        $equipment = $this->customer->equipment()->findOrFail($equipmentId);

        $this->authorize('update', $equipment);

        $this->editingId = $equipment->id;
        $this->type = $equipment->type;
        $this->manufacturer = (string) $equipment->manufacturer;
        $this->model = (string) $equipment->model;
        $this->serial_number = (string) $equipment->serial_number;
        $this->asset_tag = (string) $equipment->asset_tag;
        $this->installed_at = $equipment->installed_at?->format('Y-m-d');
        $this->warranty_expires_at = $equipment->warranty_expires_at?->format('Y-m-d');
        $this->status = $equipment->status;
        $this->notes = (string) $equipment->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $equipment = $this->customer->equipment()->findOrFail($this->editingId);
            $this->authorize('update', $equipment);
            $equipment->update($validated);
        } else {
            $this->authorize('create', Equipment::class);
            $this->customer->equipment()->create($validated);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $equipmentId): void
    {
        $equipment = $this->customer->equipment()->findOrFail($equipmentId);

        $this->authorize('delete', $equipment);

        $equipment->delete();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'type', 'manufacturer', 'model', 'serial_number',
            'asset_tag', 'installed_at', 'warranty_expires_at', 'notes',
        ]);
        $this->status = 'active';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.customers.equipment-manager', [
            'equipmentList' => $this->customer->equipment()->orderByDesc('created_at')->get(),
        ]);
    }
}
