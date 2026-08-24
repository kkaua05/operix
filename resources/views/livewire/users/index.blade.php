<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Usuários</h1>
            <p class="text-xs text-op-secondary">Gerencie os usuários e papéis de acesso da sua empresa.</p>
        </div>

        @can('create', \App\Models\User::class)
            <a href="{{ route('users.create') }}" wire:navigate>
                <x-button>Novo usuário</x-button>
            </a>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-4">
        <x-input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por nome ou e-mail..." />
    </div>

    @if ($users->isEmpty())
        <x-empty-state title="Nenhum usuário encontrado" description="{{ $search !== '' ? 'Ajuste a busca.' : 'Cadastre o primeiro usuário da equipe.' }}">
            <x-slot:action>
                @can('create', \App\Models\User::class)
                    <a href="{{ route('users.create') }}" wire:navigate>
                        <x-button>Novo usuário</x-button>
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
                        <th class="px-4 py-3 font-medium">E-mail</th>
                        <th class="px-4 py-3 font-medium">Papel</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-op-surface/50">
                            <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $user->getRoleNames()->first() ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$user->status === 'active' ? 'success' : 'default'">
                                    {{ $user->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    @can('update', $user)
                                        <a href="{{ route('users.edit', $user) }}" wire:navigate class="text-op-secondary hover:text-op-primary">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('delete', $user)
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $user->id }})"
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
            {{ $users->links() }}
        </div>
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir usuário?</h2>
        <p class="mt-1 text-xs text-op-secondary">Esta ação não poderá ser desfeita.</p>

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" x-on:click="deleteModal = false" wire:click="cancelDelete">Cancelar</x-button>
            <x-button variant="primary" class="!bg-op-danger !text-white hover:!bg-op-danger/80" wire:click="delete" x-on:click="deleteModal = false">Excluir</x-button>
        </div>
    </x-modal>
</div>
