<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\Technician;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Team $team = null;

    public string $name = '';

    public ?string $region = null;

    public ?int $supervisor_id = null;

    public ?int $capacity = null;

    public string $status = 'active';

    public function mount(?Team $team = null): void
    {
        if ($team?->exists) {
            $this->authorize('update', $team);

            $this->team = $team;
            $this->name = $team->name;
            $this->region = $team->region;
            $this->supervisor_id = $team->supervisor_id;
            $this->capacity = $team->capacity;
            $this->status = $team->status;
        } else {
            $this->authorize('create', Team::class);
        }
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('teams', 'name')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->team?->id),
            ],
            'region' => ['nullable', 'string', 'max:100'],
            'supervisor_id' => [
                'nullable',
                Rule::exists('technicians', 'id')->where('company_id', auth()->user()->company_id),
            ],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->team) {
            $this->team->update($validated);
            session()->flash('status', 'Equipe atualizada com sucesso.');
        } else {
            $this->team = Team::create($validated);
            session()->flash('status', 'Equipe criada com sucesso.');
        }

        $this->redirectRoute('teams.show', $this->team, navigate: true);
    }

    public function render(): View
    {
        $supervisors = Technician::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.teams.form', ['supervisors' => $supervisors]);
    }
}
