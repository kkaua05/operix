<div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold">{{ $workOrder->number }}</h1>
                <x-status-badge :status="$workOrder->status" />
                <x-priority-badge :priority="$workOrder->priority" />
            </div>
            <p class="text-xs text-op-secondary">
                {{ $workOrder->customer->name }}
                {{ $workOrder->category ? '· '.$workOrder->category : '' }}
                {{ $workOrder->technician ? '· '.$workOrder->technician->name : '' }}
            </p>

            @if ($workOrder->slaPolicy)
                <div class="mt-3 max-w-xs">
                    <x-sla-indicator :status="$liveSlaStatus" :percentage="$slaPercentage" />
                </div>
            @endif
        </div>

        <div class="flex gap-3">
            <a href="{{ route('work-orders.index') }}" wire:navigate>
                <x-button variant="secondary">Voltar</x-button>
            </a>
            @can('update', $workOrder)
                <a href="{{ route('work-orders.edit', $workOrder) }}" wire:navigate>
                    <x-button>Editar</x-button>
                </a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @if ($errors->has('status'))
        <x-alert variant="danger" class="mb-4">{{ $errors->first('status') }}</x-alert>
    @endif

    @if (! empty($allowedTransitions))
        <div class="mb-6 flex flex-wrap items-center gap-2 rounded-xl border border-op-border bg-op-card p-4">
            <span class="text-xs text-op-secondary">Avançar status:</span>
            @foreach ($allowedTransitions as $transition)
                <button
                    type="button"
                    wire:click="transitionTo('{{ $transition->value }}')"
                    wire:confirm="Mudar o status para &quot;{{ $transition->label() }}&quot;?"
                    @class([
                        'rounded-lg border px-3 py-1.5 text-xs font-medium transition',
                        'border-op-danger/30 text-op-danger hover:bg-op-danger/10' => $transition === \App\Enums\WorkOrderStatus::Cancelled,
                        'border-op-border text-op-primary hover:bg-op-surface' => $transition !== \App\Enums\WorkOrderStatus::Cancelled,
                    ])
                >
                    {{ $transition->label() }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="mb-6 border-b border-op-border">
        <nav class="-mb-px flex gap-6 overflow-x-auto text-sm">
            @foreach ([
                'detalhes' => 'Detalhes',
                'itens' => 'Itens',
                'timeline' => 'Timeline',
                'sla' => 'SLA',
                'checklist' => 'Checklist',
                'materiais' => 'Materiais',
                'anexos' => 'Anexos',
            ] as $tab => $label)
                <button
                    type="button"
                    wire:click="setTab('{{ $tab }}')"
                    @class([
                        'whitespace-nowrap border-b-2 px-1 py-3 text-xs font-medium transition',
                        'border-op-accent text-op-primary' => $activeTab === $tab,
                        'border-transparent text-op-secondary hover:text-op-primary' => $activeTab !== $tab,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    @if ($activeTab === 'detalhes')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Cliente e local</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-op-secondary">Cliente</dt>
                        <dd><a href="{{ route('customers.show', $workOrder->customer) }}" wire:navigate class="hover:text-op-accent-hover">{{ $workOrder->customer->name }}</a></dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Endereço</dt><dd class="text-right">{{ $workOrder->address ? $workOrder->address->street.', '.$workOrder->address->number : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Equipamento</dt><dd>{{ $workOrder->equipment?->type ?: '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Atendimento</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-op-secondary">Técnico</dt><dd>{{ $workOrder->technician?->name ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Equipe</dt><dd>{{ $workOrder->team?->name ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">SLA</dt><dd>{{ $workOrder->slaPolicy?->name ?: 'Não configurado' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Agendado para</dt><dd>{{ $workOrder->scheduled_at?->format('d/m/Y H:i') ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Origem</dt><dd>{{ ['manual' => 'Manual', 'phone' => 'Telefone', 'email' => 'E-mail', 'web' => 'Web', 'api' => 'API'][$workOrder->origin] ?? $workOrder->origin }}</dd></div>
                </dl>
            </div>

            @if ($workOrder->description)
                <div class="rounded-xl border border-op-border bg-op-card p-5 sm:col-span-2">
                    <h3 class="mb-2 text-xs font-semibold tracking-wider text-op-secondary uppercase">Descrição</h3>
                    <p class="text-sm whitespace-pre-line text-op-secondary">{{ $workOrder->description }}</p>
                </div>
            @endif

            @if ($workOrder->notes)
                <div class="rounded-xl border border-op-border bg-op-card p-5 sm:col-span-2">
                    <h3 class="mb-2 text-xs font-semibold tracking-wider text-op-secondary uppercase">Observações</h3>
                    <p class="text-sm whitespace-pre-line text-op-secondary">{{ $workOrder->notes }}</p>
                </div>
            @endif
        </div>
    @elseif ($activeTab === 'itens')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            @livewire('work-orders.item-manager', ['workOrder' => $workOrder], key('items-'.$workOrder->id))
        </div>
    @elseif ($activeTab === 'timeline')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <h3 class="mb-4 text-xs font-semibold tracking-wider text-op-secondary uppercase">Timeline</h3>

            @if ($statusHistory->isEmpty())
                <x-empty-state title="Nenhum evento registrado" />
            @else
                <ol class="space-y-4 border-l border-op-border pl-4">
                    @foreach ($statusHistory as $event)
                        <li wire:key="event-{{ $event->id }}" class="relative">
                            <span class="absolute top-1 -left-[21px] h-2 w-2 rounded-full bg-op-accent"></span>
                            <p class="text-sm">
                                @if ($event->from_status)
                                    {{ $event->from_status->label() }} → {{ $event->to_status->label() }}
                                @else
                                    OS criada com status "{{ $event->to_status->label() }}"
                                @endif
                            </p>
                            <p class="text-xs text-op-secondary">
                                {{ $event->created_at->format('d/m/Y H:i') }}
                                {{ $event->changedBy ? '· '.$event->changedBy->name : '' }}
                            </p>
                            @if ($event->notes)
                                <p class="mt-1 text-xs text-op-secondary">{{ $event->notes }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    @elseif ($activeTab === 'sla')
        @if (! $workOrder->slaPolicy)
            <x-empty-state
                title="Nenhuma política de SLA aplicada"
                description="Edite a ordem de serviço e selecione uma política de SLA para acompanhar o prazo de atendimento."
            />
        @else
            <div class="space-y-4">
                <div class="rounded-xl border border-op-border bg-op-card p-5">
                    <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Status do SLA</h3>
                    <x-sla-indicator :status="$liveSlaStatus" :percentage="$slaPercentage" class="mb-4" />
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-op-secondary">Política</dt><dd>{{ $workOrder->slaPolicy->name }}</dd></div>
                        <div class="flex justify-between"><dt class="text-op-secondary">Tempo de resolução</dt><dd>{{ $workOrder->slaPolicy->resolution_time_minutes }} min</dd></div>
                        <div class="flex justify-between"><dt class="text-op-secondary">Considera horário comercial</dt><dd>{{ $workOrder->slaPolicy->business_hours_only ? 'Sim' : 'Não' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-op-secondary">Prazo</dt><dd>{{ $workOrder->sla_due_at?->format('d/m/Y H:i') ?: '—' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-op-border bg-op-card p-5">
                    <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Eventos de SLA</h3>

                    @if ($slaEvents->isEmpty())
                        <x-empty-state title="Nenhum evento de SLA registrado" description="Pausas e violações de SLA aparecerão aqui automaticamente." />
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($slaEvents as $event)
                                <li wire:key="sla-event-{{ $event->id }}" class="flex justify-between border-b border-op-border pb-2 last:border-0 last:pb-0">
                                    <span>{{ ['paused' => 'SLA pausado', 'resumed' => 'SLA retomado', 'breached' => 'SLA violado'][$event->event_type] ?? $event->event_type }}</span>
                                    <span class="text-op-secondary">{{ $event->occurred_at->format('d/m/Y H:i') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    @elseif ($activeTab === 'checklist')
        <x-empty-state
            title="Módulo de Checklist em construção"
            description="Checklists de execução por categoria estarão disponíveis em uma próxima fase do roadmap."
        />
    @elseif ($activeTab === 'materiais')
        <x-empty-state
            title="Módulo de Materiais em construção"
            description="O consumo de materiais desta ordem estará disponível quando o módulo de Estoque for implementado."
        />
    @elseif ($activeTab === 'anexos')
        <x-empty-state
            title="Módulo de Anexos em construção"
            description="Fotos, vídeos e assinatura do cliente estarão disponíveis em uma próxima fase do roadmap."
        />
    @endif
</div>
