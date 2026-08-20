<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">{{ $team ? 'Editar equipe' : 'Nova equipe' }}</h1>
        <p class="text-xs text-op-secondary">
            {{ $team ? 'Atualize os dados da equipe.' : 'Preencha os dados para criar uma nova equipe.' }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-op-border bg-op-card p-6">
        <div>
            <x-label for="name" value="Nome" />
            <x-input wire:model="name" id="name" type="text" autofocus placeholder="Ex: Equipe Norte" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="region" value="Região" />
                <x-input wire:model="region" id="region" type="text" />
                <x-input-error :messages="$errors->get('region')" />
            </div>

            <div>
                <x-label for="supervisor_id" value="Supervisor" />
                <select wire:model="supervisor_id" id="supervisor_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="">Nenhum</option>
                    @foreach ($supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('supervisor_id')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="capacity" value="Capacidade (nº de técnicos)" />
                <x-input wire:model="capacity" id="capacity" type="number" min="1" max="100" />
                <x-input-error :messages="$errors->get('capacity')" />
            </div>

            <div>
                <x-label for="status" value="Status" />
                <select wire:model="status" id="status" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="active">Ativa</option>
                    <option value="inactive">Inativa</option>
                </select>
                <x-input-error :messages="$errors->get('status')" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-op-border pt-6">
            <a href="{{ $team ? route('teams.show', $team) : route('teams.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">Cancelar</x-button>
            </a>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $team ? 'Salvar alterações' : 'Criar equipe' }}</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
