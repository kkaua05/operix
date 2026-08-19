<div>
    <h1 class="mb-1 text-lg font-semibold">Redefinir senha</h1>
    <p class="mb-6 text-xs text-op-secondary">Escolha uma nova senha para sua conta.</p>

    <form wire:submit="resetPassword" class="space-y-4">
        <div>
            <x-label for="email" value="E-mail" />
            <x-input wire:model="email" id="email" type="email" autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-label for="password" value="Nova senha" />
            <x-input wire:model="password" id="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-label for="password_confirmation" value="Confirmar nova senha" />
            <x-input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">Redefinir senha</span>
            <span wire:loading wire:target="resetPassword">Salvando...</span>
        </x-button>
    </form>
</div>
