<?php

namespace App\Livewire\Technicians;

use App\Enums\TechnicianStatus;
use App\Models\Technician;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Técnicos — Operix'])]
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

    public function confirmDelete(int $technicianId): void
    {
        $this->confirmingDeleteId = $technicianId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $technician = Technician::findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $technician);

        $technician->delete();

        $this->confirmingDeleteId = null;
        $this->resetPage();

        session()->flash('status', 'Técnico excluído com sucesso.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Technician::class);

        $technicians = Technician::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('registration_number', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->withCount('workOrders')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.technicians.index', [
            'technicians' => $technicians,
            'statuses' => TechnicianStatus::cases(),
        ]);
    }
}
