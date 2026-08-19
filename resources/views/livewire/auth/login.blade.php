<div>
    <h1 class="mb-1 text-lg font-semibold">Entrar</h1>
    <p class="mb-6 text-xs text-op-secondary">Acesse o painel operacional do Operix.</p>

    <form wire:submit="login" class="space-y-4">
        <div>
            <x-label for="email" value="E-mail" />
            <x-input wire:model="email" id="email" type="email" autofocus autocomplete="username" placeholder="voce@empresa.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-label for="password" value="Senha" />
            <x-input wire:model="password" id="password" type="password" autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-xs text-op-secondary">
                <input wire:model="remember" type="checkbox" class="rounded border-op-border bg-op-surface text-op-accent focus:ring-op-accent">
                Lembrar de mim
            </label>

            <a href="{{ route('password.request') }}" wire:navigate class="text-xs text-op-secondary hover:text-op-primary">
                Esqueceu a senha?
            </a>
        </div>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Entrar</span>
            <span wire:loading wire:target="login">Entrando...</span>
        </x-button>
    </form>
</div>
