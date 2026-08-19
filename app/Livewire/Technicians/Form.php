<?php

namespace App\Livewire\Technicians;

use App\Enums\TechnicianStatus;
use App\Models\Technician;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Technician $technician = null;

    public string $name = '';

    public ?string $document = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $registration_number = null;

    public ?string $region = null;

    public ?int $supervisor_id = null;

    public string $status = 'offline';

    public int $daily_capacity = 8;

    public function mount(?Technician $technician = null): void
    {
        if ($technician?->exists) {
            $this->authorize('update', $technician);

            $this->technician = $technician;
            $this->name = $technician->name;
            $this->document = $technician->document;
            $this->phone = $technician->phone;
            $this->email = $technician->email;
            $this->registration_number = $technician->registration_number;
            $this->region = $technician->region;
            $this->supervisor_id = $technician->supervisor_id;
            $this->status = $technician->status->value;
            $this->daily_capacity = $technician->daily_capacity;
        } else {
            $this->authorize('create', Technician::class);
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'registration_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('technicians', 'registration_number')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($this->technician?->id),
            ],
            'region' => ['nullable', 'string', 'max:100'],
            'supervisor_id' => [
                'nullable',
                Rule::exists('technicians', 'id')->where('company_id', auth()->user()->company_id),
                function ($attribute, $value, $fail) {
                    if ($this->technician && $value == $this->technician->id) {
                        $fail('Um técnico não pode ser supervisor de si mesmo.');
                    }
                },
            ],
            'status' => ['required', Rule::in(array_column(TechnicianStatus::cases(), 'value'))],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:24'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->technician) {
            $this->technician->update($validated);
            session()->flash('status', 'Técnico atualizado com sucesso.');
        } else {
            $this->technician = Technician::create($validated);
            session()->flash('status', 'Técnico cadastrado com sucesso.');
        }

        $this->redirectRoute('technicians.show', $this->technician, navigate: true);
    }

    public function render(): View
    {
        $supervisors = Technician::query()
            ->when($this->technician, fn ($query) => $query->whereKeyNot($this->technician->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.technicians.form', ['supervisors' => $supervisors, 'statuses' => TechnicianStatus::cases()]);
    }
}
