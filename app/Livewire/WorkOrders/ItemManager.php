<?php

namespace App\Livewire\WorkOrders;

use App\Models\WorkOrder;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ItemManager extends Component
{
    public WorkOrder $workOrder;

    public bool $showForm = false;

    public string $description = '';

    public float $quantity = 1;

    public float $unit_price = 0;

    protected function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('update', $this->workOrder);

        $this->reset(['description', 'quantity', 'unit_price']);
        $this->quantity = 1;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->workOrder);

        $validated = $this->validate();

        $this->workOrder->items()->create([
            ...$validated,
            'total_price' => $validated['quantity'] * $validated['unit_price'],
        ]);

        $this->reset(['description', 'quantity', 'unit_price']);
        $this->quantity = 1;
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function delete(int $itemId): void
    {
        $this->authorize('update', $this->workOrder);

        $this->workOrder->items()->whereKey($itemId)->delete();
    }

    public function cancel(): void
    {
        $this->reset(['description', 'quantity', 'unit_price']);
        $this->quantity = 1;
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function render(): View
    {
        $items = $this->workOrder->items()->orderBy('id')->get();

        return view('livewire.work-orders.item-manager', [
            'items' => $items,
            'total' => $items->sum('total_price'),
        ]);
    }
}
