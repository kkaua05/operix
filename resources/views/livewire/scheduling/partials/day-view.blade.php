@if ($appointments->isEmpty())
    <x-empty-state
        title="Nenhum agendamento neste dia"
        description="Crie um novo agendamento ou navegue para outro dia."
    >
        <x-slot:action>
            @can('create', \App\Models\Appointment::class)
                <a href="{{ route('scheduling.create') }}" wire:navigate>
                    <x-button>Novo agendamento</x-button>
                </a>
            @endcan
        </x-slot:action>
    </x-empty-state>
@else
    <div class="space-y-2">
        @foreach ($appointments as $appointment)
            <div wire:key="appt-{{ $appointment->id }}" class="flex items-center justify-between rounded-xl border border-op-border bg-op-card p-4">
                <div class="flex items-center gap-4">
                    <div class="w-24 shrink-0 text-sm font-medium">
                        {{ $appointment->scheduled_start->format('H:i') }}–{{ $appointment->scheduled_end->format('H:i') }}
                    </div>

                    <div>
                        <a href="{{ route('work-orders.show', $appointment->work_order_id) }}" wire:navigate class="text-sm font-medium hover:text-op-accent-hover">
                            {{ $appointment->workOrder->number }} — {{ $appointment->workOrder->customer->name }}
                        </a>
                        <p class="text-xs text-op-secondary">
                            {{ $appointment->technician?->name ?: ($appointment->team?->name ?: 'Sem responsável') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-badge :variant="match ($appointment->status) {
                        \App\Enums\AppointmentStatus::Scheduled, \App\Enums\AppointmentStatus::Confirmed => 'info',
                        \App\Enums\AppointmentStatus::InProgress => 'warning',
                        \App\Enums\AppointmentStatus::Completed => 'success',
                        \App\Enums\AppointmentStatus::Cancelled, \App\Enums\AppointmentStatus::NoShow => 'danger',
                    }">{{ $appointment->status->label() }}</x-badge>

                    @can('update', $appointment)
                        <a href="{{ route('scheduling.edit', $appointment) }}" wire:navigate class="text-xs text-op-secondary hover:text-op-primary">
                            Editar
                        </a>
                    @endcan

                    @can('delete', $appointment)
                        <button
                            type="button"
                            wire:click="confirmDelete({{ $appointment->id }})"
                            x-on:click="$dispatch('open-delete-modal')"
                            class="text-xs text-op-secondary hover:text-op-danger"
                        >
                            Excluir
                        </button>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>
@endif
