<div x-data="{ shown: false }" x-on:password-updated.window="shown = true; setTimeout(() => shown = false, 3000)">
    <h2 class="mb-1 text-sm font-semibold">Alterar senha</h2>
    <p class="mb-6 text-xs text-op-secondary">Use uma senha longa e única para manter sua conta segura.</p>

    <form wire:submit="updatePassword" class="space-y-4">
        <div>
            <x-label for="current_password" value="Senha atual" />
            <x-input wire:model="current_password" id="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" />
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

        <div class="flex items-center gap-4">
            <x-button type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                Atualizar senha
            </x-button>

            <span x-show="shown" x-transition class="text-xs text-op-success">Atualizada.</span>
        </div>
    </form>
</div>
