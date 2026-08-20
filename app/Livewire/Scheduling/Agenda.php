<?php

namespace App\Livewire\Scheduling;

use App\Models\Appointment;
use App\Models\Technician;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Agenda — Operix'])]
class Agenda extends Component
{
    #[Url(as: 'visao')]
    public string $view = 'day';

    #[Url(as: 'data')]
    public string $date;

    #[Url(as: 'tecnico')]
    public string $technicianId = '';

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        $this->date ??= now()->toDateString();
    }

    public function confirmDelete(int $appointmentId): void
    {
        $this->confirmingDeleteId = $appointmentId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $appointment = Appointment::findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $appointment);

        $appointment->delete();

        $this->confirmingDeleteId = null;

        session()->flash('status', 'Agendamento excluído com sucesso.');
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function goToDay(string $date): void
    {
        $this->date = $date;
        $this->view = 'day';
    }

    public function previous(): void
    {
        $current = Carbon::parse($this->date);

        $this->date = match ($this->view) {
            'day' => $current->subDay()->toDateString(),
            'week' => $current->subWeek()->toDateString(),
            'month' => $current->subMonthNoOverflow()->toDateString(),
            default => $current->toDateString(),
        };
    }

    public function next(): void
    {
        $current = Carbon::parse($this->date);

        $this->date = match ($this->view) {
            'day' => $current->addDay()->toDateString(),
            'week' => $current->addWeek()->toDateString(),
            'month' => $current->addMonthNoOverflow()->toDateString(),
            default => $current->toDateString(),
        };
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
    }

    protected function baseQuery()
    {
        return Appointment::query()
            ->when($this->technicianId !== '', fn ($q) => $q->where('technician_id', $this->technicianId))
            ->with(['technician', 'team', 'workOrder.customer']);
    }

    public function render(): View
    {
        $this->authorize('viewAny', Appointment::class);

        $current = Carbon::parse($this->date);

        $data = match ($this->view) {
            'week' => $this->buildWeekData($current),
            'month' => $this->buildMonthData($current),
            default => $this->buildDayData($current),
        };

        return view('livewire.scheduling.agenda', array_merge($data, [
            'currentDate' => $current,
            'technicians' => Technician::query()->orderBy('name')->get(['id', 'name']),
        ]));
    }

    protected function buildDayData(Carbon $current): array
    {
        $appointments = $this->baseQuery()
            ->whereDate('scheduled_start', $current->toDateString())
            ->orderBy('scheduled_start')
            ->get();

        return ['appointments' => $appointments];
    }

    protected function buildWeekData(Carbon $current): array
    {
        $start = $current->copy()->startOfWeek(Carbon::MONDAY);
        $end = $current->copy()->endOfWeek(Carbon::SUNDAY);

        $appointments = $this->baseQuery()
            ->whereBetween('scheduled_start', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderBy('scheduled_start')
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->scheduled_start->toDateString());

        $days = collect();
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $days->push([
                'date' => $cursor->copy(),
                'appointments' => $appointments->get($cursor->toDateString(), collect()),
            ]);
        }

        return ['weekDays' => $days];
    }

    protected function buildMonthData(Carbon $current): array
    {
        $monthStart = $current->copy()->startOfMonth();
        $monthEnd = $current->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $counts = $this->baseQuery()
            ->whereBetween('scheduled_start', [$gridStart->copy()->startOfDay(), $gridEnd->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->scheduled_start->toDateString())
            ->map->count();

        $weeks = collect();
        $week = collect();

        for ($cursor = $gridStart->copy(); $cursor->lte($gridEnd); $cursor->addDay()) {
            $week->push([
                'date' => $cursor->copy(),
                'inCurrentMonth' => $cursor->month === $current->month,
                'count' => $counts->get($cursor->toDateString(), 0),
            ]);

            if ($week->count() === 7) {
                $weeks->push($week);
                $week = collect();
            }
        }

        return ['monthWeeks' => $weeks];
    }
}
