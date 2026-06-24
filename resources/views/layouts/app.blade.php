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
    <body class="font-sans antialiased bg-[#f7faf9] text-ink">
        <div x-data="{ sidebarExpanded: localStorage.getItem('sidebar-expanded') === '1' }"
             @sidebar-toggle.window="sidebarExpanded = $event.detail.expanded"
             :class="sidebarExpanded ? 'md:pl-64' : 'md:pl-20'"
             class="min-h-screen transition-all duration-200">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-line bg-white/90 backdrop-blur">
                    <div class="app-section flex items-center gap-4 py-5">
                        <img src="{{ asset('images/logo-filipponi.png') }}" alt="Danilo Filipponi" class="hidden h-12 w-auto shrink-0 object-contain sm:block">
                        <div class="min-w-0 flex-1">
                            {{ $header }}
                        </div>
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
        @stack('modals')
        @stack('scripts')
    </body>
</html>
