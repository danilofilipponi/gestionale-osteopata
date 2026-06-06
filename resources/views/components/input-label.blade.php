@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted']) }}>
    {{ $value ?? $slot }}
</label>
