@props(['href', 'active' => false])

<a href="{{ $href }}" wire:navigate
   {{ $attributes->merge(['class' => 'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition '
        .($active
            ? 'bg-op-surface text-op-primary font-medium'
            : 'text-op-secondary hover:bg-op-surface hover:text-op-primary')]) }}>
    {{ $slot }}
</a>
