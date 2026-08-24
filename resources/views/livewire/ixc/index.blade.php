<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold">IXC — Ordens de serviço</h1>
            <p class="text-xs text-op-secondary">
                Dados sincronizados do painel do IXC (sem API disponível — leia
                <code>scripts/ixc-sync/README.md</code> para calibrar o scraper).
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs text-op-secondary">
                Última sincronização: {{ $lastSyncedAt ? \Illuminate\Support\Carbon::parse($lastSyncedAt)->diffForHumans() : 'nunca' }}
            </span>
            <x-button wire:click="syncNow" wire:loading.attr="disabled" wire:target="syncNow">
                <span wire:loading.remove wire:target="syncNow">Sincronizar agora</span>
                <span wire:loading wire:target="syncNow">Sincronizando...</span>
            </x-button>
        </div>
    </div>

    @if (! $ixcEnabled)
        <x-alert variant="danger" class="mb-4">
            A integração está desativada (<code>IXC_SYNC_ENABLED=false</code>). O botão "Sincronizar agora"
            ainda funciona para testes, mas o comando agendado não roda sozinho até isso ser ligado no <code>.env</code>.
        </x-alert>
    @endif

    @if ($syncMessage)
        <x-alert :variant="$syncFailed ? 'danger' : 'success'" class="mb-4">{{ $syncMessage }}</x-alert>
    @endif

    @if ($ordersByTechnician->isEmpty())
        <x-empty-state
            title="Nenhuma OS sincronizada ainda"
            description="Clique em “Sincronizar agora” ou aguarde o próximo ciclo automático (a cada 2 minutos)."
        />
    @else
        <div class="space-y-6">
            @foreach ($ordersByTechnician as $technician => $orders)
                <div class="rounded-xl border border-op-border bg-op-card p-5">
                    <h3 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">
                        {{ $technician }} ({{ $orders->count() }})
                    </h3>

                    <div class="overflow-x-auto rounded-lg border border-op-border">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                                <tr>
                                    <th class="px-3 py-2 font-medium">OS (IXC)</th>
                                    <th class="px-3 py-2 font-medium">Cliente</th>
                                    <th class="px-3 py-2 font-medium">Assunto</th>
                                    <th class="px-3 py-2 font-medium">Endereço</th>
                                    <th class="px-3 py-2 font-medium">Status</th>
                                    <th class="px-3 py-2 font-medium">Agendado para</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-op-border">
                                @foreach ($orders as $order)
                                    <tr wire:key="ixc-os-{{ $order->id }}">
                                        <td class="px-3 py-2 text-op-secondary">#{{ $order->external_id }}</td>
                                        <td class="px-3 py-2">{{ $order->customer_name ?: '—' }}</td>
                                        <td class="px-3 py-2 text-op-secondary">{{ $order->subject ?: '—' }}</td>
                                        <td class="px-3 py-2 text-op-secondary">{{ $order->address ?: '—' }}</td>
                                        <td class="px-3 py-2">
                                            @if ($order->status)
                                                <x-badge>{{ $order->status }}</x-badge>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-op-secondary">
                                            {{ $order->scheduled_start?->format('d/m/Y H:i') ?: 'Não agendada' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
