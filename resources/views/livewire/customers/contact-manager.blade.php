<div>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold">Contatos</h2>

        @can('update', $customer)
            <button type="button" wire:click="addNew" class="text-xs text-op-accent hover:text-op-accent-hover">
                + Adicionar contato
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-4 space-y-4 rounded-lg border border-op-border bg-op-surface p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="name" value="Nome" />
                    <x-input wire:model="name" id="name" type="text" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-label for="role" value="Cargo / função" />
                    <x-input wire:model="role" id="role" type="text" />
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
                </div>
            </div>

            <label class="flex items-center gap-2 text-xs text-op-secondary">
                <input wire:model="is_primary" type="checkbox" class="rounded border-op-border bg-op-card text-op-accent focus:ring-op-accent">
                Contato principal
            </label>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="secondary" wire:click="cancel">Cancelar</x-button>
                <x-button type="submit">Salvar contato</x-button>
            </div>
        </form>
    @endif

    @if ($contacts->isEmpty() && ! $showForm)
        <x-empty-state title="Nenhum contato cadastrado" description="Adicione o primeiro contato deste cliente." />
    @else
        <div class="space-y-2">
            @foreach ($contacts as $contact)
                <div wire:key="contact-{{ $contact->id }}" class="flex items-start justify-between rounded-lg border border-op-border p-3">
                    <div class="text-sm">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $contact->name }}</span>
                            @if ($contact->role)
                                <span class="text-xs text-op-secondary">· {{ $contact->role }}</span>
                            @endif
                            @if ($contact->is_primary)
                                <x-badge variant="info">Principal</x-badge>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-op-secondary">
                            {{ $contact->email ?: '—' }} {{ $contact->phone ? '· '.$contact->phone : '' }}
                        </p>
                    </div>

                    @can('update', $customer)
                        <div class="flex shrink-0 gap-3 text-xs">
                            <button type="button" wire:click="edit({{ $contact->id }})" class="text-op-secondary hover:text-op-primary">Editar</button>
                            <button type="button" wire:click="delete({{ $contact->id }})" wire:confirm="Excluir este contato?" class="text-op-secondary hover:text-op-danger">Excluir</button>
                        </div>
                    @endcan
                </div>
            @endforeach
        </div>
    @endif
</div>
