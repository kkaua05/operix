<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\Technician;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MemberManager extends Component
{
    public Team $team;

    public string $addingTechnicianId = '';

    public function addMember(): void
    {
        $this->authorize('update', $this->team);

        if ($this->addingTechnicianId === '') {
            return;
        }

        $technician = Technician::findOrFail($this->addingTechnicianId);

        if ($technician->company_id !== $this->team->company_id) {
            abort(403);
        }

        $this->team->technicians()->syncWithoutDetaching([$technician->id]);

        $this->addingTechnicianId = '';
    }

    public function removeMember(int $technicianId): void
    {
        $this->authorize('update', $this->team);

        $this->team->technicians()->detach($technicianId);
    }

    public function render(): View
    {
        $members = $this->team->technicians()->orderBy('name')->get();

        $availableTechnicians = Technician::query()
            ->whereNotIn('id', $members->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.teams.member-manager', [
            'members' => $members,
            'availableTechnicians' => $availableTechnicians,
        ]);
    }
}
