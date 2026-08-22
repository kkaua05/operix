<?php

namespace App\Livewire\Inventory\Suppliers;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Fornecedores — Operix'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'busca', history: true)]
    public string $search = '';

    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $supplierId): void
    {
        $this->confirmingDeleteId = $supplierId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $supplier = Supplier::findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $supplier);

        $supplier->delete();

        $this->confirmingDeleteId = null;
        $this->resetPage();

        session()->flash('status', 'Fornecedor excluído com sucesso.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('document', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.inventory.suppliers.index', ['suppliers' => $suppliers]);
    }
}
