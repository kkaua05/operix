@props(['variant' => 'primary'])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-50';

$variants = [
    'primary' => 'bg-op-primary text-op-bg hover:bg-op-accent-hover hover:text-op-bg',
    'secondary' => 'border border-op-border bg-transparent text-op-primary hover:bg-op-surface',
    'ghost' => 'text-op-secondary hover:text-op-primary',
];
@endphp

<button {{ $attributes->merge(['class' => $base.' '.($variants[$variant] ?? $variants['primary'])]) }}>
    {{ $slot }}
</button>
