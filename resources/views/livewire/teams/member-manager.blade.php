<div>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold">Técnicos da equipe</h2>
    </div>

    @can('update', $team)
        <div class="mb-4 flex gap-3">
            <select wire:model="addingTechnicianId" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                <option value="">Selecione um técnico para adicionar...</option>
                @foreach ($availableTechnicians as $technician)
                    <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                @endforeach
            </select>
            <x-button wire:click="addMember" class="shrink-0">Adicionar</x-button>
        </div>
    @endcan

    @if ($members->isEmpty())
        <x-empty-state title="Nenhum técnico nesta equipe" description="Adicione técnicos para compor a equipe." />
    @else
        <div class="space-y-2">
            @foreach ($members as $member)
                <div wire:key="member-{{ $member->id }}" class="flex items-center justify-between rounded-lg border border-op-border p-3 text-sm">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('technicians.show', $member) }}" wire:navigate class="font-medium hover:text-op-accent-hover">
                            {{ $member->name }}
                        </a>
                        @if ($team->supervisor_id === $member->id)
                            <x-badge variant="info">Supervisor</x-badge>
                        @endif
                    </div>

                    @can('update', $team)
                        <button type="button" wire:click="removeMember({{ $member->id }})" wire:confirm="Remover este técnico da equipe?" class="text-xs text-op-secondary hover:text-op-danger">
                            Remover
                        </button>
                    @endcan
                </div>
            @endforeach
        </div>
    @endif
</div>
