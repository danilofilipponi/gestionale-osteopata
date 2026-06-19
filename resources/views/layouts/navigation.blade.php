<aside
    x-data="{ expanded: localStorage.getItem('sidebar-expanded') === '1' }"
    x-effect="localStorage.setItem('sidebar-expanded', expanded ? '1' : '0')"
    :class="expanded ? 'w-64' : 'w-20'"
    class="fixed inset-y-0 left-0 z-40 hidden border-r border-line bg-white/95 shadow-sm backdrop-blur transition-all duration-200 md:flex md:flex-col"
>
    <div class="flex h-20 items-center gap-3 border-b border-line px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3">
            <img src="{{ asset('images/logo-filipponi.png') }}" alt="Danilo Filipponi" class="h-11 w-11 shrink-0 rounded-xl object-contain">
            <span x-show="expanded" x-transition class="min-w-0">
                <span class="block truncate text-sm font-black leading-tight text-ink">Danilo Filipponi</span>
                <span class="block truncate text-[10px] font-bold uppercase tracking-[.14em] text-muted">Riabilitazione - Osteopatia</span>
            </span>
        </a>
        <button
            type="button"
            @click="expanded = ! expanded; $dispatch('sidebar-toggle', { expanded })"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-line bg-white text-sage shadow-sm hover:bg-mist"
            :title="expanded ? 'Compatta menu' : 'Espandi menu'"
        >
            <svg x-show="! expanded" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 6h16" />
                <path d="M4 12h16" />
                <path d="M4 18h16" />
            </svg>
            <svg x-show="expanded" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </button>
    </div>

    @php
        $items = [
            ['label' => 'Dashboard', 'route' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'icon' => 'dashboard'],
            ['label' => 'Pazienti', 'route' => route('patients.index'), 'active' => request()->routeIs('patients.*'), 'icon' => 'patients'],
            ['label' => 'Agenda', 'route' => route('appointments.index'), 'active' => request()->routeIs('appointments.*'), 'icon' => 'agenda'],
            ['label' => 'Fatture', 'route' => route('invoices.index'), 'active' => request()->routeIs('invoices.*'), 'icon' => 'invoices'],
            ['label' => 'Contabilita', 'route' => route('accounting.index'), 'active' => request()->routeIs('accounting.*'), 'icon' => 'accounting'],
        ];
    @endphp

    <nav class="flex-1 space-y-1 px-3 py-5">
        @foreach ($items as $item)
            <a
                href="{{ $item['route'] }}"
                class="group flex h-12 items-center gap-3 rounded-xl px-3 text-sm font-bold transition {{ $item['active'] ? 'bg-sage text-white shadow-sm' : 'text-muted hover:bg-mist hover:text-ink' }}"
                title="{{ $item['label'] }}"
            >
                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                    @switch($item['icon'])
                        @case('dashboard')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                            @break
                        @case('patients')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            @break
                        @case('agenda')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>
                            @break
                        @case('invoices')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/></svg>
                            @break
                        @default
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    @endswitch
                </span>
                <span x-show="expanded" x-transition class="truncate">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="border-t border-line p-3">
        <a href="{{ route('settings.edit') }}" class="flex h-11 items-center gap-3 rounded-xl px-3 text-sm font-bold text-muted hover:bg-mist hover:text-ink" title="Impostazioni">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 16 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.2.36.6.6 1 .6h.6a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1.4Z"/></svg>
            <span x-show="expanded" x-transition>Impostazioni</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="mt-1 flex h-11 items-center gap-3 rounded-xl px-3 text-sm font-bold text-muted hover:bg-mist hover:text-ink" title="Account">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            <span x-show="expanded" x-transition class="truncate">{{ Auth::user()->name }}</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="flex h-11 w-full items-center gap-3 rounded-xl px-3 text-sm font-bold text-red-700 hover:bg-red-50" title="Esci">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                <span x-show="expanded" x-transition>Esci</span>
            </button>
        </form>
    </div>
</aside>

<nav x-data="{ open: false }" class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur md:hidden">
    <div class="app-section">
        <div class="flex h-16 items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 font-semibold text-ink">
                <img src="{{ asset('images/logo-filipponi.png') }}" alt="Danilo Filipponi" class="h-11 w-11 rounded-xl object-contain">
                <span>
                    <span class="block leading-tight">Danilo Filipponi</span>
                    <span class="block text-[10px] font-bold uppercase tracking-[.16em] text-muted">Riabilitazione - Osteopatia</span>
                </span>
            </a>
            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-xl p-2 text-muted transition hover:bg-mist hover:text-ink focus:outline-none">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden">
        <div class="space-y-1 px-4 pb-4">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*')">Pazienti</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">Agenda</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">Fatture</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.index')" :active="request()->routeIs('accounting.*')">Contabilita</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('settings.edit')">Impostazioni</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('profile.edit')">Account</x-responsive-nav-link>
        </div>
    </div>
</nav>
