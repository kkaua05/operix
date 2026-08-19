@props(['variant' => 'info'])

@php
$variants = [
    'info' => 'border-op-info/30 bg-op-info/10 text-op-info',
    'success' => 'border-op-success/30 bg-op-success/10 text-op-success',
    'danger' => 'border-op-danger/30 bg-op-danger/10 text-op-danger',
];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border px-3 py-2 text-xs '.($variants[$variant] ?? $variants['info'])]) }}>
    {{ $slot }}
</div>
