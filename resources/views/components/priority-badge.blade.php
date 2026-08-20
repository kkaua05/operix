@props(['priority'])

@php
use App\Enums\WorkOrderPriority;

$variant = match ($priority) {
    WorkOrderPriority::Low => 'default',
    WorkOrderPriority::Medium => 'info',
    WorkOrderPriority::High => 'warning',
    WorkOrderPriority::Urgent, WorkOrderPriority::Critical => 'danger',
};
@endphp

<x-badge :variant="$variant" {{ $attributes }}>{{ $priority->label() }}</x-badge>
