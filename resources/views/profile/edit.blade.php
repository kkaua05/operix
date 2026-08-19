<x-layouts.app title="Perfil — Operix">
    <div class="mx-auto max-w-2xl space-y-6">
        <h1 class="text-lg font-semibold">Meu perfil</h1>

        <div class="rounded-xl border border-op-border bg-op-card p-6">
            @livewire('profile.update-profile-information')
        </div>

        <div class="rounded-xl border border-op-border bg-op-card p-6">
            @livewire('profile.update-password')
        </div>
    </div>
</x-layouts.app>
