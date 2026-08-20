@props(['status', 'percentage' => null])

@php
use App\Enums\SlaStatus;

$barColor = match ($status) {
    SlaStatus::Normal => 'bg-op-success',
    SlaStatus::Warning => 'bg-op-warning',
    SlaStatus::Critical, SlaStatus::Breached => 'bg-op-danger',
    SlaStatus::Paused => 'bg-op-secondary',
};

$displayPercentage = $percentage !== null ? min(100, max(0, $percentage)) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="mb-1 flex items-center justify-between text-xs">
        <x-badge :variant="match ($status) {
            SlaStatus::Normal => 'success',
            SlaStatus::Warning => 'warning',
            SlaStatus::Critical, SlaStatus::Breached => 'danger',
            SlaStatus::Paused => 'default',
        }">{{ $status->label() }}</x-badge>

        @if ($percentage !== null)
            <span class="text-op-secondary">{{ number_format($displayPercentage, 0) }}%</span>
        @endif
    </div>

    <div class="h-1.5 w-full overflow-hidden rounded-full bg-op-surface">
        <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $displayPercentage }}%"></div>
    </div>
</div>
