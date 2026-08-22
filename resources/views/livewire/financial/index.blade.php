<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Financeiro</h1>
            <p class="text-xs text-op-secondary">Lançamentos de receita e custo da empresa, incluindo os vinculados a ordens de serviço.</p>
        </div>

        @can('create', \App\Models\FinancialTransaction::class)
            <x-button wire:click="addNew">Novo lançamento</x-button>
        @endcan
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Receita no período</p>
            <p class="mt-1 text-2xl font-semibold text-op-success">R$ {{ number_format($totals['total_revenue'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Custo no período</p>
            <p class="mt-1 text-2xl font-semibold text-op-danger">R$ {{ number_format($totals['total_cost'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Resultado líquido</p>
            <p @class(['mt-1 text-2xl font-semibold', 'text-op-success' => $totals['net'] >= 0, 'text-op-danger' => $totals['net'] < 0])>
                R$ {{ number_format($totals['net'], 2, ',', '.') }}
            </p>
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-op-border bg-op-card p-5">
            <h3 class="mb-4 text-xs font-semibold tracking-wider text-op-secondary uppercase">Novo lançamento</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="form_type" value="Tipo" />
                    <select wire:model="form_type" id="form_type" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                        <option value="cost">Custo</option>
                        <option value="revenue">Receita</option>
                    </select>
                    <x-input-error :messages="$errors->get('form_type')" />
                </div>

                <div>
                    <x-label for="form_category" value="Categoria (opcional)" />
                    <x-input wire:model="form_category" id="form_category" type="text" />
                    <x-input-error :messages="$errors->get('form_category')" />
                </div>
            </div>

            <div class="mt-4">
                <x-label for="form_description" value="Descrição" />
                <x-input wire:model="form_description" id="form_description" type="text" />
                <x-input-error :messages="$errors->get('form_description')" />
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="form_amount" value="Valor (R$)" />
                    <x-input wire:model="form_amount" id="form_amount" type="number" step="0.01" min="0.01" />
                    <x-input-error :messages="$errors->get('form_amount')" />
                </div>
                <div>
                    <x-label for="form_occurred_at" value="Data" />
                    <x-input wire:model="form_occurred_at" id="form_occurred_at" type="date" />
                    <x-input-error :messages="$errors->get('form_occurred_at')" />
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <x-button variant="secondary" wire:click="cancel">Cancelar</x-button>
                <x-button wire:click="save">Salvar</x-button>
            </div>
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <select wire:model.live="type" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
            <option value="">Todos os tipos</option>
            @foreach ($types as $t)
                <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
        </select>

        <input type="date" wire:model.live="from" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent" />
        <input type="date" wire:model.live="to" class="rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent" />
    </div>

    @if ($transactions->isEmpty())
        <x-empty-state title="Nenhum lançamento encontrado" description="Ajuste os filtros ou registre um novo lançamento." />
    @else
        <div class="overflow-x-auto rounded-xl border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-4 py-3 font-medium">Data</th>
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 font-medium">Descrição</th>
                        <th class="px-4 py-3 font-medium">OS</th>
                        <th class="px-4 py-3 font-medium">Valor</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($transactions as $transaction)
                        <tr wire:key="ledger-{{ $transaction->id }}">
                            <td class="px-4 py-3 text-op-secondary">{{ $transaction->occurred_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$transaction->type === \App\Enums\FinancialTransactionType::Revenue ? 'success' : 'danger'">
                                    {{ $transaction->type->label() }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                {{ $transaction->description }}
                                @if ($transaction->category)
                                    <span class="text-op-secondary">· {{ $transaction->category }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-op-secondary">
                                @if ($transaction->workOrder)
                                    <a href="{{ route('work-orders.show', $transaction->workOrder) }}" wire:navigate class="hover:text-op-accent-hover">
                                        {{ $transaction->workOrder->number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('delete', $transaction)
                                    <button type="button" wire:click="delete({{ $transaction->id }})" wire:confirm="Remover este lançamento?" class="text-xs text-op-secondary hover:text-op-danger">
                                        Remover
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
