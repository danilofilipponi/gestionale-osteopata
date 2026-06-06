<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pazienti</h2>
            <a href="{{ route('patients.create') }}" class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                Nuovo paziente
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" class="mb-6 flex gap-3">
                <input name="search" value="{{ request('search') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Cerca per nome, telefono o email">
                <button class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cerca</button>
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="divide-y divide-gray-100">
                    @forelse ($patients as $patient)
                        <a href="{{ route('patients.show', $patient) }}" class="grid gap-2 px-6 py-4 hover:bg-gray-50 md:grid-cols-4 md:items-center">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $patient->full_name }}</p>
                                <p class="text-sm text-gray-500">{{ $patient->birth_date?->format('d/m/Y') ?: 'Data di nascita non inserita' }}</p>
                            </div>
                            <p class="text-sm text-gray-700">{{ $patient->phone ?: 'Telefono non inserito' }}</p>
                            <p class="text-sm text-gray-700">{{ $patient->email ?: 'Email non inserita' }}</p>
                            <p class="text-sm text-gray-500 md:text-right">Aggiornato {{ $patient->updated_at->format('d/m/Y') }}</p>
                        </a>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <p class="text-gray-600">Nessun paziente trovato.</p>
                            <a href="{{ route('patients.create') }}" class="mt-4 inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Crea il primo paziente</a>
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
