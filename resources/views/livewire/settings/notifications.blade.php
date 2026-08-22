<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-lg font-semibold">Notificações</h1>
        <p class="text-xs text-op-secondary">Configure a integração de webhook para eventos da operação.</p>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <form wire:submit="save" class="space-y-6 rounded-xl border border-op-border bg-op-card p-6">
        <div>
            <x-label for="webhook_url" value="URL de webhook" />
            <x-input wire:model="webhook_url" id="webhook_url" type="url" placeholder="https://exemplo.com/webhooks/operix" />
            <x-input-error :messages="$errors->get('webhook_url')" />
            <p class="mt-2 text-xs text-op-secondary">
                Eventos enviados: OS concluída (<code>work_order.completed</code>) e SLA violado (<code>work_order.sla_breached</code>).
                Deixe em branco para desativar.
            </p>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-op-border pt-6">
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Salvar</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </x-button>
        </div>
    </form>
</div>
