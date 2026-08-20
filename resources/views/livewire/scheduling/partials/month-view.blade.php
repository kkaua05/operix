<div class="overflow-hidden rounded-xl border border-op-border">
    <div class="grid grid-cols-7 border-b border-op-border bg-op-surface text-center text-[10px] font-semibold tracking-wider text-op-secondary uppercase">
        @foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $label)
            <div class="py-2">{{ $label }}</div>
        @endforeach
    </div>

    @foreach ($monthWeeks as $week)
        <div class="grid grid-cols-7 border-b border-op-border last:border-b-0">
            @foreach ($week as $day)
                <button
                    type="button"
                    wire:click="goToDay('{{ $day['date']->toDateString() }}')"
                    @class([
                        'flex h-24 flex-col items-start border-r border-op-border p-2 text-left transition last:border-r-0 hover:bg-op-surface',
                        'bg-op-bg text-op-secondary/50' => ! $day['inCurrentMonth'],
                        'ring-1 ring-inset ring-op-accent' => $day['date']->isToday(),
                    ])
                >
                    <span class="text-xs font-medium">{{ $day['date']->format('d') }}</span>

                    @if ($day['count'] > 0)
                        <span class="mt-1 rounded-full bg-op-accent/10 px-2 py-0.5 text-[11px] text-op-accent">
                            {{ $day['count'] }} {{ $day['count'] === 1 ? 'agendamento' : 'agendamentos' }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    @endforeach
</div>
