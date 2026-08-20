<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Equipes</h1>
            <p class="text-xs text-op-secondary">Organize os técnicos em equipes por região ou especialidade.</p>
        </div>

        @can('create', \App\Models\Team::class)
            <a href="{{ route('teams.create') }}" wire:navigate>
                <x-button>Nova equipe</x-button>
            </a>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-4">
        <x-input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por nome ou região..." />
    </div>

    @if ($teams->isEmpty())
        <x-empty-state
            title="Nenhuma equipe encontrada"
            description="{{ $search !== '' ? 'Ajuste a busca ou crie uma nova equipe.' : 'Crie sua primeira equipe para começar.' }}"
        >
            <x-slot:action>
                @can('create', \App\Models\Team::class)
                    <a href="{{ route('teams.create') }}" wire:navigate>
                        <x-button>Nova equipe</x-button>
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
                        <th class="px-4 py-3 font-medium">Supervisor</th>
                        <th class="px-4 py-3 font-medium">Região</th>
                        <th class="px-4 py-3 font-medium">Técnicos</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($teams as $team)
                        <tr wire:key="team-{{ $team->id }}" class="hover:bg-op-surface/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('teams.show', $team) }}" wire:navigate class="font-medium text-op-primary hover:text-op-accent-hover">
                                    {{ $team->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-op-secondary">{{ $team->supervisor?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $team->region ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">
                                {{ $team->technicians_count }}{{ $team->capacity ? ' / '.$team->capacity : '' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$team->status === 'active' ? 'success' : 'default'">
                                    {{ $team->status === 'active' ? 'Ativa' : 'Inativa' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    @can('update', $team)
                                        <a href="{{ route('teams.edit', $team) }}" wire:navigate class="text-op-secondary hover:text-op-primary">
                                            Editar
                                        </a>
                                    @endcan

                                    @can('delete', $team)
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $team->id }})"
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
            {{ $teams->links() }}
        </div>
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir equipe?</h2>
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
