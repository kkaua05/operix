<div>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold">Materiais consumidos</h2>

        @can('update', $workOrder)
            <button type="button" wire:click="addNew" class="text-xs text-op-accent hover:text-op-accent-hover">
                + Adicionar material
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-4 space-y-4 rounded-lg border border-op-border bg-op-surface p-4">
            <div>
                <x-label for="product_id" value="Produto" />
                <select wire:model="product_id" id="product_id" class="block w-full rounded-lg border border-op-border bg-op-card px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="">Selecione...</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ rtrim(rtrim((string) $product->stock_quantity, '0'), '.') }} {{ $product->unit }} em estoque)</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('product_id')" />
            </div>

            <div>
                <x-label for="quantity" value="Quantidade" />
                <x-input wire:model="quantity" id="quantity" type="number" step="0.01" min="0.01" />
                <x-input-error :messages="$errors->get('quantity')" />
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="secondary" wire:click="cancel">Cancelar</x-button>
                <x-button type="submit">Salvar material</x-button>
            </div>
        </form>
    @endif

    @if ($materials->isEmpty() && ! $showForm)
        <x-empty-state title="Nenhum material registrado" description="Registre os produtos do estoque consumidos nesta ordem de serviço." />
    @else
        <div class="overflow-x-auto rounded-lg border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-3 py-2 font-medium">Produto</th>
                        <th class="px-3 py-2 font-medium">Qtd.</th>
                        <th class="px-3 py-2 font-medium">Custo unit.</th>
                        <th class="px-3 py-2 font-medium">Total</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($materials as $material)
                        <tr wire:key="material-{{ $material->id }}">
                            <td class="px-3 py-2">{{ $material->product->name }}</td>
                            <td class="px-3 py-2 text-op-secondary">{{ rtrim(rtrim((string) $material->quantity, '0'), '.') }} {{ $material->product->unit }}</td>
                            <td class="px-3 py-2 text-op-secondary">R$ {{ number_format((float) $material->unit_cost, 2, ',', '.') }}</td>
                            <td class="px-3 py-2">R$ {{ number_format((float) $material->total_cost, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                @can('update', $workOrder)
                                    <button type="button" wire:click="delete({{ $material->id }})" wire:confirm="Remover este material? O estoque será devolvido." class="text-xs text-op-secondary hover:text-op-danger">
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
