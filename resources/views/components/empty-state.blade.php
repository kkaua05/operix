@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-op-border px-6 py-16 text-center']) }}>
    <p class="text-sm font-medium text-op-primary">{{ $title }}</p>

    @if ($description)
        <p class="mt-1 max-w-sm text-xs text-op-secondary">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-4">
            {{ $action }}
        </div>
    @endisset
</div>
