<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Clientes — Operix'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'busca', history: true)]
    public string $search = '';

    #[Url(as: 'status')]
    public string $status = '';

    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $customerId): void
    {
        $this->confirmingDeleteId = $customerId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $customer = Customer::findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $customer);

        $customer->delete();

        $this->confirmingDeleteId = null;
        $this->resetPage();

        session()->flash('status', 'Cliente excluído com sucesso.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('document', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->withCount('workOrders')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.customers.index', ['customers' => $customers]);
    }
}
