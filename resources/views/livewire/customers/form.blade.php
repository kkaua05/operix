<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">{{ $customer ? 'Editar cliente' : 'Novo cliente' }}</h1>
        <p class="text-xs text-op-secondary">
            {{ $customer ? 'Atualize os dados do cliente.' : 'Preencha os dados para cadastrar um novo cliente.' }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-op-border bg-op-card p-6">
        <div>
            <x-label for="type" value="Tipo" />
            <select wire:model="type" id="type" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                <option value="individual">Pessoa física</option>
                <option value="company">Pessoa jurídica</option>
            </select>
            <x-input-error :messages="$errors->get('type')" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="name" value="Nome" />
                <x-input wire:model="name" id="name" type="text" autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-label for="document" :value="$type === 'company' ? 'CNPJ' : 'CPF'" />
                <x-input wire:model="document" id="document" type="text" />
                <x-input-error :messages="$errors->get('document')" />
            </div>
        </div>

        @if ($type === 'company')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="legal_name" value="Razão social" />
                    <x-input wire:model="legal_name" id="legal_name" type="text" />
                    <x-input-error :messages="$errors->get('legal_name')" />
                </div>

                <div>
                    <x-label for="trading_name" value="Nome fantasia" />
                    <x-input wire:model="trading_name" id="trading_name" type="text" />
                    <x-input-error :messages="$errors->get('trading_name')" />
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-label for="email" value="E-mail" />
                <x-input wire:model="email" id="email" type="email" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div>
                <x-label for="phone" value="Telefone" />
                <x-input wire:model="phone" id="phone" type="text" />
                <x-input-error :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-label for="mobile_phone" value="Celular" />
                <x-input wire:model="mobile_phone" id="mobile_phone" type="text" />
                <x-input-error :messages="$errors->get('mobile_phone')" />
            </div>
        </div>

        <div>
            <x-label for="notes" value="Observações" />
            <textarea wire:model="notes" id="notes" rows="3"
                class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary placeholder:text-op-secondary/60 focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
            <x-input-error :messages="$errors->get('notes')" />
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
            <a href="{{ $customer ? route('customers.show', $customer) : route('customers.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">Cancelar</x-button>
            </a>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $customer ? 'Salvar alterações' : 'Criar cliente' }}</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
