@props(['value' => null])

<label {{ $attributes->merge(['class' => 'mb-1.5 block text-xs font-medium text-op-secondary']) }}>
    {{ $value ?? $slot }}
</label>
