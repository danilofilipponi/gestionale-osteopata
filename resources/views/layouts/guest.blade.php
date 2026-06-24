<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-filipponi.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/logo-filipponi.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased bg-[#f4f8f7]">
        <div class="min-h-screen grid place-items-center px-4 py-8">
            <div class="mb-6 text-center">
                <a href="/" class="inline-flex flex-col items-center gap-3">
                    <img src="{{ asset('images/logo-filipponi.png') }}" alt="Studio Osteopatico Dott. Filipponi Danilo" class="h-16 w-auto object-contain">
                    <span class="text-center">
                        <span class="block text-xl font-bold text-ink">Studio Osteopatico Dott. Filipponi Danilo</span>
                        <span class="block text-[10px] font-bold uppercase tracking-[.18em] text-muted">Riabilitazione - Osteopatia</span>
                    </span>
                </a>
            </div>

            <div class="w-full sm:max-w-md overflow-hidden rounded-2xl border border-line bg-white px-7 py-7 shadow-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
