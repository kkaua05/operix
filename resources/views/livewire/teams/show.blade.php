<div>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold">{{ $team->name }}</h1>
                <x-badge :variant="$team->status === 'active' ? 'success' : 'default'">
                    {{ $team->status === 'active' ? 'Ativa' : 'Inativa' }}
                </x-badge>
            </div>
            <p class="text-xs text-op-secondary">
                {{ $team->region ?: 'Sem região definida' }}
                {{ $team->supervisor ? '· Supervisor: '.$team->supervisor->name : '' }}
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('teams.index') }}" wire:navigate>
                <x-button variant="secondary">Voltar</x-button>
            </a>
            @can('update', $team)
                <a href="{{ route('teams.edit', $team) }}" wire:navigate>
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
                'visao-geral' => 'Visão geral',
                'tecnicos' => 'Técnicos ('.$team->technicians_count.')',
                'ordens' => 'Ordens ('.$team->work_orders_count.')',
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

    @if ($activeTab === 'visao-geral')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Dados da equipe</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-op-secondary">Região</dt><dd>{{ $team->region ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Supervisor</dt><dd>{{ $team->supervisor?->name ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Capacidade</dt><dd>{{ $team->capacity ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-op-secondary">Técnicos ativos</dt><dd>{{ $team->technicians_count }}</dd></div>
                </dl>
            </div>
        </div>
    @elseif ($activeTab === 'tecnicos')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            @livewire('teams.member-manager', ['team' => $team], key('members-'.$team->id))
        </div>
    @elseif ($activeTab === 'ordens')
        <x-empty-state
            title="Módulo de Ordens de Serviço em construção"
            description="As ordens de serviço atribuídas a esta equipe estarão disponíveis em uma próxima fase do roadmap."
        />
    @elseif ($activeTab === 'historico')
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Histórico</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex justify-between border-b border-op-border pb-2">
                    <span class="text-op-secondary">Criada em</span>
                    <span>{{ $team->created_at->format('d/m/Y H:i') }}</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-op-secondary">Última atualização</span>
                    <span>{{ $team->updated_at->format('d/m/Y H:i') }}</span>
                </li>
            </ul>
        </div>
    @endif
</div>
