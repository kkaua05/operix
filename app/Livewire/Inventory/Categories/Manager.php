<?php

namespace App\Livewire\Inventory\Categories;

use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A single-page manager for product categories (§32): a flat list with an
 * inline create/edit form and one level of parent nesting, mirroring the
 * lightweight "manager" pattern used for technician skills.
 */
#[Layout('components.layouts.app', ['title' => 'Categorias — Operix'])]
class Manager extends Component
{
    public bool $showForm = false;

    public ?ProductCategory $editing = null;

    public string $name = '';

    public ?int $parent_id = null;

    public ?int $confirmingDeleteId = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('product_categories', 'name')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->editing?->id),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')->where('company_id', auth()->user()->company_id),
            ],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('create', ProductCategory::class);

        $this->reset(['editing', 'name', 'parent_id']);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function edit(int $categoryId): void
    {
        $category = ProductCategory::findOrFail($categoryId);
        $this->authorize('update', $category);

        $this->editing = $category;
        $this->name = $category->name;
        $this->parent_id = $category->parent_id;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize($this->editing ? 'update' : 'create', $this->editing ?? ProductCategory::class);

        $validated = $this->validate();

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            ProductCategory::create($validated);
        }

        $this->reset(['editing', 'name', 'parent_id']);
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function confirmDelete(int $categoryId): void
    {
        $this->confirmingDeleteId = $categoryId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $category = ProductCategory::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $category);

        $category->delete();
        $this->confirmingDeleteId = null;

        session()->flash('status', 'Categoria excluída com sucesso.');
    }

    public function cancel(): void
    {
        $this->reset(['editing', 'name', 'parent_id']);
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function render(): View
    {
        $this->authorize('viewAny', ProductCategory::class);

        return view('livewire.inventory.categories.manager', [
            'categories' => ProductCategory::query()
                ->with('parent')
                ->withCount('products')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
