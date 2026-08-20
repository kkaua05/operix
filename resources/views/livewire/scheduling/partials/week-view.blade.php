<div class="grid grid-cols-1 gap-3 sm:grid-cols-7">
    @foreach ($weekDays as $day)
        <button
            type="button"
            wire:click="goToDay('{{ $day['date']->toDateString() }}')"
            @class([
                'flex flex-col rounded-xl border p-3 text-left transition hover:border-op-accent',
                'border-op-accent bg-op-surface' => $day['date']->isToday(),
                'border-op-border' => ! $day['date']->isToday(),
            ])
        >
            <span class="text-[10px] font-semibold tracking-wider text-op-secondary uppercase">
                {{ $day['date']->translatedFormat('D') }}
            </span>
            <span class="text-sm font-medium">{{ $day['date']->format('d/m') }}</span>

            <div class="mt-2 space-y-1">
                @forelse ($day['appointments']->take(3) as $appointment)
                    <div class="truncate rounded bg-op-surface px-1.5 py-1 text-[11px] text-op-secondary">
                        {{ $appointment->scheduled_start->format('H:i') }} {{ $appointment->workOrder->number }}
                    </div>
                @empty
                    <p class="text-[11px] text-op-secondary">—</p>
                @endforelse

                @if ($day['appointments']->count() > 3)
                    <p class="text-[11px] text-op-secondary">+{{ $day['appointments']->count() - 3 }} mais</p>
                @endif
            </div>
        </button>
    @endforeach
</div>
