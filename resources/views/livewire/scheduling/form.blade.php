<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">{{ $appointment ? 'Editar agendamento' : 'Novo agendamento' }}</h1>
        <p class="text-xs text-op-secondary">
            {{ $appointment ? 'Atualize os dados do agendamento.' : 'Agende o atendimento de uma ordem de serviço.' }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-op-border bg-op-card p-6">
        <div>
            <x-label for="work_order_id" value="Ordem de serviço" />
            <select wire:model="work_order_id" id="work_order_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                <option value="">Selecione uma ordem de serviço</option>
                @foreach ($workOrders as $workOrder)
                    <option value="{{ $workOrder->id }}">{{ $workOrder->number }} — {{ $workOrder->customer->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('work_order_id')" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="technician_id" value="Técnico" />
                <select wire:model="technician_id" id="technician_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="">Nenhum</option>
                    @foreach ($technicians as $technician)
                        <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('technician_id')" />
            </div>

            <div>
                <x-label for="team_id" value="Equipe" />
                <select wire:model="team_id" id="team_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="">Nenhuma</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('team_id')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="scheduled_start" value="Início" />
                <x-input wire:model="scheduled_start" id="scheduled_start" type="datetime-local" />
                <x-input-error :messages="$errors->get('scheduled_start')" />
            </div>

            <div>
                <x-label for="scheduled_end" value="Fim" />
                <x-input wire:model="scheduled_end" id="scheduled_end" type="datetime-local" />
                <x-input-error :messages="$errors->get('scheduled_end')" />
            </div>
        </div>

        <div>
            <x-label for="status" value="Status" />
            <select wire:model="status" id="status" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" />
        </div>

        <div>
            <x-label for="notes" value="Observações" />
            <textarea wire:model="notes" id="notes" rows="2"
                class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
            <x-input-error :messages="$errors->get('notes')" />
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-op-border pt-6">
            <a href="{{ route('scheduling.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">Cancelar</x-button>
            </a>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $appointment ? 'Salvar alterações' : 'Criar agendamento' }}</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
