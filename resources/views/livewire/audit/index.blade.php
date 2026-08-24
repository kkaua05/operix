<div>
    <div class="mb-6">
        <h1 class="text-lg font-semibold">Auditoria</h1>
        <p class="text-xs text-op-secondary">Trilha de ações críticas: autenticação, gestão de usuários, financeiro e exclusões.</p>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="action" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
            <option value="">Todas as ações</option>
            @foreach ($availableActions as $a)
                <option value="{{ $a }}">{{ $a }}</option>
            @endforeach
        </select>
    </div>

    @if ($logs->isEmpty())
        <x-empty-state title="Nenhum registro de auditoria encontrado" description="Ações críticas do sistema aparecerão aqui conforme forem realizadas." />
    @else
        <div class="overflow-x-auto rounded-xl border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-4 py-3 font-medium">Data</th>
                        <th class="px-4 py-3 font-medium">Ação</th>
                        <th class="px-4 py-3 font-medium">Usuário</th>
                        <th class="px-4 py-3 font-medium">Registro</th>
                        <th class="px-4 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($logs as $log)
                        <tr wire:key="audit-{{ $log->id }}">
                            <td class="px-4 py-3 text-op-secondary">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3"><x-badge>{{ $log->action }}</x-badge></td>
                            <td class="px-4 py-3 text-op-secondary">{{ $log->user?->name ?: 'Sistema/anônimo' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $log->ip_address ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
