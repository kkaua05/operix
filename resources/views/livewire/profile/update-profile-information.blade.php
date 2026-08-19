<div x-data="{ shown: false }" x-on:profile-updated.window="shown = true; setTimeout(() => shown = false, 3000)">
    <h2 class="mb-1 text-sm font-semibold">Informações do perfil</h2>
    <p class="mb-6 text-xs text-op-secondary">Atualize seu nome, e-mail e telefone.</p>

    <form wire:submit="updateProfileInformation" class="space-y-4">
        <div>
            <x-label for="name" value="Nome" />
            <x-input wire:model="name" id="name" type="text" autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-label for="email" value="E-mail" />
            <x-input wire:model="email" id="email" type="email" autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-label for="phone" value="Telefone" />
            <x-input wire:model="phone" id="phone" type="text" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" />
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit" wire:loading.attr="disabled" wire:target="updateProfileInformation">
                Salvar
            </x-button>

            <span x-show="shown" x-transition class="text-xs text-op-success">Salvo.</span>
        </div>
    </form>
</div>
