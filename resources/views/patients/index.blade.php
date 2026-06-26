<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pazienti</h2>
            <a href="{{ route('patients.create') }}" class="inline-flex items-center rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#4f7f75]">
                Nuovo paziente
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section">
            <form method="GET" class="mb-4 flex flex-col gap-3 sm:mb-6 sm:flex-row">
                <input name="search" value="{{ request('search') }}" class="app-field w-full" placeholder="Cerca per nome, telefono o email">
                <button class="inline-flex items-center justify-center gap-2 rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                    <svg class="h-4 w-4 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    Cerca
                </button>
            </form>

            <div class="space-y-3 md:hidden">
                @forelse ($patients as $patient)
                    <a href="{{ route('patients.show', $patient) }}" class="app-card block p-4 transition hover:bg-mist">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line bg-mist text-sage">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="truncate font-bold text-ink">{{ $patient->list_name }}</h3>
                                        <p class="mt-0.5 truncate text-xs text-muted">{{ $patient->fiscal_code ?: 'Codice fiscale non inserito' }}</p>
                                    </div>
                                    <svg class="mt-1 h-5 w-5 shrink-0 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-line pt-3 text-xs text-gray-700">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z" />
                                        </svg>
                                        <span class="truncate">{{ $patient->phone ?: 'Telefono n.d.' }}</span>
                                    </span>
                                    <span class="flex min-w-0 items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect width="18" height="18" x="3" y="4" rx="2" />
                                            <path d="M16 2v4M8 2v4M3 10h18" />
                                        </svg>
                                        <span>{{ $patient->birth_date?->format('d/m/Y') ?: 'Nascita n.d.' }}</span>
                                    </span>
                                    <span class="col-span-2 flex min-w-0 items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect width="20" height="16" x="2" y="4" rx="2" />
                                            <path d="m22 7-10 5L2 7" />
                                        </svg>
                                        <span class="truncate">{{ $patient->email ?: 'Email non inserita' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="app-card px-5 py-10 text-center">
                        <p class="text-gray-600">Nessun paziente trovato.</p>
                        <a href="{{ route('patients.create') }}" class="mt-4 inline-flex rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#4f7f75]">Crea il primo paziente</a>
                    </div>
                @endforelse
            </div>

            <div class="app-card hidden overflow-hidden md:block">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line bg-mist text-xs uppercase text-muted">
                                <th class="px-6 py-4"></th>
                                <th class="px-6 py-4">Paziente</th>
                                <th class="px-6 py-4">Data nascita</th>
                                <th class="px-6 py-4">Telefono</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4 text-right">Aggiornato</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line bg-white">
                            @forelse ($patients as $patient)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('patients.show', $patient) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-white text-sage shadow-sm hover:bg-mist" title="Apri scheda paziente">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M15 3h6v6" />
                                                <path d="M10 14 21 3" />
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                                            </svg>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('patients.show', $patient) }}" class="font-bold text-ink hover:text-sage">{{ $patient->list_name }}</a>
                                        <p class="mt-1 text-xs text-muted">{{ $patient->fiscal_code ?: 'Codice fiscale non inserito' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $patient->birth_date?->format('d/m/Y') ?: 'Non inserita' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $patient->phone ?: 'Telefono non inserito' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $patient->email ?: 'Email non inserita' }}</td>
                                    <td class="px-6 py-4 text-right text-gray-500">{{ $patient->updated_at->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <p class="text-gray-600">Nessun paziente trovato.</p>
                                        <a href="{{ route('patients.create') }}" class="mt-4 inline-flex rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#4f7f75]">Crea il primo paziente</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $patients->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
