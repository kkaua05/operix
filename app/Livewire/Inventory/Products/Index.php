<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Produtos — Operix'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'busca', history: true)]
    public string $search = '';

    #[Url(as: 'categoria')]
    public string $category = '';

    #[Url(as: 'critico')]
    public bool $onlyCritical = false;

    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingOnlyCritical(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $productId): void
    {
        $this->confirmingDeleteId = $productId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $product = Product::findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $product);

        $product->delete();

        $this->confirmingDeleteId = null;
        $this->resetPage();

        session()->flash('status', 'Produto excluído com sucesso.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            ->when($this->category !== '', fn ($query) => $query->where('product_category_id', $this->category))
            ->when($this->onlyCritical, fn ($query) => $query->whereColumn('stock_quantity', '<', 'min_stock'))
            ->with('category')
            ->orderBy('name')
            ->paginate(15);

        $criticalCount = Product::query()->whereColumn('stock_quantity', '<', 'min_stock')->count();

        return view('livewire.inventory.products.index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(),
            'criticalCount' => $criticalCount,
        ]);
    }
}
