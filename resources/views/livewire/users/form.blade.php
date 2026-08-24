<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">{{ $user ? 'Editar usuário' : 'Novo usuário' }}</h1>
        <p class="text-xs text-op-secondary">
            {{ $user ? 'Atualize os dados e o papel de acesso do usuário.' : 'Preencha os dados para cadastrar um novo usuário.' }}
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
                <x-label for="email" value="E-mail" />
                <x-input wire:model="email" id="email" type="email" />
                <x-input-error :messages="$errors->get('email')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="phone" value="Telefone" />
                <x-input wire:model="phone" id="phone" type="text" />
                <x-input-error :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-label for="status" value="Status" />
                <select wire:model="status" id="status" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
                <x-input-error :messages="$errors->get('status')" />
            </div>
        </div>

        <div>
            <x-label for="role" value="Papel de acesso" />
            <select wire:model="role" id="role" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                <option value="">Selecione...</option>
                @foreach ($roles as $r)
                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" />
        </div>

        <div>
            <x-label for="password" :value="$user ? 'Nova senha (opcional)' : 'Senha'" />
            <x-input wire:model="password" id="password" type="password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-op-border pt-6">
            <a href="{{ route('users.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">Cancelar</x-button>
            </a>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $user ? 'Salvar alterações' : 'Cadastrar usuário' }}</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
