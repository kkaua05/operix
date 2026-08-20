<div>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold">Itens</h2>

        @can('update', $workOrder)
            <button type="button" wire:click="addNew" class="text-xs text-op-accent hover:text-op-accent-hover">
                + Adicionar item
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-4 space-y-4 rounded-lg border border-op-border bg-op-surface p-4">
            <div>
                <x-label for="description" value="Descrição" />
                <x-input wire:model="description" id="description" type="text" />
                <x-input-error :messages="$errors->get('description')" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="quantity" value="Quantidade" />
                    <x-input wire:model="quantity" id="quantity" type="number" step="0.01" min="0.01" />
                    <x-input-error :messages="$errors->get('quantity')" />
                </div>
                <div>
                    <x-label for="unit_price" value="Preço unitário (R$)" />
                    <x-input wire:model="unit_price" id="unit_price" type="number" step="0.01" min="0" />
                    <x-input-error :messages="$errors->get('unit_price')" />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="secondary" wire:click="cancel">Cancelar</x-button>
                <x-button type="submit">Salvar item</x-button>
            </div>
        </form>
    @endif

    @if ($items->isEmpty() && ! $showForm)
        <x-empty-state title="Nenhum item adicionado" description="Adicione os itens/serviços previstos para esta ordem." />
    @else
        <div class="overflow-x-auto rounded-lg border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-3 py-2 font-medium">Descrição</th>
                        <th class="px-3 py-2 font-medium">Qtd.</th>
                        <th class="px-3 py-2 font-medium">Preço unit.</th>
                        <th class="px-3 py-2 font-medium">Total</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="px-3 py-2">{{ $item->description }}</td>
                            <td class="px-3 py-2 text-op-secondary">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-3 py-2 text-op-secondary">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                            <td class="px-3 py-2">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                @can('update', $workOrder)
                                    <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Remover este item?" class="text-xs text-op-secondary hover:text-op-danger">
                                        Remover
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-op-border bg-op-surface">
                        <td colspan="3" class="px-3 py-2 text-right text-xs text-op-secondary">Total</td>
                        <td class="px-3 py-2 font-medium" colspan="2">R$ {{ number_format($total, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
