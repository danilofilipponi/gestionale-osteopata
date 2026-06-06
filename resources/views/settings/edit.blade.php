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

                <aside class="space-y-6">
                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900">Area amministrativa</h3>
                        <div class="mt-4 space-y-3">
                            <a href="{{ route('profile.edit') }}" class="block rounded-md border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Gestione account e password</a>
                            <div class="rounded-md border border-gray-200 px-4 py-3 text-sm text-gray-500">Utenti e ruoli</div>
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
