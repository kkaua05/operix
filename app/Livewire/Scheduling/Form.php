<?php

namespace App\Livewire\Scheduling;

use App\Enums\AppointmentStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Team;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Services\AppointmentConflictChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?Appointment $appointment = null;

    public ?int $work_order_id = null;

    public ?int $technician_id = null;

    public ?int $team_id = null;

    public string $scheduled_start = '';

    public string $scheduled_end = '';

    public string $status = 'scheduled';

    public string $notes = '';

    public function mount(?Appointment $appointment = null): void
    {
        if ($appointment?->exists) {
            $this->authorize('update', $appointment);

            $this->appointment = $appointment;
            $this->work_order_id = $appointment->work_order_id;
            $this->technician_id = $appointment->technician_id;
            $this->team_id = $appointment->team_id;
            $this->scheduled_start = $appointment->scheduled_start->format('Y-m-d\TH:i');
            $this->scheduled_end = $appointment->scheduled_end->format('Y-m-d\TH:i');
            $this->status = $appointment->status->value;
            $this->notes = (string) $appointment->notes;
        } else {
            $this->authorize('create', Appointment::class);
        }
    }

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'work_order_id' => ['required', Rule::exists('work_orders', 'id')->where('company_id', $companyId)],
            'technician_id' => ['nullable', Rule::exists('technicians', 'id')->where('company_id', $companyId)],
            'team_id' => ['nullable', Rule::exists('teams', 'id')->where('company_id', $companyId)],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after:scheduled_start'],
            'status' => ['required', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(AppointmentConflictChecker $conflictChecker): void
    {
        $validated = $this->validate();

        if (! $this->technician_id && ! $this->team_id) {
            $this->addError('technician_id', 'Selecione um técnico ou uma equipe responsável.');

            return;
        }

        $companyId = auth()->user()->company_id;
        $start = Carbon::parse($this->scheduled_start);
        $end = Carbon::parse($this->scheduled_end);

        $conflicts = $conflictChecker->conflictingAppointments(
            $companyId,
            $this->technician_id,
            $this->team_id,
            $start,
            $end,
            $this->appointment?->id,
        );

        if ($conflicts->isNotEmpty()) {
            $conflict = $conflicts->first();
            $responsibleName = $conflict->technician_id ? $conflict->technician->name : $conflict->team->name;

            $this->addError('scheduled_start', 'Conflito de agenda: '.$responsibleName.
                ' já está agendado de '.$conflict->scheduled_start->format('d/m H:i').
                ' até '.$conflict->scheduled_end->format('d/m H:i').'.');

            return;
        }

        if ($this->appointment) {
            $this->appointment->update($validated);
            session()->flash('status', 'Agendamento atualizado com sucesso.');
        } else {
            $validated['company_id'] = $companyId;
            $this->appointment = Appointment::create($validated);
            session()->flash('status', 'Agendamento criado com sucesso.');
        }

        $this->redirectRoute('scheduling.index', navigate: true);
    }

    public function render(): View
    {
        $companyId = auth()->user()->company_id;

        return view('livewire.scheduling.form', [
            'workOrders' => WorkOrder::query()
                ->whereNotIn('status', [WorkOrderStatus::Completed->value, WorkOrderStatus::Cancelled->value])
                ->with('customer')
                ->orderByDesc('created_at')
                ->get(),
            'technicians' => Technician::query()->orderBy('name')->get(['id', 'name']),
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => AppointmentStatus::cases(),
        ]);
    }
}
