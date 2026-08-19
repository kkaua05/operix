<div>
    <h1 class="mb-1 text-lg font-semibold">Recuperar senha</h1>
    <p class="mb-6 text-xs text-op-secondary">
        Informe seu e-mail e enviaremos um link para redefinir sua senha.
    </p>

    @if ($status)
        <x-alert variant="success" class="mb-4">{{ $status }}</x-alert>
    @endif

    <form wire:submit="sendResetLink" class="space-y-4">
        <div>
            <x-label for="email" value="E-mail" />
            <x-input wire:model="email" id="email" type="email" autofocus autocomplete="username" placeholder="voce@empresa.com" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="sendResetLink">
            <span wire:loading.remove wire:target="sendResetLink">Enviar link de recuperação</span>
            <span wire:loading wire:target="sendResetLink">Enviando...</span>
        </x-button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" wire:navigate class="text-xs text-op-secondary hover:text-op-primary">
            Voltar para o login
        </a>
    </div>
</div>
