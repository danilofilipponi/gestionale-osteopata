<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuovo paziente</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('patients.store') }}" class="rounded-lg bg-white p-6 shadow-sm">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="first_name" value="Nome" />
                        <x-text-input id="first_name" name="first_name" class="mt-1 block w-full" :value="old('first_name')" required />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_name" value="Cognome" />
                        <x-text-input id="last_name" name="last_name" class="mt-1 block w-full" :value="old('last_name')" required />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="birth_date" value="Data di nascita" />
                        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date')" />
                    </div>
                    <div>
                        <x-input-label for="gender" value="Sesso" />
                        <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <option value="">Non indicato</option>
                            <option value="F">F</option>
                            <option value="M">M</option>
                            <option value="Altro">Altro</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="birth_place" value="Luogo di nascita" />
                        <x-text-input id="birth_place" name="birth_place" class="mt-1 block w-full" :value="old('birth_place')" />
                    </div>
                    <div>
                        <x-input-label for="fiscal_code" value="Codice fiscale" />
                        <x-text-input id="fiscal_code" name="fiscal_code" class="mt-1 block w-full" :value="old('fiscal_code')" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Telefono" />
                        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="profession" value="Professione" />
                        <x-text-input id="profession" name="profession" class="mt-1 block w-full" :value="old('profession')" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="address" value="Indirizzo" />
                        <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address')" />
                    </div>
                    <div>
                        <x-input-label for="city" value="Citta" />
                        <x-text-input id="city" name="city" class="mt-1 block w-full" :value="old('city')" />
                    </div>
                    <div>
                        <x-input-label for="province" value="Provincia" />
                        <x-text-input id="province" name="province" class="mt-1 block w-full" :value="old('province')" maxlength="2" />
                    </div>
                    <div>
                        <x-input-label for="postal_code" value="CAP" />
                        <x-text-input id="postal_code" name="postal_code" class="mt-1 block w-full" :value="old('postal_code')" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="notes" value="Note" />
                        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('patients.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Annulla</a>
                    <x-primary-button>Salva paziente</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
