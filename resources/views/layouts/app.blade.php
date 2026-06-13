<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-[#f7faf9] text-ink">
        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-line bg-white/90 backdrop-blur">
                    <div class="app-section py-6">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
        @stack('modals')
        <script>
            window.addEventListener('load', () => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                if (!csrf || sessionStorage.getItem('google-calendar-auto-sync-running') === '1') {
                    return;
                }

                sessionStorage.setItem('google-calendar-auto-sync-running', '1');

                fetch('{{ route('google.calendar.auto-sync') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                    keepalive: true,
                }).catch(() => {}).finally(() => {
                    sessionStorage.removeItem('google-calendar-auto-sync-running');
                });
            });
        </script>
        @stack('scripts')
    </body>
</html>
