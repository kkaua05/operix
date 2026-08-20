<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Ordens de Serviço</h1>
            <p class="text-xs text-op-secondary">Acompanhe e gerencie todas as ordens de serviço da sua empresa.</p>
        </div>

        @can('create', \App\Models\WorkOrder::class)
            <a href="{{ route('work-orders.create') }}" wire:navigate>
                <x-button>Nova ordem</x-button>
            </a>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <x-input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por número, cliente ou descrição..." />
        </div>

        <select wire:model.live="status" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
            <option value="">Todos os status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="priority" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
            <option value="">Todas as prioridades</option>
            @foreach ($priorities as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </select>
    </div>

    @if ($workOrders->isEmpty())
        <x-empty-state
            title="Nenhuma ordem de serviço encontrada"
            description="{{ $search !== '' || $status !== '' || $priority !== '' ? 'Ajuste os filtros ou crie uma nova ordem.' : 'Crie sua primeira ordem de serviço para começar.' }}"
        >
            <x-slot:action>
                @can('create', \App\Models\WorkOrder::class)
                    <a href="{{ route('work-orders.create') }}" wire:navigate>
                        <x-button>Nova ordem</x-button>
                    </a>
                @endcan
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="overflow-x-auto rounded-xl border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-4 py-3 font-medium">Número</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Técnico</th>
                        <th class="px-4 py-3 font-medium">Prioridade</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Criada em</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($workOrders as $workOrder)
                        <tr wire:key="wo-{{ $workOrder->id }}" class="hover:bg-op-surface/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('work-orders.show', $workOrder) }}" wire:navigate class="font-medium text-op-primary hover:text-op-accent-hover">
                                    {{ $workOrder->number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-op-secondary">{{ $workOrder->customer->name }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $workOrder->technician?->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <x-priority-badge :priority="$workOrder->priority" />
                            </td>
                            <td class="px-4 py-3">
                                <x-status-badge :status="$workOrder->status" />
                            </td>
                            <td class="px-4 py-3 text-op-secondary">{{ $workOrder->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    @can('update', $workOrder)
                                        <a href="{{ route('work-orders.edit', $workOrder) }}" wire:navigate class="text-op-secondary hover:text-op-primary">
                                            Editar
                                        </a>
                                    @endcan

                                    @can('delete', $workOrder)
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $workOrder->id }})"
                                            x-on:click="$dispatch('open-delete-modal')"
                                            class="text-op-secondary hover:text-op-danger"
                                        >
                                            Excluir
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $workOrders->links() }}
        </div>
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir ordem de serviço?</h2>
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
