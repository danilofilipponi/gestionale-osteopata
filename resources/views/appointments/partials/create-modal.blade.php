<div id="new-appointment-modal" class="fixed inset-0 hidden items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6" style="z-index: 2147483647;" aria-hidden="true">
    <div class="relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border-2 border-line bg-white shadow-2xl" style="z-index: 2147483647;">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line bg-mist px-5 py-4" style="border-left: 8px solid #5c8d83;">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase text-muted">Nuovo appuntamento</p>
                <h3 class="mt-1 text-xl font-bold text-ink" data-new-appointment-title>Appuntamento</h3>
                <p class="mt-1 text-sm font-semibold text-muted" data-new-appointment-period></p>
            </div>
            <button type="button" data-close-agenda-modal class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                Chiudi
            </button>
        </div>

        <div class="overflow-y-auto px-5 py-5">
            <form method="POST" action="{{ route('appointments.store') }}" class="grid gap-3 md:grid-cols-2">
                @csrf

                <div class="relative md:col-span-2">
                    <x-input-label for="new_title" value="Titolo appuntamento" />
                    <x-text-input id="new_title" name="title" class="mt-1 block w-full" placeholder="Scrivi cognome e nome del paziente oppure un titolo libero" autocomplete="off" data-patient-search required />
                    <input id="new_patient_id" type="hidden" name="patient_id" value="">
                    <div class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-64 overflow-y-auto rounded-xl border border-line bg-white p-1 shadow-2xl" data-patient-results></div>
                </div>

                <div>
                    <x-input-label for="new_type" value="Categoria" />
                    <select id="new_type" name="type" class="app-field mt-1 block w-full">
                        @foreach ($categories as $category)
                            <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="new_status" value="Stato" />
                    <select id="new_status" name="status" class="app-field mt-1 block w-full">
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="new_starts_at" value="Inizio" />
                    <x-text-input id="new_starts_at" name="starts_at" type="datetime-local" class="mt-1 block w-full" required />
                </div>

                <div>
                    <x-input-label for="new_ends_at" value="Fine" />
                    <x-text-input id="new_ends_at" name="ends_at" type="datetime-local" class="mt-1 block w-full" required />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="new_notes" value="Note" />
                    <x-text-input id="new_notes" name="notes" class="mt-1 block w-full" placeholder="Note" />
                </div>

                <input type="hidden" name="color" value="">

                <div class="flex items-center justify-end gap-2 md:col-span-2">
                    <button type="button" data-close-agenda-modal class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                        Annulla
                    </button>
                    <x-primary-button>Crea appuntamento</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
