@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm text-op-primary placeholder:text-op-secondary/60 focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent disabled:cursor-not-allowed disabled:opacity-50',
]) !!}>
