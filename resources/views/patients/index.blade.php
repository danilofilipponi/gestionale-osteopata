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
                <div class="divide-y divide-gray-100">
                    @forelse ($patients as $patient)
                        <a href="{{ route('patients.show', $patient) }}" class="grid gap-2 px-6 py-4 hover:bg-gray-50 md:grid-cols-4 md:items-center">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $patient->list_name }}</p>
                                <p class="text-sm text-gray-500">{{ $patient->birth_date?->format('d/m/Y') ?: 'Data di nascita non inserita' }}</p>
                            </div>
                            <p class="text-sm text-gray-700">{{ $patient->phone ?: 'Telefono non inserito' }}</p>
                            <p class="text-sm text-gray-700">{{ $patient->email ?: 'Email non inserita' }}</p>
                            <p class="text-sm text-gray-500 md:text-right">Aggiornato {{ $patient->updated_at->format('d/m/Y') }}</p>
                        </a>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <p class="text-gray-600">Nessun paziente trovato.</p>
                            <a href="{{ route('patients.create') }}" class="mt-4 inline-flex rounded-xl bg-sage px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#4f7f75]">Crea il primo paziente</a>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">
                {{ $patients->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
