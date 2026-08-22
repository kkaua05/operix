<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Fornecedores</h1>
            <p class="text-xs text-op-secondary">Gerencie os fornecedores de produtos do estoque.</p>
        </div>

        @can('create', \App\Models\Supplier::class)
            <a href="{{ route('inventory.suppliers.create') }}" wire:navigate>
                <x-button>Novo fornecedor</x-button>
            </a>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-4">
        <x-input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por nome, documento ou e-mail..." />
    </div>

    @if ($suppliers->isEmpty())
        <x-empty-state
            title="Nenhum fornecedor encontrado"
            description="{{ $search !== '' ? 'Ajuste a busca ou cadastre um novo fornecedor.' : 'Cadastre seu primeiro fornecedor para começar.' }}"
        >
            <x-slot:action>
                @can('create', \App\Models\Supplier::class)
                    <a href="{{ route('inventory.suppliers.create') }}" wire:navigate>
                        <x-button>Novo fornecedor</x-button>
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
                        <th class="px-4 py-3 font-medium">Produtos</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($suppliers as $supplier)
                        <tr wire:key="supplier-{{ $supplier->id }}" class="hover:bg-op-surface/50">
                            <td class="px-4 py-3 font-medium">{{ $supplier->name }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $supplier->document ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $supplier->email ?: $supplier->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $supplier->products_count }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$supplier->status === 'active' ? 'success' : 'default'">
                                    {{ $supplier->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    @can('update', $supplier)
                                        <a href="{{ route('inventory.suppliers.edit', $supplier) }}" wire:navigate class="text-op-secondary hover:text-op-primary">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('delete', $supplier)
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $supplier->id }})"
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
            {{ $suppliers->links() }}
        </div>
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir fornecedor?</h2>
        <p class="mt-1 text-xs text-op-secondary">Esta ação não poderá ser desfeita.</p>

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" x-on:click="deleteModal = false" wire:click="cancelDelete">Cancelar</x-button>
            <x-button variant="primary" class="!bg-op-danger !text-white hover:!bg-op-danger/80" wire:click="delete" x-on:click="deleteModal = false">Excluir</x-button>
        </div>
    </x-modal>
</div>
