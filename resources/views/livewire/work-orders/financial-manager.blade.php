<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Receita total</p>
            <p class="mt-1 text-2xl font-semibold text-op-success">R$ {{ number_format($summary['total_revenue'], 2, ',', '.') }}</p>
            <p class="mt-1 text-xs text-op-secondary">
                Itens: R$ {{ number_format($summary['revenue_items'], 2, ',', '.') }}
                · Manual: R$ {{ number_format($summary['revenue_manual'], 2, ',', '.') }}
            </p>
        </div>

        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Custo total</p>
            <p class="mt-1 text-2xl font-semibold text-op-danger">R$ {{ number_format($summary['total_cost'], 2, ',', '.') }}</p>
            <p class="mt-1 text-xs text-op-secondary">
                Materiais: R$ {{ number_format($summary['cost_materials'], 2, ',', '.') }}
                · Manual: R$ {{ number_format($summary['cost_manual'], 2, ',', '.') }}
            </p>
        </div>

        <div class="rounded-xl border border-op-border bg-op-card p-5">
            <p class="text-xs text-op-secondary">Margem</p>
            <p @class(['mt-1 text-2xl font-semibold', 'text-op-success' => $summary['margin'] >= 0, 'text-op-danger' => $summary['margin'] < 0])>
                R$ {{ number_format($summary['margin'], 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-op-secondary">{{ number_format($summary['margin_percentage'], 1, ',', '.') }}% sobre a receita</p>
        </div>
    </div>

    <div class="rounded-xl border border-op-border bg-op-card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xs font-semibold tracking-wider text-op-secondary uppercase">Lançamentos manuais</h3>
            @can('create', \App\Models\FinancialTransaction::class)
                <button type="button" wire:click="addNew" class="text-xs text-op-accent hover:text-op-accent-hover">
                    + Novo lançamento
                </button>
            @endcan
        </div>

        @if ($showForm)
            <form wire:submit="save" class="mb-4 space-y-4 rounded-lg border border-op-border bg-op-surface p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-label for="type" value="Tipo" />
                        <select wire:model="type" id="type" class="block w-full rounded-lg border border-op-border bg-op-card px-3 py-2 text-sm text-op-primary focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent">
                            <option value="cost">Custo</option>
                            <option value="revenue">Receita</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" />
                    </div>

                    <div>
                        <x-label for="category" value="Categoria (opcional)" />
                        <x-input wire:model="category" id="category" type="text" placeholder="Deslocamento, comissão..." />
                        <x-input-error :messages="$errors->get('category')" />
                    </div>
                </div>

                <div>
                    <x-label for="description" value="Descrição" />
                    <x-input wire:model="description" id="description" type="text" />
                    <x-input-error :messages="$errors->get('description')" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-label for="amount" value="Valor (R$)" />
                        <x-input wire:model="amount" id="amount" type="number" step="0.01" min="0.01" />
                        <x-input-error :messages="$errors->get('amount')" />
                    </div>

                    <div>
                        <x-label for="occurred_at" value="Data" />
                        <x-input wire:model="occurred_at" id="occurred_at" type="date" />
                        <x-input-error :messages="$errors->get('occurred_at')" />
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <x-button type="button" variant="secondary" wire:click="cancel">Cancelar</x-button>
                    <x-button type="submit">Salvar lançamento</x-button>
                </div>
            </form>
        @endif

        @if ($transactions->isEmpty() && ! $showForm)
            <x-empty-state title="Nenhum lançamento manual" description="Itens faturados e materiais consumidos já entram automaticamente no cálculo acima." />
        @else
            <div class="overflow-x-auto rounded-lg border border-op-border">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                        <tr>
                            <th class="px-3 py-2 font-medium">Data</th>
                            <th class="px-3 py-2 font-medium">Tipo</th>
                            <th class="px-3 py-2 font-medium">Descrição</th>
                            <th class="px-3 py-2 font-medium">Valor</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-op-border">
                        @foreach ($transactions as $transaction)
                            <tr wire:key="transaction-{{ $transaction->id }}">
                                <td class="px-3 py-2 text-op-secondary">{{ $transaction->occurred_at->format('d/m/Y') }}</td>
                                <td class="px-3 py-2">
                                    <x-badge :variant="$transaction->type === \App\Enums\FinancialTransactionType::Revenue ? 'success' : 'danger'">
                                        {{ $transaction->type->label() }}
                                    </x-badge>
                                </td>
                                <td class="px-3 py-2">
                                    {{ $transaction->description }}
                                    @if ($transaction->category)
                                        <span class="text-op-secondary">· {{ $transaction->category }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">
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
        @endif
    </div>
</div>
