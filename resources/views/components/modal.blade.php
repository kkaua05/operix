@props(['show' => false, 'maxWidth' => 'md'])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
][$maxWidth] ?? 'sm:max-w-md';
@endphp

<div
    x-show="{{ $show }}"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div class="absolute inset-0 bg-black/60" x-on:click="{{ $show }} = false"></div>

    <div
        x-show="{{ $show }}"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        {{ $attributes->merge(['class' => "relative w-full $maxWidthClass rounded-xl border border-op-border bg-op-card p-6 shadow-2xl"]) }}
    >
        {{ $slot }}
    </div>
</div>
