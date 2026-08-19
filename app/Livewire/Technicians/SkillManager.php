<?php

namespace App\Livewire\Technicians;

use App\Models\Technician;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SkillManager extends Component
{
    public Technician $technician;

    public bool $showForm = false;

    public string $skill = '';

    public string $proficiency_level = '';

    protected function rules(): array
    {
        return [
            'skill' => [
                'required', 'string', 'max:100',
                Rule::unique('technician_skills', 'skill')->where('technician_id', $this->technician->id),
            ],
            'proficiency_level' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function addNew(): void
    {
        $this->authorize('update', $this->technician);

        $this->reset(['skill', 'proficiency_level']);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->technician);

        $validated = $this->validate();

        $this->technician->skills()->create($validated);

        $this->reset(['skill', 'proficiency_level']);
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function delete(int $skillId): void
    {
        $this->authorize('update', $this->technician);

        $this->technician->skills()->whereKey($skillId)->delete();
    }

    public function cancel(): void
    {
        $this->reset(['skill', 'proficiency_level']);
        $this->resetErrorBag();
        $this->showForm = false;
    }

    public function render(): View
    {
        return view('livewire.technicians.skill-manager', [
            'skills' => $this->technician->skills()->orderBy('skill')->get(),
        ]);
    }
}
