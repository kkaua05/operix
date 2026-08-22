<?php

namespace App\Livewire\WorkOrders;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Manages the stock-linked materials consumed on a work order (§34): every
 * add/remove goes through StockService so the product's running stock
 * balance and its inventory_movements audit trail stay in sync with what
 * was actually used on this OS.
 */
class MaterialManager extends Component
{
    public WorkOrder $workOrder;

    public bool $showForm = false;

    public ?int $product_id = null;

    public float $quantity = 1;

    protected function rules(): array
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('company_id', $this->workOrder->company_id),
            ],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('update', $this->workOrder);

        $this->reset(['product_id', 'quantity']);
        $this->quantity = 1;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(StockService $stockService): void
    {
        $this->authorize('update', $this->workOrder);

        $validated = $this->validate();

        $product = Product::findOrFail($validated['product_id']);

        try {
            $stockService->consumeForWorkOrder($this->workOrder, $product, $validated['quantity'], auth()->user());
        } catch (InsufficientStockException $e) {
            $this->addError('quantity', $e->getMessage());

            return;
        }

        $this->reset(['product_id', 'quantity']);
        $this->quantity = 1;
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function delete(int $materialId, StockService $stockService): void
    {
        $this->authorize('update', $this->workOrder);

        $material = $this->workOrder->materials()->whereKey($materialId)->firstOrFail();

        $stockService->returnFromWorkOrder($material, auth()->user());
    }

    public function cancel(): void
    {
        $this->reset(['product_id', 'quantity']);
        $this->quantity = 1;
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function render(): View
    {
        $materials = $this->workOrder->materials()->with('product')->orderBy('id')->get();

        return view('livewire.work-orders.material-manager', [
            'materials' => $materials,
            'total' => $materials->sum('total_cost'),
            'products' => Product::where('company_id', $this->workOrder->company_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
