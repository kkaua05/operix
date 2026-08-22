<?php

namespace App\Livewire\Inventory\Products;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Product $product;

    public bool $showMovementForm = false;

    public string $movement_type = 'in';

    public float $quantity = 1;

    public string $notes = '';

    public function mount(Product $product): void
    {
        $this->authorize('view', $product);

        $this->product = $product;
    }

    protected function rules(): array
    {
        return [
            'movement_type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => ['required', 'numeric', $this->movement_type === 'adjustment' ? 'min:0' : 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function openMovementForm(): void
    {
        $this->authorize('update', $this->product);

        $this->reset(['quantity', 'notes']);
        $this->movement_type = 'in';
        $this->quantity = 1;
        $this->resetErrorBag();
        $this->showMovementForm = true;
    }

    public function registerMovement(StockService $stockService): void
    {
        $this->authorize('update', $this->product);

        $validated = $this->validate();

        try {
            $stockService->registerMovement(
                product: $this->product,
                type: InventoryMovementType::from($validated['movement_type']),
                quantity: (float) $validated['quantity'],
                user: auth()->user(),
                notes: $validated['notes'] !== '' ? $validated['notes'] : null,
            );
        } catch (InsufficientStockException $e) {
            $this->addError('quantity', $e->getMessage());

            return;
        }

        $this->product->refresh();
        $this->reset(['quantity', 'notes']);
        $this->showMovementForm = false;

        session()->flash('status', 'Movimentação registrada com sucesso.');
    }

    public function cancelMovement(): void
    {
        $this->reset(['quantity', 'notes']);
        $this->resetErrorBag();
        $this->showMovementForm = false;
    }

    public function render(): View
    {
        return view('livewire.inventory.products.show', [
            'movements' => $this->product->movements()->with('performedBy')->latest()->paginate(10),
        ]);
    }
}
