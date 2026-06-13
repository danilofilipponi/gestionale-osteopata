<x-app-layout>
    @php
        $today = now();
        $viewLabels = ['day' => 'Giorno', 'week' => 'Settimana', 'month' => 'Mese'];
        $periodTitle = match ($view) {
            'day' => $date->translatedFormat('l d F Y'),
            'month' => $date->translatedFormat('F Y'),
            default => $start->format('d/m/Y').' - '.$end->format('d/m/Y'),
        };
        $prevDate = match ($view) {
            'day' => $date->copy()->subDay(),
            'month' => $date->copy()->subMonth(),
            default => $date->copy()->subWeek(),
        };
        $nextDate = match ($view) {
            'day' => $date->copy()->addDay(),
            'month' => $date->copy()->addMonth(),
            default => $date->copy()->addWeek(),
        };
        $defaultStart = $date->copy()->setTimeFromTimeString($settings['agenda_start_time'])->format('Y-m-d\TH:i');
        $defaultEnd = $date->copy()->setTimeFromTimeString($settings['agenda_start_time'])->addMinutes((int) $settings['agenda_default_duration'])->format('Y-m-d\TH:i');
        $categoryMap = collect($categories)->keyBy('key');
        $calendarDayChunks = $calendarDays->chunk(7);
        $slotMinutes = 15;
        $slotHeight = 26;
        $agendaStartMinutes = (int) now()->setTimeFromTimeString($settings['agenda_start_time'])->diffInMinutes(now()->setTimeFromTimeString($settings['agenda_end_time']));
        $agendaBodyHeight = max(count($timeSlots) * $slotHeight, $slotHeight);
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Agenda</h2>
                <p class="mt-1 text-sm text-gray-500">{{ ucfirst($periodTitle) }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('settings.agenda') }}" class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-bold text-ink shadow-sm hover:bg-mist">Impostazioni agenda</a>
                <a href="{{ route('appointments.index', ['view' => $view, 'date' => now()->toDateString()]) }}" class="rounded-xl border border-line bg-white px-3 py-2 text-sm font-bold text-muted hover:bg-mist hover:text-ink">Oggi</a>
                <div class="flex rounded-xl border border-line bg-white p-1 shadow-sm">
                    @foreach ($viewLabels as $key => $label)
                        <a href="{{ route('appointments.index', ['view' => $key, 'date' => $date->toDateString()]) }}" class="rounded-lg px-3 py-1.5 text-sm font-bold {{ $view === $key ? 'bg-sage text-white' : 'text-muted hover:bg-mist hover:text-ink' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="app-section space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="app-card bg-[#eef6f4] p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase text-muted">Nuovo appuntamento</p>
                        <h3 class="text-lg font-semibold text-gray-900">Inserimento rapido in agenda</h3>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-sage">{{ $settings['agenda_default_duration'] }} minuti default</span>
                </div>

                <form method="POST" action="{{ route('appointments.store') }}" class="mt-4 grid gap-3 md:grid-cols-6">
                    @csrf
                    <select name="patient_id" class="app-field md:col-span-2">
                        <option value="">Impegno personale</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->list_name }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="title" placeholder="Titolo appuntamento" class="md:col-span-2" required />
                    <select name="type" class="app-field">
                        @foreach ($categories as $category)
                            <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="app-field">
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="starts_at" type="datetime-local" :value="$defaultStart" required />
                    <x-text-input name="ends_at" type="datetime-local" :value="$defaultEnd" required />
                    <x-text-input name="notes" placeholder="Note" class="md:col-span-3" />
                    <input type="hidden" name="color" value="">
                    <div class="md:col-span-1">
                        <x-primary-button class="w-full justify-center">Crea</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="app-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line bg-white px-5 py-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('appointments.index', ['view' => $view, 'date' => $prevDate->toDateString()]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-white text-xl font-bold text-sage shadow-sm hover:bg-mist" aria-label="Periodo precedente">
                            &lsaquo;
                        </a>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted">Calendario</p>
                            <h3 class="text-lg font-semibold text-gray-900">{{ ucfirst($periodTitle) }}</h3>
                        </div>
                        <a href="{{ route('appointments.index', ['view' => $view, 'date' => $nextDate->toDateString()]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-white text-xl font-bold text-sage shadow-sm hover:bg-mist" aria-label="Periodo successivo">
                            &rsaquo;
                        </a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($categories as $category)
                            <span class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-3 py-1 text-xs font-bold text-ink">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $category['color'] }}"></span>
                                {{ $category['label'] }}
                            </span>
                        @endforeach
                    </div>
                </div>

                @if ($view === 'month')
                    <div class="grid grid-cols-7 border-b border-line bg-mist text-center text-xs font-bold uppercase text-muted">
                        @foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $dayLabel)
                            <div class="border-r border-line px-2 py-3 last:border-r-0">{{ $dayLabel }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7 bg-white">
                        @foreach ($calendarDays as $day)
                            @php
                                $dayEvents = $appointmentsByDate->get($day->toDateString(), collect());
                                $isToday = $day->isSameDay($today);
                                $outsideMonth = ! $day->isSameMonth($date);
                            @endphp
                            <div class="min-h-36 border-b border-r border-line p-2 last:border-r-0 {{ $isToday ? 'bg-[#eef6f4]' : ($outsideMonth ? 'bg-gray-50 text-gray-400' : 'bg-white') }}">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold {{ $isToday ? 'bg-sage text-white' : 'text-ink' }}">{{ $day->format('d') }}</span>
                                    @if ($dayEvents->isNotEmpty())
                                        <span class="rounded-full bg-mist px-2 py-0.5 text-[11px] font-bold text-sage">{{ $dayEvents->count() }}</span>
                                    @endif
                                </div>
                                <div class="mt-2 space-y-1">
                                    @foreach ($dayEvents->take(4) as $appointment)
                                        @php
                                            $appointmentColor = $appointment->color ?: ($categoryMap->get($appointment->type)['color'] ?? '#5f948a');
                                        @endphp
                                        <button type="button" data-appointment-modal="appointment-modal-{{ $appointment->id }}" class="block w-full rounded-lg border border-line bg-white p-2 text-left text-xs shadow-sm transition hover:bg-mist" style="border-left: 5px solid {{ $appointmentColor }};">
                                            <span class="block truncate font-bold text-ink">
                                                <span class="mr-1 inline-block h-2 w-2 rounded-full" style="background-color: {{ $appointmentColor }}"></span>
                                                {{ $appointment->starts_at->format('H:i') }} {{ $appointment->title }}
                                            </span>
                                        </button>
                                    @endforeach
                                    @if ($dayEvents->count() > 4)
                                        <p class="text-xs font-bold text-muted">+ {{ $dayEvents->count() - 4 }} altri</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <div class="min-w-[980px]">
                            <div class="grid border-b border-line bg-mist text-xs font-bold uppercase text-muted" style="grid-template-columns: 82px repeat({{ $calendarDays->count() }}, minmax(180px, 1fr));">
                                <div class="border-r border-line px-3 py-3">Ora</div>
                                @foreach ($calendarDays as $day)
                                    <div class="border-r border-line px-3 py-3 last:border-r-0 {{ $day->isSameDay($today) ? 'bg-[#dff1ed] text-sage' : '' }}">
                                        <span class="block">{{ $day->translatedFormat('D') }}</span>
                                        <span class="text-base text-ink">{{ $day->format('d/m') }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="grid bg-white" style="grid-template-columns: 82px repeat({{ $calendarDays->count() }}, minmax(180px, 1fr));">
                                <div class="border-r border-line bg-white">
                                    @foreach ($timeSlots as $slot)
                                        <div class="border-b border-[#d1dfdb] px-2 pt-1.5 text-[11px] font-bold leading-none text-muted" style="height: {{ $slotHeight }}px;">{{ $slot }}</div>
                                    @endforeach
                                </div>

                                @foreach ($calendarDays as $day)
                                    @php
                                        $dayStart = $day->copy()->setTimeFromTimeString($settings['agenda_start_time']);
                                        $dayEnd = $day->copy()->setTimeFromTimeString($settings['agenda_end_time']);
                                        $dayEvents = $appointmentsByDate->get($day->toDateString(), collect());
                                    @endphp
                                    <div class="relative border-r border-line last:border-r-0 {{ $day->isSameDay($today) ? 'bg-[#f4faf8]' : 'bg-white' }}" style="height: {{ $agendaBodyHeight }}px;">
                                        <div class="pointer-events-none absolute inset-0 z-0" style="background-image: repeating-linear-gradient(to bottom, transparent 0, transparent {{ $slotHeight - 1 }}px, #b8cbc6 {{ $slotHeight - 1 }}px, #b8cbc6 {{ $slotHeight }}px);"></div>

                                        @foreach ($dayEvents as $appointment)
                                            @php
                                                $visibleStart = $appointment->starts_at->copy()->lessThan($dayStart) ? $dayStart->copy() : $appointment->starts_at->copy();
                                                $visibleEnd = $appointment->ends_at->copy()->greaterThan($dayEnd) ? $dayEnd->copy() : $appointment->ends_at->copy();
                                                $minutesFromStart = max(0, $dayStart->diffInMinutes($visibleStart, false));
                                                $durationMinutes = max(15, $visibleStart->diffInMinutes($visibleEnd, false));
                                                $eventTop = ($minutesFromStart / $slotMinutes) * $slotHeight;
                                                $eventHeight = max(24, ($durationMinutes / $slotMinutes) * $slotHeight);
                                            @endphp
                                            @php
                                                $appointmentColor = $appointment->color ?: ($categoryMap->get($appointment->type)['color'] ?? '#5f948a');
                                            @endphp
                                            <button type="button" data-appointment-modal="appointment-modal-{{ $appointment->id }}" class="absolute left-0 right-0 z-10 box-border overflow-hidden rounded-xl border border-line bg-white p-2 text-left shadow-sm transition hover:bg-mist" style="top: {{ $eventTop + 2 }}px; width: 100%; min-height: {{ $eventHeight }}px; border-left: 5px solid {{ $appointmentColor }};">
                                                <div class="flex items-start gap-2">
                                                    <span class="mt-1 h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $appointmentColor }}"></span>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-bold text-ink">{{ $appointment->title }}</p>
                                                        <p class="mt-0.5 text-xs text-muted">{{ $appointment->starts_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }}</p>
                                                        <p class="truncate text-xs text-muted">{{ $appointment->patient?->list_name ?: ($categoryMap->get($appointment->type)['label'] ?? 'Impegno personale') }}</p>
                                                    </div>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>

    @push('modals')
        @foreach ($appointments as $appointment)
            @php
                $appointmentColor = $appointment->color ?: ($categoryMap->get($appointment->type)['color'] ?? '#5f948a');
            @endphp
            @include('appointments.partials.modal', ['appointment' => $appointment, 'appointmentColor' => $appointmentColor])
        @endforeach
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const opener = event.target.closest('[data-appointment-modal]');
                const closer = event.target.closest('[data-close-appointment-modal]');

                if (opener) {
                    const modal = document.getElementById(opener.dataset.appointmentModal);
                    if (modal) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        modal.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('overflow-hidden');
                    }
                    return;
                }

                if (closer) {
                    const modal = closer.closest('[id^="appointment-modal-"]');
                    if (modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        modal.setAttribute('aria-hidden', 'true');
                        document.body.classList.remove('overflow-hidden');
                    }
                    return;
                }

                if (event.target.matches('[id^="appointment-modal-"]')) {
                    event.target.classList.add('hidden');
                    event.target.classList.remove('flex');
                    event.target.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('[id^="appointment-modal-"].flex').forEach((modal) => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('overflow-hidden');
                });
            });
        </script>
    @endpush
</x-app-layout>
