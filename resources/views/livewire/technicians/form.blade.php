<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">{{ $technician ? 'Editar técnico' : 'Novo técnico' }}</h1>
        <p class="text-xs text-op-secondary">
            {{ $technician ? 'Atualize os dados do técnico.' : 'Preencha os dados para cadastrar um novo técnico.' }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-op-border bg-op-card p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="name" value="Nome" />
                <x-input wire:model="name" id="name" type="text" autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-label for="document" value="CPF" />
                <x-input wire:model="document" id="document" type="text" />
                <x-input-error :messages="$errors->get('document')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="email" value="E-mail" />
                <x-input wire:model="email" id="email" type="email" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div>
                <x-label for="phone" value="Telefone" />
                <x-input wire:model="phone" id="phone" type="text" />
                <x-input-error :messages="$errors->get('phone')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="registration_number" value="Matrícula" />
                <x-input wire:model="registration_number" id="registration_number" type="text" />
                <x-input-error :messages="$errors->get('registration_number')" />
            </div>

            <div>
                <x-label for="region" value="Região" />
                <x-input wire:model="region" id="region" type="text" />
                <x-input-error :messages="$errors->get('region')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

            <div>
                <x-label for="daily_capacity" value="Capacidade diária (atendimentos)" />
                <x-input wire:model="daily_capacity" id="daily_capacity" type="number" min="1" max="24" />
                <x-input-error :messages="$errors->get('daily_capacity')" />
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

        <div class="flex items-center justify-end gap-3 border-t border-op-border pt-6">
            <a href="{{ $technician ? route('technicians.show', $technician) : route('technicians.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">Cancelar</x-button>
            </a>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $technician ? 'Salvar alterações' : 'Cadastrar técnico' }}</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
