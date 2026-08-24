<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold">Olá, {{ auth()->user()->name }}.</h1>
            <p class="text-sm text-op-secondary">Resumo operacional de {{ $monthLabel }}.</p>
        </div>

        @can('reports.view')
            <a href="{{ route('reports.index') }}" wire:navigate class="text-xs text-op-accent hover:text-op-accent-hover">
                Ver relatórios completos →
            </a>
        @endcan
    </div>

    @if ($hasTechnicians === false || $hasCustomers === false)
        <div class="mb-6 rounded-xl border border-op-accent/30 bg-op-accent/5 p-5">
            <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-primary uppercase">Primeiros passos</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center gap-2">
                    <span>{{ $hasCustomers ? '✅' : '⬜' }}</span>
                    <span class="{{ $hasCustomers ? 'text-op-secondary line-through' : '' }}">Cadastre seu primeiro cliente</span>
                    @unless ($hasCustomers)
                        <a href="{{ route('customers.create') }}" wire:navigate class="ml-auto text-xs text-op-accent hover:text-op-accent-hover">Cadastrar →</a>
                    @endunless
                </li>
                <li class="flex items-center gap-2">
                    <span>{{ $hasTechnicians ? '✅' : '⬜' }}</span>
                    <span class="{{ $hasTechnicians ? 'text-op-secondary line-through' : '' }}">Cadastre sua equipe técnica</span>
                    @unless ($hasTechnicians)
                        <a href="{{ route('technicians.create') }}" wire:navigate class="ml-auto text-xs text-op-accent hover:text-op-accent-hover">Cadastrar →</a>
                    @endunless
                </li>
                <li class="flex items-center gap-2">
                    <span>{{ $hasWorkOrders ? '✅' : '⬜' }}</span>
                    <span class="{{ $hasWorkOrders ? 'text-op-secondary line-through' : '' }}">Abra sua primeira ordem de serviço</span>
                    @unless ($hasWorkOrders)
                        <a href="{{ route('work-orders.create') }}" wire:navigate class="ml-auto text-xs text-op-accent hover:text-op-accent-hover">Abrir →</a>
                    @endunless
                </li>
            </ul>
        </div>
    @endif

    @if ($operational)
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">OS no mês</p>
                <p class="mt-1 text-2xl font-semibold">{{ $operational['total'] }}</p>
            </div>
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Dentro do prazo (SLA)</p>
                <p @class(['mt-1 text-2xl font-semibold', 'text-op-success' => $sla['on_time_percentage'] >= 90, 'text-op-warning' => $sla['on_time_percentage'] < 90 && $sla['on_time_percentage'] >= 70, 'text-op-danger' => $sla['on_time_percentage'] < 70])>
                    {{ $sla['on_time_percentage'] }}%
                </p>
            </div>
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Resultado financeiro no mês</p>
                <p @class(['mt-1 text-2xl font-semibold', 'text-op-success' => $financial['net'] >= 0, 'text-op-danger' => $financial['net'] < 0])>
                    R$ {{ number_format($financial['net'], 2, ',', '.') }}
                </p>
            </div>
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Produtos em estoque crítico</p>
                <p @class(['mt-1 text-2xl font-semibold', 'text-op-danger' => $stock['critical_products']->isNotEmpty()])>
                    {{ $stock['critical_products']->count() }}
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <h3 class="mb-4 text-xs font-semibold tracking-wider text-op-secondary uppercase">Ordens de serviço que precisam de atenção</h3>

            @if ($attentionWorkOrders->isEmpty())
                <x-empty-state title="Nenhuma OS com SLA em risco" description="Todas as ordens de serviço em aberto estão dentro do prazo." />
            @else
                <div class="overflow-x-auto rounded-lg border border-op-border">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                            <tr>
                                <th class="px-3 py-2 font-medium">OS</th>
                                <th class="px-3 py-2 font-medium">Cliente</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 font-medium">SLA</th>
                                <th class="px-3 py-2 font-medium">Prazo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-op-border">
                            @foreach ($attentionWorkOrders as $workOrder)
                                <tr wire:key="attention-{{ $workOrder->id }}">
                                    <td class="px-3 py-2">
                                        <a href="{{ route('work-orders.show', $workOrder) }}" wire:navigate class="font-medium hover:text-op-accent-hover">{{ $workOrder->number }}</a>
                                    </td>
                                    <td class="px-3 py-2 text-op-secondary">{{ $workOrder->customer->name }}</td>
                                    <td class="px-3 py-2"><x-status-badge :status="$workOrder->status" /></td>
                                    <td class="px-3 py-2">
                                        <x-badge :variant="$workOrder->sla_status->value === 'breached' ? 'danger' : 'warning'">
                                            {{ $workOrder->sla_status->label() }}
                                        </x-badge>
                                    </td>
                                    <td class="px-3 py-2 text-op-secondary">{{ $workOrder->sla_due_at?->format('d/m/Y H:i') ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @else
        <x-empty-state title="Sem dados para exibir" description="Você não está vinculado a uma empresa com dados operacionais." />
    @endif
</div>
