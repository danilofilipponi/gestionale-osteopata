<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifica paziente</h2>
            <a href="{{ route('patients.show', $patient) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Torna alla scheda</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('patients.update', $patient) }}" class="rounded-lg bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="first_name" value="Nome" />
                        <x-text-input id="first_name" name="first_name" class="mt-1 block w-full" :value="old('first_name', $patient->first_name)" required />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_name" value="Cognome" />
                        <x-text-input id="last_name" name="last_name" class="mt-1 block w-full" :value="old('last_name', $patient->last_name)" required />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="birth_date" value="Data di nascita" />
                        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', $patient->birth_date?->toDateString())" />
                    </div>
                    <div>
                        <x-input-label for="gender" value="Sesso" />
                        <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <option value="">Non indicato</option>
                            @foreach (['F', 'M', 'Altro'] as $gender)
                                <option value="{{ $gender }}" @selected(old('gender', $patient->gender) === $gender)>{{ $gender }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="birth_place" value="Luogo di nascita" />
                        <x-text-input id="birth_place" name="birth_place" class="mt-1 block w-full" :value="old('birth_place', $patient->birth_place)" />
                    </div>
                    <div>
                        <x-input-label for="fiscal_code" value="Codice fiscale" />
                        <x-text-input id="fiscal_code" name="fiscal_code" class="mt-1 block w-full" :value="old('fiscal_code', $patient->fiscal_code)" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Telefono" />
                        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $patient->phone)" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $patient->email)" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="profession" value="Professione" />
                        <x-text-input id="profession" name="profession" class="mt-1 block w-full" :value="old('profession', $patient->profession)" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="address" value="Indirizzo" />
                        <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $patient->address)" />
                    </div>
                    <div>
                        <x-input-label for="city" value="Citta" />
                        <x-text-input id="city" name="city" class="mt-1 block w-full" :value="old('city', $patient->city)" />
                    </div>
                    <div>
                        <x-input-label for="province" value="Provincia" />
                        <x-text-input id="province" name="province" class="mt-1 block w-full" :value="old('province', $patient->province)" maxlength="2" />
                    </div>
                    <div>
                        <x-input-label for="postal_code" value="CAP" />
                        <x-text-input id="postal_code" name="postal_code" class="mt-1 block w-full" :value="old('postal_code', $patient->postal_code)" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="notes" value="Note" />
                        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">{{ old('notes', $patient->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap justify-between gap-3">
                    <button form="delete-patient-form" class="rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminare questo paziente e tutti i dati collegati?')">Elimina paziente</button>
                    <div class="flex gap-3">
                        <a href="{{ route('patients.show', $patient) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annulla</a>
                        <x-primary-button>Salva modifiche</x-primary-button>
                    </div>
                </div>
            </form>

            <form id="delete-patient-form" method="POST" action="{{ route('patients.destroy', $patient) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</x-app-layout>
