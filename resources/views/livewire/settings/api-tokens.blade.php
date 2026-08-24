<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">Tokens de API</h1>
        <p class="text-xs text-op-secondary">
            Gere tokens de acesso pessoal para integrar sistemas externos com a API do Operix (<code>/api/v1</code>).
        </p>
    </div>

    @if ($plainTextToken)
        <div class="mb-6 rounded-xl border border-op-accent/40 bg-op-accent/10 p-5">
            <p class="text-xs font-semibold text-op-primary">Copie seu token agora — ele não será mostrado novamente.</p>
            <code class="mt-2 block break-all rounded-lg bg-op-surface px-3 py-2 text-xs">{{ $plainTextToken }}</code>
            <div class="mt-3 flex justify-end">
                <x-button variant="secondary" wire:click="dismissToken">Ok, copiei</x-button>
            </div>
        </div>
    @endif

    <form wire:submit="create" class="mb-6 flex items-end gap-3 rounded-xl border border-op-border bg-op-card p-5">
        <div class="flex-1">
            <x-label for="name" value="Nome do token" />
            <x-input wire:model="name" id="name" type="text" placeholder="Ex.: Integração ERP" />
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <x-button type="submit">Gerar token</x-button>
    </form>

    @if ($tokens->isEmpty())
        <x-empty-state title="Nenhum token gerado" description="Crie um token para autenticar chamadas à API." />
    @else
        <div class="overflow-x-auto rounded-xl border border-op-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-op-border bg-op-surface text-xs text-op-secondary">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nome</th>
                        <th class="px-4 py-3 font-medium">Criado em</th>
                        <th class="px-4 py-3 font-medium">Último uso</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-op-border">
                    @foreach ($tokens as $token)
                        <tr wire:key="token-{{ $token->id }}">
                            <td class="px-4 py-3 font-medium">{{ $token->name }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $token->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-op-secondary">{{ $token->last_used_at?->format('d/m/Y H:i') ?: 'Nunca' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="revoke({{ $token->id }})" wire:confirm="Revogar este token?" class="text-xs text-op-secondary hover:text-op-danger">
                                    Revogar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
