<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Product $product = null;

    public string $name = '';

    public string $sku = '';

    public ?int $product_category_id = null;

    public ?int $supplier_id = null;

    public string $unit = 'un';

    public float $stock_quantity = 0;

    public float $min_stock = 0;

    public ?float $max_stock = null;

    public float $cost_price = 0;

    public float $sale_price = 0;

    public string $status = 'active';

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->authorize('update', $product);

            $this->product = $product;
            $this->name = $product->name;
            $this->sku = $product->sku;
            $this->product_category_id = $product->product_category_id;
            $this->supplier_id = $product->supplier_id;
            $this->unit = $product->unit;
            $this->stock_quantity = (float) $product->stock_quantity;
            $this->min_stock = (float) $product->min_stock;
            $this->max_stock = $product->max_stock !== null ? (float) $product->max_stock : null;
            $this->cost_price = (float) $product->cost_price;
            $this->sale_price = (float) $product->sale_price;
            $this->status = $product->status;
        } else {
            $this->authorize('create', Product::class);
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required', 'string', 'max:100',
                Rule::unique('products', 'sku')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->product?->id),
            ],
            'product_category_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')->where('company_id', auth()->user()->company_id),
            ],
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where('company_id', auth()->user()->company_id),
            ],
            'unit' => ['required', 'string', 'max:10'],
            'stock_quantity' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'gte:min_stock'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->product) {
            // Stock quantity is only ever changed through StockService movements
            // (§32) so a running balance always has a matching audit trail;
            // the edit form cannot silently overwrite it.
            unset($validated['stock_quantity']);
            $this->product->update($validated);
            session()->flash('status', 'Produto atualizado com sucesso.');
        } else {
            $this->product = Product::create($validated);
            session()->flash('status', 'Produto cadastrado com sucesso.');
        }

        $this->redirectRoute('inventory.products.show', $this->product, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.inventory.products.form', [
            'categories' => ProductCategory::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }
}
