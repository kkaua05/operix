<div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold">{{ $product->name }}</h1>
                <x-badge :variant="$product->status === 'active' ? 'success' : 'default'">
                    {{ $product->status === 'active' ? 'Ativo' : 'Inativo' }}
                </x-badge>
                @if ($product->isBelowMinimumStock())
                    <x-badge variant="danger">Estoque crítico</x-badge>
                @endif
            </div>
            <p class="text-xs text-op-secondary">
                SKU {{ $product->sku }}
                {{ $product->category ? '· '.$product->category->name : '' }}
                {{ $product->supplier ? '· '.$product->supplier->name : '' }}
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('inventory.products.index') }}" wire:navigate>
                <x-button variant="secondary">Voltar</x-button>
            </a>
            @can('update', $product)
                <a href="{{ route('inventory.products.edit', $product) }}" wire:navigate>
                    <x-button>Editar</x-button>
                </a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Estoque atual</p>
            <p @class(['mt-1 text-2xl font-semibold', 'text-op-danger' => $product->isBelowMinimumStock()])>
                {{ rtrim(rtrim((string) $product->stock_quantity, '0'), '.') }} {{ $product->unit }}
            </p>
            <p class="mt-1 text-xs text-op-secondary">Mínimo: {{ rtrim(rtrim((string) $product->min_stock, '0'), '.') }} {{ $product->unit }}</p>
        </div>

        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Preço de custo</p>
            <p class="mt-1 text-2xl font-semibold">R$ {{ number_format((float) $product->cost_price, 2, ',', '.') }}</p>
        </div>

        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Preço de venda</p>
            <p class="mt-1 text-2xl font-semibold">R$ {{ number_format((float) $product->sale_price, 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-op-border bg-op-card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xs font-semibold tracking-wider text-op-secondary uppercase">Movimentações de estoque</h3>
            @can('update', $product)
                <x-button wire:click="openMovementForm">Registrar movimentação</x-button>
            @endcan
        </div>

        @if ($showMovementForm)
            <div class="mb-4 rounded-lg border border-op-border bg-op-surface p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-label for="movement_type" value="Tipo" />
                        <select wire:model="movement_type" id="movement_type" class="block w-full rounded-lg border border-op-border bg-op-card px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                            <option value="in">Entrada</option>
                            <option value="out">Saída</option>
                            <option value="adjustment">Ajuste (definir valor absoluto)</option>
                        </select>
                        <x-input-error :messages="$errors->get('movement_type')" />
                    </div>

                    <div>
                        <x-label for="quantity" value="Quantidade" />
                        <x-input wire:model="quantity" id="quantity" type="number" step="0.01" min="0" />
                        <x-input-error :messages="$errors->get('quantity')" />
                    </div>

                    <div>
                        <x-label for="notes" value="Observação (opcional)" />
                        <x-input wire:model="notes" id="notes" type="text" />
                        <x-input-error :messages="$errors->get('notes')" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-3">
                    <x-button variant="secondary" wire:click="cancelMovement">Cancelar</x-button>
                    <x-button wire:click="registerMovement">Registrar</x-button>
                </div>
            </div>
        @endif

        @if ($movements->isEmpty())
            <x-empty-state title="Nenhuma movimentação registrada" description="Entradas, saídas e consumos em OS aparecerão aqui." />
        @else
            <div class="overflow-x-auto rounded-lg border border-op-border">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                        <tr>
                            <th class="px-4 py-3 font-medium">Data</th>
                            <th class="px-4 py-3 font-medium">Tipo</th>
                            <th class="px-4 py-3 font-medium">Quantidade</th>
                            <th class="px-4 py-3 font-medium">Responsável</th>
                            <th class="px-4 py-3 font-medium">Observação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-op-border">
                        @foreach ($movements as $movement)
                            @php
                                $isPositive = in_array($movement->type, [\App\Enums\InventoryMovementType::In, \App\Enums\InventoryMovementType::Return], true);
                            @endphp
                            <tr wire:key="movement-{{ $movement->id }}">
                                <td class="px-4 py-3 text-op-secondary">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $movement->type->label() }}</td>
                                <td @class(['px-4 py-3 font-medium', 'text-op-success' => $isPositive, 'text-op-danger' => ! $isPositive])>
                                    {{ $isPositive ? '+' : '-' }}{{ rtrim(rtrim((string) $movement->quantity, '0'), '.') }}
                                </td>
                                <td class="px-4 py-3 text-op-secondary">{{ $movement->performedBy?->name ?: '—' }}</td>
                                <td class="px-4 py-3 text-op-secondary">{{ $movement->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
