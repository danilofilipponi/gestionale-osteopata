<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted">Area pazienti</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifica paziente</h2>
            </div>
            <a href="{{ route('patients.show', $patient) }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Torna alla scheda</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section">
            @include('patients._form', [
                'patient' => $patient,
                'action' => route('patients.update', $patient),
                'method' => 'PATCH',
                'submitLabel' => 'Salva modifiche',
                'cancelUrl' => route('patients.show', $patient),
                'deleteFormId' => 'delete-patient-form',
            ])

            <form id="delete-patient-form" method="POST" action="{{ route('patients.destroy', $patient) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</x-app-layout>
