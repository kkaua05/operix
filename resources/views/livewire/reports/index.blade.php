<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Relatórios</h1>
            <p class="text-xs text-op-secondary">Indicadores operacionais, de SLA, técnicos, financeiros e de estoque.</p>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <input type="date" wire:model.live="from" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent" />
        <span class="text-xs text-op-secondary">até</span>
        <input type="date" wire:model.live="to" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent" />
    </div>

    <div class="mb-6 border-b border-op-border">
        <nav class="-mb-px flex gap-6 overflow-x-auto text-sm">
            @foreach ([
                'operacional' => 'Operacional',
                'sla' => 'SLA',
                'tecnicos' => 'Técnicos',
                'financeiro' => 'Financeiro',
                'estoque' => 'Estoque',
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

    @if ($activeTab === 'operacional')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Ordens de serviço no período</p>
                <p class="mt-1 text-2xl font-semibold">{{ $operational['total'] }}</p>
            </div>
            <div class="rounded-xl border border-op-border bg-op-card p-5 sm:col-span-2">
                <p class="text-xs text-op-secondary">Tempo médio de resolução (concluídas)</p>
                <p class="mt-1 text-2xl font-semibold">{{ $operational['avg_resolution_hours'] }}h</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Por status</h3>
                @if ($operational['by_status']->isEmpty())
                    <x-empty-state title="Nenhuma OS no período" />
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($operational['by_status'] as $status => $count)
                            <li class="flex justify-between border-b border-op-border pb-2 last:border-0 last:pb-0">
                                <span>{{ \App\Enums\WorkOrderStatus::from($status)->label() }}</span>
                                <span class="font-medium">{{ $count }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Por prioridade</h3>
                @if ($operational['by_priority']->isEmpty())
                    <x-empty-state title="Nenhuma OS no período" />
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($operational['by_priority'] as $priority => $count)
                            <li class="flex justify-between border-b border-op-border pb-2 last:border-0 last:pb-0">
                                <span>{{ \App\Enums\WorkOrderPriority::from($priority)->label() }}</span>
                                <span class="font-medium">{{ $count }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @elseif ($activeTab === 'sla')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">OS concluídas com SLA no período</p>
                <p class="mt-1 text-2xl font-semibold">{{ $sla['total'] }}</p>
            </div>
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Violações de SLA</p>
                <p class="mt-1 text-2xl font-semibold text-op-danger">{{ $sla['breached'] }}</p>
            </div>
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Dentro do prazo</p>
                <p class="mt-1 text-2xl font-semibold text-op-success">{{ $sla['on_time_percentage'] }}%</p>
            </div>
        </div>
    @elseif ($activeTab === 'tecnicos')
        <div class="mb-4 flex justify-end">
            <x-button wire:click="exportTechniciansCsv">Exportar CSV</x-button>
        </div>

        @if ($technicians->isEmpty())
            <x-empty-state title="Nenhuma OS concluída no período" />
        @else
            <div class="overflow-x-auto rounded-xl border border-op-border">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                        <tr>
                            <th class="px-4 py-3 font-medium">Técnico</th>
                            <th class="px-4 py-3 font-medium">OS concluídas</th>
                            <th class="px-4 py-3 font-medium">Tempo médio</th>
                            <th class="px-4 py-3 font-medium">Avaliação média</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-op-border">
                        @foreach ($technicians as $row)
                            <tr wire:key="tech-{{ $row['technician']->id }}">
                                <td class="px-4 py-3 font-medium">{{ $row['technician']->name }}</td>
                                <td class="px-4 py-3 text-op-secondary">{{ $row['completed_count'] }}</td>
                                <td class="px-4 py-3 text-op-secondary">{{ $row['avg_resolution_hours'] }}h</td>
                                <td class="px-4 py-3 text-op-secondary">{{ $row['avg_rating'] > 0 ? $row['avg_rating'].' ★' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @elseif ($activeTab === 'financeiro')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Receita no período</p>
                <p class="mt-1 text-2xl font-semibold text-op-success">R$ {{ number_format($financial['total_revenue'], 2, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Custo no período</p>
                <p class="mt-1 text-2xl font-semibold text-op-danger">R$ {{ number_format($financial['total_cost'], 2, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Resultado líquido</p>
                <p @class(['mt-1 text-2xl font-semibold', 'text-op-success' => $financial['net'] >= 0, 'text-op-danger' => $financial['net'] < 0])>
                    R$ {{ number_format($financial['net'], 2, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('financial.index') }}" wire:navigate class="text-xs text-op-accent hover:text-op-accent-hover">
                Ver todos os lançamentos →
            </a>
        </div>
    @elseif ($activeTab === 'estoque')
        <div class="mb-4 flex items-center justify-between">
            <div class="rounded-xl border border-op-border bg-op-card p-5">
                <p class="text-xs text-op-secondary">Valor total em estoque</p>
                <p class="mt-1 text-2xl font-semibold">R$ {{ number_format($stock['total_stock_value'], 2, ',', '.') }}</p>
            </div>

            @if ($stock['critical_products']->isNotEmpty())
                <x-button wire:click="exportStockCsv">Exportar CSV</x-button>
            @endif
        </div>

        <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Produtos com estoque crítico</h3>

        @if ($stock['critical_products']->isEmpty())
            <x-empty-state title="Nenhum produto com estoque crítico" description="Todos os produtos estão acima do estoque mínimo." />
        @else
            <div class="overflow-x-auto rounded-xl border border-op-border">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                        <tr>
                            <th class="px-4 py-3 font-medium">Produto</th>
                            <th class="px-4 py-3 font-medium">SKU</th>
                            <th class="px-4 py-3 font-medium">Estoque atual</th>
                            <th class="px-4 py-3 font-medium">Estoque mínimo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-op-border">
                        @foreach ($stock['critical_products'] as $product)
                            <tr wire:key="stock-{{ $product->id }}">
                                <td class="px-4 py-3">
                                    <a href="{{ route('inventory.products.show', $product) }}" wire:navigate class="font-medium hover:text-op-accent-hover">{{ $product->name }}</a>
                                </td>
                                <td class="px-4 py-3 text-op-secondary">{{ $product->sku }}</td>
                                <td class="px-4 py-3 text-op-danger">{{ rtrim(rtrim((string) $product->stock_quantity, '0'), '.') }} {{ $product->unit }}</td>
                                <td class="px-4 py-3 text-op-secondary">{{ rtrim(rtrim((string) $product->min_stock, '0'), '.') }} {{ $product->unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
