<div>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold">Endereços</h2>

        @can('update', $customer)
            <button type="button" wire:click="addNew" class="text-xs text-op-accent hover:text-op-accent-hover">
                + Adicionar endereço
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-4 space-y-4 rounded-lg border border-op-border bg-op-surface p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="label" value="Rótulo (ex: Sede, Filial)" />
                    <x-input wire:model="label" id="label" type="text" />
                </div>
                <div>
                    <x-label for="type" value="Tipo" />
                    <select wire:model="type" id="type" class="block w-full rounded-lg border border-op-border bg-op-card px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                        <option value="service">Atendimento</option>
                        <option value="billing">Cobrança</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-label for="zip_code" value="CEP" />
                    <x-input wire:model="zip_code" id="zip_code" type="text" />
                </div>
                <div class="sm:col-span-2">
                    <x-label for="street" value="Logradouro" />
                    <x-input wire:model="street" id="street" type="text" />
                    <x-input-error :messages="$errors->get('street')" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-label for="number" value="Número" />
                    <x-input wire:model="number" id="number" type="text" />
                </div>
                <div>
                    <x-label for="complement" value="Complemento" />
                    <x-input wire:model="complement" id="complement" type="text" />
                </div>
                <div>
                    <x-label for="neighborhood" value="Bairro" />
                    <x-input wire:model="neighborhood" id="neighborhood" type="text" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-label for="city" value="Cidade" />
                    <x-input wire:model="city" id="city" type="text" />
                    <x-input-error :messages="$errors->get('city')" />
                </div>
                <div>
                    <x-label for="state" value="UF" />
                    <x-input wire:model="state" id="state" type="text" maxlength="2" />
                    <x-input-error :messages="$errors->get('state')" />
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 pb-2 text-xs text-op-secondary">
                        <input wire:model="is_primary" type="checkbox" class="rounded border-op-border bg-op-card text-op-accent focus:ring-op-accent">
                        Endereço principal
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="secondary" wire:click="cancel">Cancelar</x-button>
                <x-button type="submit">Salvar endereço</x-button>
            </div>
        </form>
    @endif

    @if ($addresses->isEmpty() && ! $showForm)
        <x-empty-state title="Nenhum endereço cadastrado" description="Adicione o primeiro endereço deste cliente." />
    @else
        <div class="space-y-2">
            @foreach ($addresses as $address)
                <div wire:key="address-{{ $address->id }}" class="flex items-start justify-between rounded-lg border border-op-border p-3">
                    <div class="text-sm">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $address->label ?: 'Endereço' }}</span>
                            @if ($address->is_primary)
                                <x-badge variant="info">Principal</x-badge>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-op-secondary">
                            {{ $address->street }}{{ $address->number ? ', '.$address->number : '' }}
                            {{ $address->complement ? '- '.$address->complement : '' }}
                            — {{ $address->neighborhood }}, {{ $address->city }}/{{ $address->state }}
                            {{ $address->zip_code ? '· CEP '.$address->zip_code : '' }}
                        </p>
                    </div>

                    @can('update', $customer)
                        <div class="flex shrink-0 gap-3 text-xs">
                            <button type="button" wire:click="edit({{ $address->id }})" class="text-op-secondary hover:text-op-primary">Editar</button>
                            <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Excluir este endereço?" class="text-op-secondary hover:text-op-danger">Excluir</button>
                        </div>
                    @endcan
                </div>
            @endforeach
        </div>
    @endif
</div>
