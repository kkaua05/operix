<div>
    <div class="mb-4">
        <h1 class="text-lg font-semibold">Minhas Ordens</h1>
        <p class="text-xs text-op-secondary">{{ $workOrders->count() }} ordens em aberto</p>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @error('transition')
        <x-alert variant="danger" class="mb-4">{{ $message }}</x-alert>
    @enderror

    @if ($workOrders->isEmpty())
        <x-empty-state title="Nenhuma ordem atribuída" description="Quando uma ordem de serviço for atribuída a você, ela aparecerá aqui." />
    @else
        <div class="space-y-3">
            @foreach ($workOrders as $workOrder)
                <div wire:key="my-wo-{{ $workOrder->id }}" class="rounded-xl border border-op-border bg-op-card p-4">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('portal.work-orders.show', $workOrder) }}" wire:navigate class="text-sm font-semibold hover:text-op-accent-hover">
                            {{ $workOrder->number }}
                        </a>
                        <x-priority-badge :priority="$workOrder->priority" />
                    </div>

                    <p class="mt-1 text-sm">{{ $workOrder->customer->name }}</p>
                    <p class="text-xs text-op-secondary">
                        {{ $workOrder->address ? $workOrder->address->street.', '.$workOrder->address->number.' — '.$workOrder->address->city.'/'.$workOrder->address->state : 'Sem endereço definido' }}
                    </p>
                    <p class="text-xs text-op-secondary">
                        {{ $workOrder->scheduled_at?->format('d/m/Y H:i') ?: 'Sem horário definido' }}
                    </p>

                    <div class="mt-2 flex items-center gap-2">
                        <x-status-badge :status="$workOrder->status" />
                        @if ($workOrder->sla_due_at)
                            <x-sla-indicator :status="$slaService->refreshStatus($workOrder)" :percentage="$slaService->percentageElapsed($workOrder)" class="max-w-[140px]" />
                        @endif
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if (in_array($workOrder->status, [\App\Enums\WorkOrderStatus::Scheduled, \App\Enums\WorkOrderStatus::Assigned]))
                            <x-button wire:click="startTravel({{ $workOrder->id }})" class="flex-1">Iniciar deslocamento</x-button>
                        @endif

                        @if ($workOrder->status === \App\Enums\WorkOrderStatus::EnRoute)
                            @if (! $workOrder->arrived_at)
                                <x-button variant="secondary" wire:click="markArrived({{ $workOrder->id }})" class="flex-1">Cheguei</x-button>
                            @endif
                            <x-button wire:click="startService({{ $workOrder->id }})" class="flex-1">Iniciar atendimento</x-button>
                        @endif

                        @if ($workOrder->status === \App\Enums\WorkOrderStatus::InProgress)
                            <x-button variant="secondary" wire:click="pause({{ $workOrder->id }})" class="flex-1">Pausar</x-button>
                            <a href="{{ route('portal.work-orders.show', $workOrder) }}" wire:navigate class="flex-1">
                                <x-button class="w-full">Finalizar</x-button>
                            </a>
                        @endif

                        @if (in_array($workOrder->status, [\App\Enums\WorkOrderStatus::WaitingCustomer, \App\Enums\WorkOrderStatus::WaitingMaterial, \App\Enums\WorkOrderStatus::WaitingApproval]))
                            <x-button wire:click="resume({{ $workOrder->id }})" class="flex-1">Retomar atendimento</x-button>
                        @endif

                        @if ($workOrder->status === \App\Enums\WorkOrderStatus::Resolved)
                            <span class="flex-1 rounded-lg border border-op-border px-3 py-2 text-center text-xs text-op-secondary">
                                Aguardando revisão
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
