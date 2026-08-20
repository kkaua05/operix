<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Team $team;

    #[Url(as: 'aba')]
    public string $activeTab = 'visao-geral';

    public function mount(Team $team): void
    {
        $this->authorize('view', $team);

        $this->team = $team;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render(): View
    {
        $this->team->loadCount(['technicians', 'workOrders']);
        $this->team->load('supervisor');

        return view('livewire.teams.show');
    }
}
