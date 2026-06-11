<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted">Area pazienti</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuovo paziente</h2>
            </div>
            <a href="{{ route('patients.index') }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Torna ai pazienti</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section">
            @include('patients._form', [
                'action' => route('patients.store'),
                'submitLabel' => 'Salva paziente',
                'cancelUrl' => route('patients.index'),
            ])
        </div>
    </div>
</x-app-layout>
