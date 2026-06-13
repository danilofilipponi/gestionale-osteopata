<div id="appointment-modal-{{ $appointment->id }}" class="fixed inset-0 hidden items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6" style="z-index: 2147483647;" aria-hidden="true">
    <div class="relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border-2 border-line bg-white shadow-2xl" style="z-index: 2147483647;">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line bg-mist px-5 py-4" style="border-left: 8px solid {{ $appointmentColor }};">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase text-muted">Dettaglio appuntamento</p>
                <h3 class="mt-1 truncate text-xl font-bold text-ink">{{ $appointment->title }}</h3>
                <p class="mt-1 text-sm font-semibold text-muted">
                    {{ $appointment->starts_at->format('d/m/Y H:i') }} - {{ $appointment->ends_at->format('H:i') }}
                </p>
            </div>
            <button type="button" data-close-appointment-modal class="rounded-xl border border-line bg-white px-4 py-2 text-sm font-bold text-ink shadow-sm hover:bg-mist">
                Chiudi
            </button>
        </div>

        <div class="overflow-y-auto px-5 py-5">
            <div class="mb-4 grid gap-3 text-sm md:grid-cols-2">
                <div class="rounded-xl border border-line bg-white p-3 md:col-span-2">
                    <p class="text-xs font-bold uppercase text-muted">Paziente / origine</p>
                    <p class="mt-1 font-bold text-ink">{{ $appointment->patient?->list_name ?: 'Impegno personale / evento Google' }}</p>
                </div>
                <div class="rounded-xl border border-line bg-white p-3">
                    <p class="text-xs font-bold uppercase text-muted">Categoria</p>
                    <p class="mt-1 font-bold text-ink">{{ $appointment->type }}</p>
                </div>
                <div class="rounded-xl border border-line bg-white p-3">
                    <p class="text-xs font-bold uppercase text-muted">Stato</p>
                    <p class="mt-1 font-bold text-ink">{{ $statusLabels[$appointment->status] ?? $appointment->status }}</p>
                </div>
            </div>

            @include('appointments.partials.edit-form', ['appointment' => $appointment])
        </div>
    </div>
</div>
