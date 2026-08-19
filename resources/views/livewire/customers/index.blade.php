<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Clientes</h1>
            <p class="text-xs text-op-secondary">Gerencie a base de clientes da sua empresa.</p>
        </div>

        @can('create', \App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" wire:navigate>
                <x-button>Novo cliente</x-button>
            </a>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <x-input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por nome, documento ou e-mail..." />
        </div>

        <select wire:model.live="status" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
            <option value="">Todos os status</option>
            <option value="active">Ativo</option>
            <option value="inactive">Inativo</option>
        </select>
    </div>

    @if ($customers->isEmpty())
        <x-empty-state
            title="Nenhum cliente encontrado"
            description="{{ $search !== '' || $status !== '' ? 'Ajuste os filtros ou crie um novo cliente.' : 'Crie seu primeiro cliente para começar.' }}"
        >
            <x-slot:action>
                @can('create', \App\Models\Customer::class)
                    <a href="{{ route('customers.create') }}" wire:navigate>
                        <x-button>Novo cliente</x-button>
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
                        <th class="px-4 py-3 font-medium">Documento</th>
                        <th class="px-4 py-3 font-medium">Contato</th>
                        <th class="px-4 py-3 font-medium">OS</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-op-surface/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('customers.show', $customer) }}" wire:navigate class="font-medium text-op-primary hover:text-op-accent-hover">
                                    {{ $customer->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-op-secondary">{{ $customer->document ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $customer->email ?: $customer->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $customer->work_orders_count }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$customer->status === 'active' ? 'success' : 'default'">
                                    {{ $customer->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    @can('update', $customer)
                                        <a href="{{ route('customers.edit', $customer) }}" wire:navigate class="text-op-secondary hover:text-op-primary">
                                            Editar
                                        </a>
                                    @endcan

                                    @can('delete', $customer)
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $customer->id }})"
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
            {{ $customers->links() }}
        </div>
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir cliente?</h2>
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
