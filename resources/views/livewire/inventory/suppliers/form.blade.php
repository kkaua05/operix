<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">{{ $supplier ? 'Editar fornecedor' : 'Novo fornecedor' }}</h1>
        <p class="text-xs text-op-secondary">
            {{ $supplier ? 'Atualize os dados do fornecedor.' : 'Preencha os dados para cadastrar um novo fornecedor.' }}
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
                <x-label for="document" value="CNPJ/CPF" />
                <x-input wire:model="document" id="document" type="text" />
                <x-input-error :messages="$errors->get('document')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
        </div>

        <div>
            <x-label for="status" value="Status" />
            <select wire:model="status" id="status" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
            </select>
            <x-input-error :messages="$errors->get('status')" />
        </div>

        <div>
            <x-label for="notes" value="Observações" />
            <textarea wire:model="notes" id="notes" rows="3" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
            <x-input-error :messages="$errors->get('notes')" />
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-op-border pt-6">
            <a href="{{ route('inventory.suppliers.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">Cancelar</x-button>
            </a>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $supplier ? 'Salvar alterações' : 'Cadastrar fornecedor' }}</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
