<div>
    <div class="mb-6">
        <h1 class="text-lg font-semibold">Central de Despacho</h1>
        <p class="text-xs text-op-secondary">Arraste uma ordem pendente até um técnico para atribuí-la.</p>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Coluna esquerda: Ordens pendentes --}}
        <div>
            <h2 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">
                Ordens pendentes ({{ $pendingWorkOrders->count() }})
            </h2>

            @if ($pendingWorkOrders->isEmpty())
                <x-empty-state title="Nenhuma ordem pendente" description="Todas as ordens abertas já têm um técnico atribuído." />
            @else
                <div class="space-y-2">
                    @foreach ($pendingWorkOrders as $workOrder)
                        <div
                            wire:key="pending-{{ $workOrder->id }}"
                            draggable="true"
                            x-on:dragstart="$event.dataTransfer.setData('text/work-order-id', '{{ $workOrder->id }}')"
                            class="cursor-move rounded-xl border border-op-border bg-op-card p-3 transition hover:border-op-accent"
                        >
                            <div class="flex items-center justify-between">
                                <a href="{{ route('work-orders.show', $workOrder) }}" wire:navigate class="text-sm font-medium hover:text-op-accent-hover">
                                    {{ $workOrder->number }}
                                </a>
                                <x-status-badge :status="$workOrder->status" />
                            </div>
                            <p class="mt-1 text-xs text-op-secondary">{{ $workOrder->customer->name }}</p>
                            @if ($workOrder->category)
                                <p class="text-xs text-op-secondary">{{ $workOrder->category }}</p>
                            @endif

                            <div class="mt-2 flex items-center justify-between">
                                <select
                                    wire:change="changePriority({{ $workOrder->id }}, $event.target.value)"
                                    class="rounded-md border border-op-border bg-op-surface px-2 py-1 text-[11px] text-op-primary focus:border-op-accent focus:outline-none"
                                >
                                    @foreach ($priorities as $p)
                                        <option value="{{ $p->value }}" @selected($workOrder->priority === $p)>{{ $p->label() }}</option>
                                    @endforeach
                                </select>

                                <a href="{{ route('scheduling.create') }}" wire:navigate class="text-[11px] text-op-secondary hover:text-op-primary">
                                    Agendar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Coluna central: Agenda do dia --}}
        <div>
            <h2 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">
                Agenda de hoje ({{ $todayAppointments->count() }})
            </h2>

            @if ($todayAppointments->isEmpty())
                <x-empty-state title="Nenhum agendamento hoje" />
            @else
                <div class="space-y-2">
                    @foreach ($todayAppointments as $appointment)
                        <div wire:key="appt-{{ $appointment->id }}" class="rounded-xl border border-op-border bg-op-card p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">
                                    {{ $appointment->scheduled_start->format('H:i') }}–{{ $appointment->scheduled_end->format('H:i') }}
                                </span>
                                <x-badge variant="info">{{ $appointment->status->label() }}</x-badge>
                            </div>
                            <p class="mt-1 text-xs text-op-secondary">
                                {{ $appointment->workOrder->number }} — {{ $appointment->workOrder->customer->name }}
                            </p>
                            <p class="text-xs text-op-secondary">{{ $appointment->technician?->name ?: 'Sem técnico' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <a href="{{ route('scheduling.index') }}" wire:navigate class="mt-3 block text-center text-xs text-op-secondary hover:text-op-primary">
                Ver agenda completa →
            </a>
        </div>

        {{-- Coluna direita: Técnicos --}}
        <div>
            <h2 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">
                Técnicos ({{ $technicians->count() }})
            </h2>

            @if ($technicians->isEmpty())
                <x-empty-state title="Nenhum técnico cadastrado" />
            @else
                <div class="space-y-2">
                    @foreach ($technicians as $technician)
                        @php
                            $statusVariant = match ($technician->status) {
                                \App\Enums\TechnicianStatus::Available => 'success',
                                \App\Enums\TechnicianStatus::Busy, \App\Enums\TechnicianStatus::InService => 'warning',
                                \App\Enums\TechnicianStatus::EnRoute => 'info',
                                default => 'default',
                            };
                        @endphp
                        <div
                            wire:key="tech-{{ $technician->id }}"
                            x-data="{ over: false }"
                            x-on:dragover.prevent="over = true"
                            x-on:dragleave.prevent="over = false"
                            x-on:drop.prevent="over = false; $wire.assign($event.dataTransfer.getData('text/work-order-id'), {{ $technician->id }})"
                            class="rounded-xl border border-op-border bg-op-card p-3 transition"
                            :class="over ? 'border-op-accent bg-op-surface' : ''"
                        >
                            <div class="flex items-center justify-between">
                                <a href="{{ route('technicians.show', $technician) }}" wire:navigate class="text-sm font-medium hover:text-op-accent-hover">
                                    {{ $technician->name }}
                                </a>
                                <x-badge :variant="$statusVariant">{{ $technician->status->label() }}</x-badge>
                            </div>
                            <p class="mt-1 text-xs text-op-secondary">
                                {{ $technician->region ?: 'Sem região' }} · {{ $technician->work_orders_count }} OS em aberto
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
