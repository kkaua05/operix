<div>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold">Especialidades</h2>

        @can('update', $technician)
            <button type="button" wire:click="addNew" class="text-xs text-op-accent hover:text-op-accent-hover">
                + Adicionar especialidade
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-4 space-y-4 rounded-lg border border-op-border bg-op-surface p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="skill" value="Especialidade" />
                    <x-input wire:model="skill" id="skill" type="text" placeholder="Ex: Redes, Climatização..." />
                    <x-input-error :messages="$errors->get('skill')" />
                </div>
                <div>
                    <x-label for="proficiency_level" value="Nível" />
                    <select wire:model="proficiency_level" id="proficiency_level" class="block w-full rounded-lg border border-op-border bg-op-card px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                        <option value="">Não informado</option>
                        <option value="Básico">Básico</option>
                        <option value="Intermediário">Intermediário</option>
                        <option value="Avançado">Avançado</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="secondary" wire:click="cancel">Cancelar</x-button>
                <x-button type="submit">Salvar especialidade</x-button>
            </div>
        </form>
    @endif

    @if ($skills->isEmpty() && ! $showForm)
        <x-empty-state title="Nenhuma especialidade cadastrada" description="Adicione as especialidades deste técnico." />
    @else
        <div class="flex flex-wrap gap-2">
            @foreach ($skills as $skillItem)
                <div wire:key="skill-{{ $skillItem->id }}" class="flex items-center gap-2 rounded-full border border-op-border bg-op-surface py-1 pr-2 pl-3 text-xs">
                    <span>{{ $skillItem->skill }}</span>
                    @if ($skillItem->proficiency_level)
                        <span class="text-op-secondary">· {{ $skillItem->proficiency_level }}</span>
                    @endif
                    @can('update', $technician)
                        <button type="button" wire:click="delete({{ $skillItem->id }})" wire:confirm="Remover esta especialidade?" class="text-op-secondary hover:text-op-danger">
                            ✕
                        </button>
                    @endcan
                </div>
            @endforeach
        </div>
    @endif
</div>
