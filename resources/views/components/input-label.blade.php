@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-habits-ink']) }}>
    {{ $value ?? $slot }}
</label>
