<div id="appointment-modal-{{ $appointment->id }}" class="fixed inset-0 hidden items-center justify-center bg-slate-950/70 p-0 backdrop-blur-sm sm:p-6" style="z-index: 2147483647;" aria-hidden="true">
    <div class="relative flex h-full max-h-dvh w-full max-w-2xl flex-col overflow-hidden bg-white shadow-2xl sm:h-auto sm:max-h-[92vh] sm:rounded-2xl sm:border-2 sm:border-line" style="z-index: 2147483647;">
        <div class="flex items-start justify-between gap-3 border-b border-line bg-mist px-4 py-3 sm:px-5 sm:py-4" style="border-left: 8px solid {{ $appointmentColor }};">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase text-muted">Dettaglio appuntamento</p>
                <h3 class="mt-1 truncate text-lg font-bold text-ink sm:text-xl">{{ $appointment->title }}</h3>
                <p class="mt-1 text-sm font-semibold text-muted">
                    {{ $appointment->starts_at->format('d/m/Y H:i') }} - {{ $appointment->ends_at->format('H:i') }}
                </p>
            </div>
            <button type="button" data-close-appointment-modal class="shrink-0 rounded-xl border border-line bg-white px-3 py-2 text-sm font-bold text-ink shadow-sm hover:bg-mist sm:px-4">
                Chiudi
            </button>
        </div>

        <div class="overflow-y-auto px-4 py-4 sm:px-5 sm:py-5">
            <div class="mb-4 grid gap-3 text-sm">
                <div class="rounded-xl border border-line bg-white p-3 md:col-span-2">
                    <p class="text-xs font-bold uppercase text-muted">Paziente / origine</p>
                    <p class="mt-1 font-bold text-ink">{{ $appointment->patient?->list_name ?: 'Impegno personale / evento Google' }}</p>
                </div>
            </div>

            @include('appointments.partials.edit-form', ['appointment' => $appointment])
        </div>
    </div>
</div>
