<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Equipes — Operix'])]
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

    public function confirmDelete(int $teamId): void
    {
        $this->confirmingDeleteId = $teamId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $team = Team::findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $team);

        $team->delete();

        $this->confirmingDeleteId = null;
        $this->resetPage();

        session()->flash('status', 'Equipe excluída com sucesso.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::query()
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('region', 'like', "%{$this->search}%"))
            ->withCount('technicians')
            ->with('supervisor')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.teams.index', ['teams' => $teams]);
    }
}
