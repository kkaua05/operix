@props(['status'])

@php
use App\Enums\WorkOrderStatus;

$variant = match ($status) {
    WorkOrderStatus::New, WorkOrderStatus::Triage, WorkOrderStatus::WaitingScheduling => 'default',
    WorkOrderStatus::Scheduled, WorkOrderStatus::Assigned, WorkOrderStatus::EnRoute => 'info',
    WorkOrderStatus::InProgress => 'warning',
    WorkOrderStatus::WaitingCustomer, WorkOrderStatus::WaitingMaterial, WorkOrderStatus::WaitingApproval => 'warning',
    WorkOrderStatus::Resolved, WorkOrderStatus::Completed => 'success',
    WorkOrderStatus::Cancelled => 'danger',
};
@endphp

<x-badge :variant="$variant" {{ $attributes }}>{{ $status->label() }}</x-badge>
