<div>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold">Equipamentos</h2>

        @can('create', \App\Models\Equipment::class)
            <button type="button" wire:click="addNew" class="text-xs text-op-accent hover:text-op-accent-hover">
                + Adicionar equipamento
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-4 space-y-4 rounded-lg border border-op-border bg-op-surface p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="type" value="Tipo" />
                    <x-input wire:model="type" id="type" type="text" placeholder="Ex: Roteador, Central de alarme..." />
                    <x-input-error :messages="$errors->get('type')" />
                </div>
                <div>
                    <x-label for="manufacturer" value="Fabricante" />
                    <x-input wire:model="manufacturer" id="manufacturer" type="text" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-label for="model" value="Modelo" />
                    <x-input wire:model="model" id="model" type="text" />
                </div>
                <div>
                    <x-label for="serial_number" value="Número de série" />
                    <x-input wire:model="serial_number" id="serial_number" type="text" />
                </div>
                <div>
                    <x-label for="asset_tag" value="Patrimônio" />
                    <x-input wire:model="asset_tag" id="asset_tag" type="text" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-label for="installed_at" value="Data de instalação" />
                    <x-input wire:model="installed_at" id="installed_at" type="date" />
                </div>
                <div>
                    <x-label for="warranty_expires_at" value="Garantia até" />
                    <x-input wire:model="warranty_expires_at" id="warranty_expires_at" type="date" />
                </div>
                <div>
                    <x-label for="status" value="Status" />
                    <select wire:model="status" id="status" class="block w-full rounded-lg border border-op-border bg-op-card px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                        <option value="removed">Removido</option>
                    </select>
                </div>
            </div>

            <div>
                <x-label for="notes" value="Observações" />
                <textarea wire:model="notes" id="notes" rows="2"
                    class="block w-full rounded-lg border border-op-border bg-op-card px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="secondary" wire:click="cancel">Cancelar</x-button>
                <x-button type="submit">Salvar equipamento</x-button>
            </div>
        </form>
    @endif

    @if ($equipmentList->isEmpty() && ! $showForm)
        <x-empty-state title="Nenhum equipamento cadastrado" description="Adicione o primeiro equipamento deste cliente." />
    @else
        <div class="space-y-2">
            @foreach ($equipmentList as $equipment)
                <div wire:key="equipment-{{ $equipment->id }}" class="flex items-start justify-between rounded-lg border border-op-border p-3">
                    <div class="text-sm">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $equipment->type }}</span>
                            @if ($equipment->manufacturer || $equipment->model)
                                <span class="text-xs text-op-secondary">
                                    · {{ $equipment->manufacturer }} {{ $equipment->model }}
                                </span>
                            @endif
                            <x-badge :variant="$equipment->status === 'active' ? 'success' : 'default'">
                                {{ ['active' => 'Ativo', 'inactive' => 'Inativo', 'removed' => 'Removido'][$equipment->status] }}
                            </x-badge>
                        </div>
                        <p class="mt-0.5 text-xs text-op-secondary">
                            @if ($equipment->serial_number) S/N {{ $equipment->serial_number }} @endif
                            @if ($equipment->asset_tag) · Pat. {{ $equipment->asset_tag }} @endif
                            @if ($equipment->warranty_expires_at) · Garantia até {{ $equipment->warranty_expires_at->format('d/m/Y') }} @endif
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-3 text-xs">
                        @can('update', $equipment)
                            <button type="button" wire:click="edit({{ $equipment->id }})" class="text-op-secondary hover:text-op-primary">Editar</button>
                        @endcan
                        @can('delete', $equipment)
                            <button type="button" wire:click="delete({{ $equipment->id }})" wire:confirm="Excluir este equipamento?" class="text-op-secondary hover:text-op-danger">Excluir</button>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
