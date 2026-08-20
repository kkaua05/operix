<div>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold">{{ $technician->name }}</h1>
                @php
                    $statusVariant = match ($technician->status) {
                        \App\Enums\TechnicianStatus::Available => 'success',
                        \App\Enums\TechnicianStatus::Busy, \App\Enums\TechnicianStatus::InService => 'warning',
                        \App\Enums\TechnicianStatus::EnRoute => 'info',
                        default => 'default',
                    };
                @endphp
                <x-badge :variant="$statusVariant">{{ $technician->status->label() }}</x-badge>
            </div>
            <p class="text-xs text-op-secondary">
                {{ $technician->registration_number ? 'Matrícula '.$technician->registration_number : 'Sem matrícula' }}
                {{ $technician->region ? '· '.$technician->region : '' }}
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('technicians.index') }}" wire:navigate>
                <x-button variant="secondary">Voltar</x-button>
            </a>
            @can('update', $technician)
                <a href="{{ route('technicians.edit', $technician) }}" wire:navigate>
                    <x-button>Editar</x-button>
                </a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="mb-6 border-b border-op-border">
        <nav class="-mb-px flex gap-6 overflow-x-auto text-sm">
            @foreach ([
                'perfil' => 'Perfil',
                'agenda' => 'Agenda',
                'ordens' => 'Ordens ('.$technician->work_orders_count.')',
                'produtividade' => 'Produtividade',
                'sla' => 'SLA',
                'avaliacoes' => 'Avaliações ('.$technician->ratings_count.')',
                'materiais' => 'Materiais utilizados',
                'historico' => 'Histórico',
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

    @if ($activeTab === 'perfil')
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-op-border bg-op-card p-5">
                    <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Contato</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-op-secondary">CPF</dt><dd>{{ $technician->document ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-op-secondary">E-mail</dt><dd>{{ $technician->email ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-op-secondary">Telefone</dt><dd>{{ $technician->phone ?: '—' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-op-border bg-op-card p-5">
                    <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Operação</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-op-secondary">Região</dt><dd>{{ $technician->region ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-op-secondary">Capacidade diária</dt><dd>{{ $technician->daily_capacity }} atendimentos</dd></div>
                        <div class="flex justify-between"><dt class="text-op-secondary">Supervisor</dt><dd>{{ $technician->supervisor?->name ?: '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="rounded-xl border border-op-border bg-op-card p-5">
                @livewire('technicians.skill-manager', ['technician' => $technician], key('skills-'.$technician->id))
            </div>
        </div>
    @elseif ($activeTab === 'agenda')
        @if ($upcomingAppointments->isEmpty())
            <x-empty-state title="Nenhum agendamento futuro" description="Os próximos atendimentos deste técnico aparecerão aqui." />
        @else
            <div class="space-y-2">
                @foreach ($upcomingAppointments as $appointment)
                    <div wire:key="tech-appt-{{ $appointment->id }}" class="flex items-center justify-between rounded-xl border border-op-border bg-op-card p-4">
                        <div>
                            <a href="{{ route('work-orders.show', $appointment->work_order_id) }}" wire:navigate class="text-sm font-medium hover:text-op-accent-hover">
                                {{ $appointment->workOrder->number }} — {{ $appointment->workOrder->customer->name }}
                            </a>
                            <p class="text-xs text-op-secondary">
                                {{ $appointment->scheduled_start->format('d/m/Y H:i') }} – {{ $appointment->scheduled_end->format('H:i') }}
                            </p>
                        </div>
                        <x-badge variant="info">{{ $appointment->status->label() }}</x-badge>
                    </div>
                @endforeach
            </div>
        @endif
    @elseif ($activeTab === 'ordens')
        <x-empty-state
            title="Módulo de Ordens de Serviço em construção"
            description="As ordens de serviço atribuídas a este técnico estarão disponíveis em uma próxima fase do roadmap."
        />
    @elseif ($activeTab === 'produtividade')
        <x-empty-state
            title="Indicadores de produtividade em construção"
            description="Métricas de produtividade dependem do módulo de Ordens de Serviço, ainda não implementado."
        />
    @elseif ($activeTab === 'sla')
        <x-empty-state
            title="Indicadores de SLA em construção"
            description="O acompanhamento de SLA deste técnico estará disponível quando o SLA Engine for implementado."
        />
    @elseif ($activeTab === 'avaliacoes')
        <div class="space-y-4">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <h3 class="mb-1 text-xs font-semibold tracking-wider text-op-secondary uppercase">Avaliação média</h3>
                <p class="text-2xl font-semibold">
                    {{ $averageRating ? number_format($averageRating, 1) : '—' }}
                    <span class="text-sm font-normal text-op-secondary">/ 5</span>
                </p>
            </div>

            @if ($ratings->isEmpty())
                <x-empty-state
                    title="Nenhuma avaliação registrada"
                    description="As avaliações de clientes aparecerão aqui após a conclusão de ordens de serviço."
                />
            @else
                <div class="space-y-2">
                    @foreach ($ratings as $rating)
                        <div wire:key="rating-{{ $rating->id }}" class="rounded-lg border border-op-border p-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ $rating->customer->name }}</span>
                                <x-badge variant="info">{{ $rating->score }} / 5</x-badge>
                            </div>
                            @if ($rating->comment)
                                <p class="mt-1 text-xs text-op-secondary">{{ $rating->comment }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @elseif ($activeTab === 'materiais')
        <x-empty-state
            title="Materiais utilizados em construção"
            description="O histórico de materiais consumidos por este técnico estará disponível quando o módulo de Estoque for implementado."
        />
    @elseif ($activeTab === 'historico')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Histórico</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex justify-between border-b border-op-border pb-2">
                    <span class="text-op-secondary">Cadastro criado</span>
                    <span>{{ $technician->created_at->format('d/m/Y H:i') }}</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-op-secondary">Última atualização</span>
                    <span>{{ $technician->updated_at->format('d/m/Y H:i') }}</span>
                </li>
            </ul>
            <p class="mt-4 text-xs text-op-secondary">
                O log de auditoria completo (quem alterou o quê) estará disponível quando o módulo de Auditoria for implementado.
            </p>
        </div>
    @endif
</div>
