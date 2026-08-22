<div x-data="{ open: false }" class="relative" x-on:click.outside="open = false" wire:poll.30s>
    <button type="button" x-on:click="open = ! open" class="relative text-op-secondary hover:text-op-primary" aria-label="Notificações">
        <span class="text-lg">🔔</span>
        @if ($unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-op-danger px-1 text-[10px] font-semibold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute right-0 z-50 mt-2 w-80 rounded-xl border border-op-border bg-op-card shadow-lg"
    >
        <div class="flex items-center justify-between border-b border-op-border px-4 py-3">
            <span class="text-xs font-semibold tracking-wider text-op-secondary uppercase">Notificações</span>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs text-op-accent hover:text-op-accent-hover">
                    Marcar todas como lidas
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                <div wire:key="notif-{{ $notification->id }}" @class(['border-b border-op-border px-4 py-3 last:border-0', 'bg-op-surface/50' => ! $notification->read_at])>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-medium">{{ $notification->data['title'] ?? 'Notificação' }}</p>
                            <p class="mt-0.5 text-xs text-op-secondary">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="mt-1 text-[10px] text-op-secondary">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        @if (! $notification->read_at)
                            <button type="button" wire:click="markAsRead('{{ $notification->id }}')" class="shrink-0 text-[10px] text-op-accent hover:text-op-accent-hover">
                                Marcar lida
                            </button>
                        @endif
                    </div>

                    @if (! empty($notification->data['url']))
                        <a href="{{ $notification->data['url'] }}" wire:navigate class="mt-1 inline-block text-[11px] text-op-accent hover:text-op-accent-hover">
                            Ver detalhes →
                        </a>
                    @endif
                </div>
            @empty
                <p class="px-4 py-6 text-center text-xs text-op-secondary">Nenhuma notificação por aqui.</p>
            @endforelse
        </div>
    </div>
</div>
