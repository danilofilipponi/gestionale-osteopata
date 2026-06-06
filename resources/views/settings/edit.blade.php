<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Impostazioni</h2>
                <p class="mt-1 text-sm text-gray-500">Configurazione di base dello studio e area amministrativa.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Gestione account</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
                <div class="space-y-6">
                    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        @foreach ([
                            'studio' => 'Dati studio',
                            'billing' => 'Fatturazione',
                            'operations' => 'Preferenze operative',
                        ] as $group => $title)
                            <section class="rounded-lg bg-white p-6 shadow-sm">
                                <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    @foreach ($settings as $key => $setting)
                                        @continue($setting['group'] !== $group)
                                        <div class="{{ $key === 'practice_address' ? 'md:col-span-2' : '' }}">
                                            <x-input-label :for="$key" :value="$setting['label']" />
                                            <x-text-input
                                                :id="$key"
                                                :name="$key"
                                                :type="$setting['type']"
                                                class="mt-1 block w-full"
                                                :value="old($key, $values[$key])"
                                                :step="$setting['type'] === 'number' ? '0.01' : null"
                                            />
                                            <x-input-error :messages="$errors->get($key)" class="mt-2" />
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach

                        <div class="flex justify-end">
                            <x-primary-button>Salva impostazioni</x-primary-button>
                        </div>
                    </form>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900">Nuovo utente</h3>
                        <form method="POST" action="{{ route('settings.users.store') }}" class="mt-4 grid gap-4 md:grid-cols-2">
                            @csrf
                            <div>
                                <x-input-label for="new_user_name" value="Nome" />
                                <x-text-input id="new_user_name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="new_user_email" value="Email" />
                                <x-text-input id="new_user_email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="new_user_password" value="Password" />
                                <x-text-input id="new_user_password" name="password" type="password" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="new_user_password_confirmation" value="Conferma password" />
                                <x-text-input id="new_user_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                            </div>
                            <div class="md:col-span-2">
                                <x-primary-button>Crea utente</x-primary-button>
                            </div>
                        </form>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900">Utenti esistenti</h3>
                        <div class="mt-4 divide-y divide-gray-100">
                            @foreach ($users as $user)
                                <div class="py-5">
                                    <form method="POST" action="{{ route('settings.users.update', $user) }}" class="grid gap-4 md:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <x-input-label :for="'user_name_'.$user->id" value="Nome" />
                                            <x-text-input :id="'user_name_'.$user->id" name="name" class="mt-1 block w-full" :value="$user->name" required />
                                        </div>
                                        <div>
                                            <x-input-label :for="'user_email_'.$user->id" value="Email" />
                                            <x-text-input :id="'user_email_'.$user->id" name="email" type="email" class="mt-1 block w-full" :value="$user->email" required />
                                        </div>
                                        <div>
                                            <x-input-label :for="'user_password_'.$user->id" value="Nuova password" />
                                            <x-text-input :id="'user_password_'.$user->id" name="password" type="password" class="mt-1 block w-full" />
                                        </div>
                                        <div>
                                            <x-input-label :for="'user_password_confirmation_'.$user->id" value="Conferma nuova password" />
                                            <x-text-input :id="'user_password_confirmation_'.$user->id" name="password_confirmation" type="password" class="mt-1 block w-full" />
                                        </div>
                                        <div class="flex flex-wrap items-center justify-between gap-3 md:col-span-2">
                                            <span class="text-sm text-gray-500">Aggiornato {{ $user->updated_at->format('d/m/Y H:i') }}</span>
                                            <div class="flex gap-3">
                                                @if ($users->count() > 1)
                                                    <button form="delete-user-{{ $user->id }}" class="rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminare questo utente?')">Elimina</button>
                                                @endif
                                                <x-primary-button>Salva utente</x-primary-button>
                                            </div>
                                        </div>
                                    </form>
                                    <form id="delete-user-{{ $user->id }}" method="POST" action="{{ route('settings.users.destroy', $user) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900">Area amministrativa</h3>
                        <div class="mt-4 space-y-3">
                            <a href="{{ route('profile.edit') }}" class="block rounded-md border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Il mio account</a>
                            <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900">Utenti e password</div>
                            <div class="rounded-md border border-gray-200 px-4 py-3 text-sm text-gray-500">Numerazione documenti</div>
                            <div class="rounded-md border border-gray-200 px-4 py-3 text-sm text-gray-500">Consensi e privacy</div>
                            <div class="rounded-md border border-gray-200 px-4 py-3 text-sm text-gray-500">Backup ed esportazioni</div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900">Spunti prossimi</h3>
                        <ul class="mt-4 space-y-3 text-sm text-gray-600">
                            <li>Ruoli: amministratore, osteopata, segreteria.</li>
                            <li>Template per fatture, ricevute e consensi.</li>
                            <li>Stati seduta e promemoria appuntamenti.</li>
                            <li>Campi clinici personalizzabili.</li>
                            <li>Esportazione dati per commercialista.</li>
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
