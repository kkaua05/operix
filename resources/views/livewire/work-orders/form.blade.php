<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">{{ $workOrder ? 'Editar ordem de serviço' : 'Nova ordem de serviço' }}</h1>
        <p class="text-xs text-op-secondary">
            {{ $workOrder ? 'Atualize os dados da ordem de serviço '.$workOrder->number.'.' : 'Preencha os dados para abrir uma nova ordem de serviço.' }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-op-border bg-op-card p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="customer_id" value="Cliente" />
                <select wire:model.live="customer_id" id="customer_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="">Selecione um cliente</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('customer_id')" />
            </div>

            <div>
                <x-label for="customer_address_id" value="Endereço de atendimento" />
                <select wire:model="customer_address_id" id="customer_address_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent" @disabled(! $customer_id)>
                    <option value="">Nenhum</option>
                    @foreach ($addresses as $address)
                        <option value="{{ $address->id }}">{{ $address->street }}, {{ $address->number }} — {{ $address->city }}/{{ $address->state }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('customer_address_id')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="equipment_id" value="Equipamento" />
                <select wire:model="equipment_id" id="equipment_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent" @disabled(! $customer_id)>
                    <option value="">Nenhum</option>
                    @foreach ($equipmentOptions as $equipment)
                        <option value="{{ $equipment->id }}">{{ $equipment->type }} {{ $equipment->model }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('equipment_id')" />
            </div>

            <div>
                <x-label for="priority" value="Prioridade" />
                <select wire:model="priority" id="priority" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    @foreach ($priorities as $p)
                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('priority')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="category" value="Categoria" />
                <x-input wire:model="category" id="category" type="text" placeholder="Ex: Instalação" />
                <x-input-error :messages="$errors->get('category')" />
            </div>

            <div>
                <x-label for="subcategory" value="Subcategoria" />
                <x-input wire:model="subcategory" id="subcategory" type="text" />
                <x-input-error :messages="$errors->get('subcategory')" />
            </div>
        </div>

        <div>
            <x-label for="description" value="Descrição" />
            <textarea wire:model="description" id="description" rows="3"
                class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary placeholder:text-op-secondary/60 focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
            <x-input-error :messages="$errors->get('description')" />
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
                <x-label for="sla_policy_id" value="Política de SLA" />
                <select wire:model="sla_policy_id" id="sla_policy_id" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="">Nenhuma</option>
                    @foreach ($slaPolicies as $policy)
                        <option value="{{ $policy->id }}">{{ $policy->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('sla_policy_id')" />
            </div>

            <div>
                <x-label for="origin" value="Origem" />
                <select wire:model="origin" id="origin" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                    <option value="manual">Manual</option>
                    <option value="phone">Telefone</option>
                    <option value="email">E-mail</option>
                    <option value="web">Web</option>
                    <option value="api">API</option>
                </select>
                <x-input-error :messages="$errors->get('origin')" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-label for="scheduled_at" value="Agendado para" />
                <x-input wire:model="scheduled_at" id="scheduled_at" type="datetime-local" />
                <x-input-error :messages="$errors->get('scheduled_at')" />
            </div>

            <div>
                <x-label for="estimated_duration_minutes" value="Duração estimada (minutos)" />
                <x-input wire:model="estimated_duration_minutes" id="estimated_duration_minutes" type="number" min="1" />
                <x-input-error :messages="$errors->get('estimated_duration_minutes')" />
            </div>
        </div>

        <div>
            <x-label for="notes" value="Observações" />
            <textarea wire:model="notes" id="notes" rows="2"
                class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
            <x-input-error :messages="$errors->get('notes')" />
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-op-border pt-6">
            <a href="{{ $workOrder ? route('work-orders.show', $workOrder) : route('work-orders.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">Cancelar</x-button>
            </a>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $workOrder ? 'Salvar alterações' : 'Criar ordem' }}</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
