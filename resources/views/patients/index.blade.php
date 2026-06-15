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
            <form method="GET" class="mb-6 flex gap-3">
                <input name="search" value="{{ request('search') }}" class="app-field w-full" placeholder="Cerca per nome, telefono o email">
                <button class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Cerca</button>
            </form>

            <div class="app-card overflow-hidden">
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
