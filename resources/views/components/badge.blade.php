@props(['variant' => 'default'])

@php
$variants = [
    'default' => 'bg-op-surface text-op-secondary border-op-border',
    'success' => 'bg-op-success/10 text-op-success border-op-success/30',
    'warning' => 'bg-op-warning/10 text-op-warning border-op-warning/30',
    'danger' => 'bg-op-danger/10 text-op-danger border-op-danger/30',
    'info' => 'bg-op-info/10 text-op-info border-op-info/30',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium '.($variants[$variant] ?? $variants['default'])]) }}>
    {{ $slot }}
</span>
