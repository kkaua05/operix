<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-semibold">Agenda</h1>
            <p class="text-xs text-op-secondary">Visualize e organize os agendamentos de atendimento.</p>
        </div>

        @can('create', \App\Models\Appointment::class)
            <a href="{{ route('scheduling.create') }}" wire:navigate>
                <x-button>Novo agendamento</x-button>
            </a>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <x-button variant="secondary" wire:click="previous">‹</x-button>
            <x-button variant="secondary" wire:click="today">Hoje</x-button>
            <x-button variant="secondary" wire:click="next">›</x-button>

            <span class="ml-2 text-sm font-medium">
                @if ($view === 'day')
                    {{ $currentDate->translatedFormat('d \d\e F \d\e Y') }}
                @elseif ($view === 'week')
                    {{ $currentDate->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->format('d/m') }}
                    –
                    {{ $currentDate->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY)->format('d/m/Y') }}
                @else
                    {{ $currentDate->translatedFormat('F \d\e Y') }}
                @endif
            </span>
        </div>

        <div class="flex items-center gap-3">
            <select wire:model.live="technicianId" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-xs text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                <option value="">Todos os técnicos</option>
                @foreach ($technicians as $technician)
                    <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                @endforeach
            </select>

            <div class="flex rounded-lg border border-op-border p-0.5 text-xs">
                @foreach (['day' => 'Dia', 'week' => 'Semana', 'month' => 'Mês'] as $value => $label)
                    <button
                        type="button"
                        wire:click="setView('{{ $value }}')"
                        @class([
                            'rounded-md px-3 py-1.5 font-medium transition',
                            'bg-op-surface text-op-primary' => $view === $value,
                            'text-op-secondary hover:text-op-primary' => $view !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    @if ($view === 'day')
        @include('livewire.scheduling.partials.day-view')
    @elseif ($view === 'week')
        @include('livewire.scheduling.partials.week-view')
    @else
        @include('livewire.scheduling.partials.month-view')
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir agendamento?</h2>
        <p class="mt-1 text-xs text-op-secondary">Esta ação não poderá ser desfeita.</p>

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" x-on:click="deleteModal = false" wire:click="cancelDelete">
                Cancelar
            </x-button>
            <x-button variant="primary" class="!bg-op-danger !text-white hover:!bg-op-danger/80" wire:click="delete" x-on:click="deleteModal = false">
                Excluir
            </x-button>
        </div>
    </x-modal>
</div>
