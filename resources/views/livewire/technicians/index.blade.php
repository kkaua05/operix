<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Técnicos</h1>
            <p class="text-xs text-op-secondary">Gerencie a equipe técnica de campo da sua empresa.</p>
        </div>

        @can('create', \App\Models\Technician::class)
            <a href="{{ route('technicians.create') }}" wire:navigate>
                <x-button>Novo técnico</x-button>
            </a>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <x-input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por nome, matrícula ou e-mail..." />
        </div>

        <select wire:model.live="status" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
            <option value="">Todos os status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    @if ($technicians->isEmpty())
        <x-empty-state
            title="Nenhum técnico encontrado"
            description="{{ $search !== '' || $status !== '' ? 'Ajuste os filtros ou cadastre um novo técnico.' : 'Cadastre seu primeiro técnico para começar.' }}"
        >
            <x-slot:action>
                @can('create', \App\Models\Technician::class)
                    <a href="{{ route('technicians.create') }}" wire:navigate>
                        <x-button>Novo técnico</x-button>
                    </a>
                @endcan
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="overflow-x-auto rounded-xl border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nome</th>
                        <th class="px-4 py-3 font-medium">Matrícula</th>
                        <th class="px-4 py-3 font-medium">Região</th>
                        <th class="px-4 py-3 font-medium">OS</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($technicians as $technician)
                        <tr wire:key="technician-{{ $technician->id }}" class="hover:bg-op-surface/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('technicians.show', $technician) }}" wire:navigate class="font-medium text-op-primary hover:text-op-accent-hover">
                                    {{ $technician->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-op-secondary">{{ $technician->registration_number ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $technician->region ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $technician->work_orders_count }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusVariant = match ($technician->status) {
                                        \App\Enums\TechnicianStatus::Available => 'success',
                                        \App\Enums\TechnicianStatus::Busy, \App\Enums\TechnicianStatus::InService => 'warning',
                                        \App\Enums\TechnicianStatus::EnRoute => 'info',
                                        default => 'default',
                                    };
                                @endphp
                                <x-badge :variant="$statusVariant">{{ $technician->status->label() }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    @can('update', $technician)
                                        <a href="{{ route('technicians.edit', $technician) }}" wire:navigate class="text-op-secondary hover:text-op-primary">
                                            Editar
                                        </a>
                                    @endcan

                                    @can('delete', $technician)
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $technician->id }})"
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
            {{ $technicians->links() }}
        </div>
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir técnico?</h2>
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
