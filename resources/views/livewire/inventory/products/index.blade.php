<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Produtos</h1>
            <p class="text-xs text-op-secondary">Gerencie o catálogo de produtos e materiais do estoque.</p>
        </div>

        @can('create', \App\Models\Product::class)
            <a href="{{ route('inventory.products.create') }}" wire:navigate>
                <x-button>Novo produto</x-button>
            </a>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @if ($criticalCount > 0)
        <x-alert variant="danger" class="mb-4">
            {{ $criticalCount }} {{ $criticalCount === 1 ? 'produto está' : 'produtos estão' }} com estoque abaixo do mínimo.
            <button type="button" wire:click="$set('onlyCritical', true)" class="ml-1 font-medium underline">Ver produtos críticos</button>
        </x-alert>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <x-input wire:model.live.debounce.400ms="search" type="text" placeholder="Buscar por nome ou SKU..." />
        </div>

        <select wire:model.live="category" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
            <option value="">Todas as categorias</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>

        <label class="flex items-center gap-2 text-xs text-op-secondary">
            <input type="checkbox" wire:model.live="onlyCritical" class="rounded border-op-border text-op-accent focus:ring-op-accent" />
            Apenas estoque crítico
        </label>
    </div>

    @if ($products->isEmpty())
        <x-empty-state
            title="Nenhum produto encontrado"
            description="{{ $search !== '' || $category !== '' || $onlyCritical ? 'Ajuste os filtros ou cadastre um novo produto.' : 'Cadastre seu primeiro produto para começar.' }}"
        >
            <x-slot:action>
                @can('create', \App\Models\Product::class)
                    <a href="{{ route('inventory.products.create') }}" wire:navigate>
                        <x-button>Novo produto</x-button>
                    </a>
                @endcan
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="overflow-x-auto rounded-xl border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-4 py-3 font-medium">Produto</th>
                        <th class="px-4 py-3 font-medium">SKU</th>
                        <th class="px-4 py-3 font-medium">Categoria</th>
                        <th class="px-4 py-3 font-medium">Estoque</th>
                        <th class="px-4 py-3 font-medium">Preço venda</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="hover:bg-op-surface/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('inventory.products.show', $product) }}" wire:navigate class="font-medium text-op-primary hover:text-op-accent-hover">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-op-secondary">{{ $product->sku }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $product->category?->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span @class(['font-medium', 'text-op-danger' => $product->isBelowMinimumStock()])>
                                    {{ rtrim(rtrim($product->stock_quantity, '0'), '.') }} {{ $product->unit }}
                                </span>
                                @if ($product->isBelowMinimumStock())
                                    <x-badge variant="danger" class="ml-1">Crítico</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-op-secondary">R$ {{ number_format((float) $product->sale_price, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$product->status === 'active' ? 'success' : 'default'">
                                    {{ $product->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    @can('update', $product)
                                        <a href="{{ route('inventory.products.edit', $product) }}" wire:navigate class="text-op-secondary hover:text-op-primary">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('delete', $product)
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $product->id }})"
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
            {{ $products->links() }}
        </div>
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir produto?</h2>
        <p class="mt-1 text-xs text-op-secondary">Esta ação não poderá ser desfeita.</p>

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" x-on:click="deleteModal = false" wire:click="cancelDelete">Cancelar</x-button>
            <x-button variant="primary" class="!bg-op-danger !text-white hover:!bg-op-danger/80" wire:click="delete" x-on:click="deleteModal = false">Excluir</x-button>
        </div>
    </x-modal>
</div>
