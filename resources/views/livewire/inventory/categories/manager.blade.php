<div x-data="{ deleteModal: false }" x-on:open-delete-modal.window="deleteModal = true" x-on:close-delete-modal.window="deleteModal = false">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Categorias de produtos</h1>
            <p class="text-xs text-op-secondary">Organize os produtos do estoque em categorias e subcategorias.</p>
        </div>

        @can('create', \App\Models\ProductCategory::class)
            <x-button wire:click="addNew">Nova categoria</x-button>
        @endcan
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-op-border bg-op-card p-5">
            <h3 class="mb-4 text-xs font-semibold tracking-wider text-op-secondary uppercase">
                {{ $editing ? 'Editar categoria' : 'Nova categoria' }}
            </h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs text-op-secondary">Nome</label>
                    <x-input wire:model="name" type="text" />
                    @error('name') <p class="mt-1 text-xs text-op-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs text-op-secondary">Categoria pai (opcional)</label>
                    <select wire:model="parent_id" class="w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                        <option value="">Nenhuma</option>
                        @foreach ($categories as $category)
                            @if (! $editing || $category->id !== $editing->id)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('parent_id') <p class="mt-1 text-xs text-op-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <x-button variant="secondary" wire:click="cancel">Cancelar</x-button>
                <x-button wire:click="save">Salvar</x-button>
            </div>
        </div>
    @endif

    @if ($categories->isEmpty())
        <x-empty-state title="Nenhuma categoria cadastrada" description="Crie categorias para organizar os produtos do estoque.">
            <x-slot:action>
                @can('create', \App\Models\ProductCategory::class)
                    <x-button wire:click="addNew">Nova categoria</x-button>
                @endcan
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="overflow-x-auto rounded-xl border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nome</th>
                        <th class="px-4 py-3 font-medium">Categoria pai</th>
                        <th class="px-4 py-3 font-medium">Produtos</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($categories as $category)
                        <tr wire:key="category-{{ $category->id }}" class="hover:bg-op-surface/50">
                            <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $category->parent?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $category->products_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    @can('update', $category)
                                        <button type="button" wire:click="edit({{ $category->id }})" class="text-op-secondary hover:text-op-primary">Editar</button>
                                    @endcan
                                    @can('delete', $category)
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $category->id }})"
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
    @endif

    <x-modal show="deleteModal">
        <h2 class="text-sm font-semibold">Excluir categoria?</h2>
        <p class="mt-1 text-xs text-op-secondary">Produtos vinculados ficarão sem categoria. Esta ação não poderá ser desfeita.</p>

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" x-on:click="deleteModal = false" wire:click="cancelDelete">Cancelar</x-button>
            <x-button variant="primary" class="!bg-op-danger !text-white hover:!bg-op-danger/80" wire:click="delete" x-on:click="deleteModal = false">Excluir</x-button>
        </div>
    </x-modal>
</div>
