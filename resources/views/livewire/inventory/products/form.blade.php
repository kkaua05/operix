<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">{{ $product ? 'Editar produto' : 'Novo produto' }}</h1>
        <p class="text-xs text-op-secondary">
            {{ $product ? 'Atualize os dados do produto.' : 'Preencha os dados para cadastrar um novo produto.' }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-op-border bg-op-card p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="name" value="Nome" />
                <x-input wire:model="name" id="name" type="text" autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-label for="sku" value="SKU" />
                <x-input wire:model="sku" id="sku" type="text" />
                <x-input-error :messages="$errors->get('sku')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="product_category_id" value="Categoria" />
                <select wire:model="product_category_id" id="product_category_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="">Nenhuma</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('product_category_id')" />
            </div>

            <div>
                <x-label for="supplier_id" value="Fornecedor" />
                <select wire:model="supplier_id" id="supplier_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="">Nenhum</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('supplier_id')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-label for="unit" value="Unidade" />
                <x-input wire:model="unit" id="unit" type="text" placeholder="un, cx, m..." />
                <x-input-error :messages="$errors->get('unit')" />
            </div>

            <div>
                <x-label for="min_stock" value="Estoque mínimo" />
                <x-input wire:model="min_stock" id="min_stock" type="number" step="0.01" min="0" />
                <x-input-error :messages="$errors->get('min_stock')" />
            </div>

            <div>
                <x-label for="max_stock" value="Estoque máximo (opcional)" />
                <x-input wire:model="max_stock" id="max_stock" type="number" step="0.01" min="0" />
                <x-input-error :messages="$errors->get('max_stock')" />
            </div>
        </div>

        @if (! $product)
            <div>
                <x-label for="stock_quantity" value="Estoque inicial" />
                <x-input wire:model="stock_quantity" id="stock_quantity" type="number" step="0.01" min="0" />
                <x-input-error :messages="$errors->get('stock_quantity')" />
            </div>
        @else
            <x-alert variant="info">
                O estoque atual ({{ rtrim(rtrim((string) $product->stock_quantity, '0'), '.') }} {{ $product->unit }}) só pode ser alterado por movimentações, na página do produto.
            </x-alert>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="cost_price" value="Preço de custo (R$)" />
                <x-input wire:model="cost_price" id="cost_price" type="number" step="0.01" min="0" />
                <x-input-error :messages="$errors->get('cost_price')" />
            </div>

            <div>
                <x-label for="sale_price" value="Preço de venda (R$)" />
                <x-input wire:model="sale_price" id="sale_price" type="number" step="0.01" min="0" />
                <x-input-error :messages="$errors->get('sale_price')" />
            </div>
        </div>

        <div>
            <x-label for="status" value="Status" />
            <select wire:model="status" id="status" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
            </select>
            <x-input-error :messages="$errors->get('status')" />
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-op-border pt-6">
            <a href="{{ $product ? route('inventory.products.show', $product) : route('inventory.products.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">Cancelar</x-button>
            </a>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $product ? 'Salvar alterações' : 'Cadastrar produto' }}</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
