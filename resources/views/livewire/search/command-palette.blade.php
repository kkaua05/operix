<div
    x-data="{ open: false }"
    x-on:keydown.window="
        if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
            event.preventDefault();
            open = true;
            $nextTick(() => $refs.searchInput.focus());
        } else if (event.key === 'Escape') {
            open = false;
        }
    "
>
    <button
        type="button"
        x-on:click="open = true; $nextTick(() => $refs.searchInput.focus())"
        class="flex w-full items-center gap-2 rounded-lg border border-op-border bg-op-surface px-3 py-1.5 text-xs text-op-secondary hover:border-op-accent/40 sm:w-56"
    >
        <span>🔍</span>
        <span class="flex-1 text-left">Buscar...</span>
        <kbd class="rounded border border-op-border bg-op-card px-1.5 py-0.5 text-[10px]">Ctrl K</kbd>
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center p-4 pt-[15vh]">
        <div class="absolute inset-0 bg-black/70" x-on:click="open = false"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative w-full max-w-lg rounded-xl border border-op-border bg-op-card shadow-2xl"
        >
            <div class="border-b border-op-border p-3">
                <input
                    x-ref="searchInput"
                    wire:model.live.debounce.200ms="query"
                    type="text"
                    placeholder="Buscar OS, clientes, técnicos..."
                    class="w-full bg-transparent px-2 py-1.5 text-sm text-op-primary placeholder:text-op-secondary/60 focus:outline-none"
                    x-on:keydown.escape="open = false"
                />
            </div>

            <div class="max-h-96 overflow-y-auto p-2">
                @if ($query === '')
                    <p class="px-3 py-6 text-center text-xs text-op-secondary">Digite para buscar em ordens de serviço, clientes e técnicos.</p>
                @elseif (! $hasResults)
                    <p class="px-3 py-6 text-center text-xs text-op-secondary">Nenhum resultado para "{{ $query }}".</p>
                @else
                    @if ($workOrders->isNotEmpty())
                        <p class="px-3 py-1 text-[10px] font-semibold tracking-wider text-op-secondary uppercase">Ordens de serviço</p>
                        @foreach ($workOrders as $workOrder)
                            <a href="{{ route('work-orders.show', $workOrder) }}" wire:navigate x-on:click="open = false" class="block rounded-lg px-3 py-2 text-sm hover:bg-op-surface">
                                <span class="font-medium">{{ $workOrder->number }}</span>
                                <span class="text-op-secondary"> · {{ $workOrder->customer->name }}</span>
                            </a>
                        @endforeach
                    @endif

                    @if ($customers->isNotEmpty())
                        <p class="mt-2 px-3 py-1 text-[10px] font-semibold tracking-wider text-op-secondary uppercase">Clientes</p>
                        @foreach ($customers as $customer)
                            <a href="{{ route('customers.show', $customer) }}" wire:navigate x-on:click="open = false" class="block rounded-lg px-3 py-2 text-sm hover:bg-op-surface">
                                {{ $customer->name }}
                            </a>
                        @endforeach
                    @endif

                    @if ($technicians->isNotEmpty())
                        <p class="mt-2 px-3 py-1 text-[10px] font-semibold tracking-wider text-op-secondary uppercase">Técnicos</p>
                        @foreach ($technicians as $technician)
                            <a href="{{ route('technicians.show', $technician) }}" wire:navigate x-on:click="open = false" class="block rounded-lg px-3 py-2 text-sm hover:bg-op-surface">
                                {{ $technician->name }}
                            </a>
                        @endforeach
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
