@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 border-sage bg-mist py-2 pe-4 ps-3 text-start text-base font-bold text-sage transition duration-150 ease-in-out focus:outline-none'
            : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-bold text-muted transition duration-150 ease-in-out hover:border-line hover:bg-mist hover:text-ink focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
