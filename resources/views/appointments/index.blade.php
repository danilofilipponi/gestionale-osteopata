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
        $agendaPatients = $patients->map(function ($patient) {
            return [
                'id' => $patient->id,
                'name' => $patient->list_name,
                'phone' => $patient->phone,
                'email' => $patient->email,
            ];
        })->values();
        $syncPatientCalendarIds = collect($categories)
            ->filter(fn ($category) => filter_var($category['sync_patients'] ?? false, FILTER_VALIDATE_BOOL))
            ->pluck('google_calendar_id')
            ->map(fn ($calendarId) => trim((string) $calendarId))
            ->filter()
            ->values();
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
                                            $unmatchedPatient = blank($appointment->patient_id)
                                                && filled($appointment->google_event_id)
                                                && filled($appointment->google_calendar_id)
                                                && $syncPatientCalendarIds->contains(trim((string) $appointment->google_calendar_id));
                                        @endphp
                                        <button type="button" data-appointment-modal="appointment-modal-{{ $appointment->id }}" class="relative block w-full rounded-lg border border-line bg-white p-2 text-left text-xs shadow-sm transition hover:bg-mist" style="border-left: 5px solid {{ $appointmentColor }};">
                                            @if ($unmatchedPatient)
                                                <span title="Paziente non abbinato" style="position:absolute; top:4px; right:4px; z-index:80; display:block; width:12px; height:12px; border-radius:9999px; background:#dc2626; border:2px solid #ffffff; box-shadow:0 1px 4px rgba(15,23,42,.35);"></span>
                                            @endif
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
                                    <div
                                        class="relative cursor-pointer border-r border-line last:border-r-0 {{ $day->isSameDay($today) ? 'bg-[#f4faf8]' : 'bg-white' }}"
                                        data-agenda-day
                                        data-date="{{ $day->format('Y-m-d') }}"
                                        data-start-time="{{ $settings['agenda_start_time'] }}"
                                        data-slot-minutes="{{ $slotMinutes }}"
                                        data-slot-height="{{ $slotHeight }}"
                                        data-default-duration="{{ (int) $settings['agenda_default_duration'] }}"
                                        style="height: {{ $agendaBodyHeight }}px;"
                                    >
                                        <div class="pointer-events-none absolute inset-0 z-0" style="background-image: repeating-linear-gradient(to bottom, transparent 0, transparent {{ $slotHeight - 1 }}px, #b8cbc6 {{ $slotHeight - 1 }}px, #b8cbc6 {{ $slotHeight }}px);"></div>
                                        <div class="pointer-events-none absolute left-0 right-0 z-[1] hidden bg-sage/10" data-agenda-slot-preview style="height: {{ $slotHeight }}px;"></div>

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
                                                $unmatchedPatient = blank($appointment->patient_id)
                                                    && filled($appointment->google_event_id)
                                                    && filled($appointment->google_calendar_id)
                                                    && $syncPatientCalendarIds->contains(trim((string) $appointment->google_calendar_id));
                                            @endphp
                                            <button
                                                type="button"
                                                data-appointment-modal="appointment-modal-{{ $appointment->id }}"
                                                data-draggable-appointment
                                                data-appointment-id="{{ $appointment->id }}"
                                                data-starts-at="{{ $appointment->starts_at->format('Y-m-d\TH:i') }}"
                                                data-ends-at="{{ $appointment->ends_at->format('Y-m-d\TH:i') }}"
                                                draggable="true"
                                                class="absolute left-0 right-0 z-10 box-border cursor-grab overflow-hidden rounded-xl border border-line bg-white p-2 text-left shadow-sm transition hover:bg-mist active:cursor-grabbing"
                                                style="top: {{ $eventTop + 2 }}px; width: 100%; min-height: {{ $eventHeight }}px; border-left: 5px solid {{ $appointmentColor }};"
                                            >
                                                @if ($unmatchedPatient)
                                                    <span title="Paziente non abbinato" style="position:absolute; top:5px; right:5px; z-index:80; display:block; width:13px; height:13px; border-radius:9999px; background:#dc2626; border:2px solid #ffffff; box-shadow:0 1px 5px rgba(15,23,42,.4);"></span>
                                                @endif
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
        @include('appointments.partials.create-modal')
        @include('appointments.partials.patient-match-modal')

        @foreach ($appointments as $appointment)
            @php
                $appointmentColor = $appointment->color ?: ($categoryMap->get($appointment->type)['color'] ?? '#5f948a');
            @endphp
            @include('appointments.partials.modal', ['appointment' => $appointment, 'appointmentColor' => $appointmentColor])
        @endforeach
    @endpush

    @push('scripts')
        <script>
            const agendaPatients = @json($agendaPatients);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));
            const padNumber = (value) => String(value).padStart(2, '0');

            const formatDateTimeLocal = (date) => {
                return [
                    date.getFullYear(),
                    padNumber(date.getMonth() + 1),
                    padNumber(date.getDate()),
                ].join('-') + 'T' + [padNumber(date.getHours()), padNumber(date.getMinutes())].join(':');
            };

            const formatItalianPeriod = (start, end) => {
                return [
                    padNumber(start.getDate()),
                    padNumber(start.getMonth() + 1),
                    start.getFullYear(),
                ].join('/') + ' ' + [padNumber(start.getHours()), padNumber(start.getMinutes())].join(':') + ' - ' + [padNumber(end.getHours()), padNumber(end.getMinutes())].join(':');
            };

            const minutesBetween = (start, end) => Math.max(15, Math.round((end - start) / 60000));

            const slotDateFromClick = (column, clientY) => {
                const rect = column.getBoundingClientRect();
                const relativeY = Math.max(0, Math.min(clientY - rect.top, rect.height - 1));
                const slotHeight = Number(column.dataset.slotHeight || 26);
                const slotMinutes = Number(column.dataset.slotMinutes || 15);
                const defaultDuration = Number(column.dataset.defaultDuration || 60);
                const slotIndex = Math.floor(relativeY / slotHeight);
                const [startHour, startMinute] = (column.dataset.startTime || '08:00').split(':').map(Number);
                const start = new Date(`${column.dataset.date}T${padNumber(startHour)}:${padNumber(startMinute)}`);

                start.setMinutes(start.getMinutes() + (slotIndex * slotMinutes));

                const end = new Date(start);
                end.setMinutes(end.getMinutes() + defaultDuration);

                return { start, end, slotIndex, slotHeight };
            };

            const moveAppointment = async (appointmentId, startsAt, endsAt) => {
                const response = await fetch(`{{ url('/appointments') }}/${appointmentId}/move`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        starts_at: formatDateTimeLocal(startsAt),
                        ends_at: formatDateTimeLocal(endsAt),
                    }),
                });

                if (response.ok) {
                    window.location.reload();
                    return;
                }

                const data = await response.json().catch(() => null);
                const message = data?.message || Object.values(data?.errors || {})?.flat()?.[0] || 'Non sono riuscito a spostare l’appuntamento.';
                alert(message);
            };

            const fillNewAppointmentModal = (start, end) => {
                const modal = document.getElementById('new-appointment-modal');
                const startsAt = modal?.querySelector('#new_starts_at');
                const endsAt = modal?.querySelector('#new_ends_at');
                const title = modal?.querySelector('[data-new-appointment-title]');
                const period = modal?.querySelector('[data-new-appointment-period]');
                const patientId = modal?.querySelector('#new_patient_id');
                const appointmentTitle = modal?.querySelector('#new_title');
                const results = modal?.querySelector('[data-patient-results]');

                if (startsAt) {
                    startsAt.value = formatDateTimeLocal(start);
                }

                if (endsAt) {
                    endsAt.value = formatDateTimeLocal(end);
                }

                if (title) {
                    title.textContent = 'Nuovo appuntamento';
                }

                if (period) {
                    period.textContent = formatItalianPeriod(start, end);
                }

                if (patientId) {
                    patientId.value = '';
                }

                if (appointmentTitle) {
                    appointmentTitle.value = '';
                }

                if (results) {
                    results.classList.add('hidden');
                    results.innerHTML = '';
                }

                openAgendaModal(modal);
                appointmentTitle?.focus();
            };

            const renderPatientResults = (input) => {
                const modal = document.getElementById('new-appointment-modal');
                const results = modal?.querySelector('[data-patient-results]');
                const patientId = modal?.querySelector('#new_patient_id');
                const query = input.value.trim().toLowerCase();

                if (patientId) {
                    patientId.value = '';
                }

                if (!results) {
                    return;
                }

                if (query.length < 2) {
                    results.classList.add('hidden');
                    results.innerHTML = '';
                    return;
                }

                const matches = agendaPatients
                    .filter((patient) => patient.name.toLowerCase().includes(query))
                    .slice(0, 8);

                if (matches.length === 0) {
                    results.innerHTML = '<div class="px-3 py-2 text-sm font-semibold text-muted">Nessun paziente trovato</div>';
                    results.classList.remove('hidden');
                    return;
                }

                results.innerHTML = matches.map((patient) => `
                    <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-mist" data-patient-result-id="${patient.id}">
                        <span class="block font-bold text-ink">${escapeHtml(patient.name)}</span>
                        <span class="block text-xs text-muted">${escapeHtml([patient.phone, patient.email].filter(Boolean).join(' - '))}</span>
                    </button>
                `).join('');
                results.classList.remove('hidden');
            };

            const renderLinkPatientResults = (input) => {
                const form = input.closest('form');
                const results = form?.querySelector('[data-link-patient-results]');
                const query = input.value.trim().toLowerCase();

                if (!results) {
                    return;
                }

                if (query.length < 2) {
                    results.classList.add('hidden');
                    results.innerHTML = '';
                    return;
                }

                const matches = agendaPatients
                    .filter((patient) => patient.name.toLowerCase().includes(query))
                    .slice(0, 10);

                if (matches.length === 0) {
                    results.innerHTML = '<div class="px-3 py-2 text-sm font-semibold text-muted">Nessun paziente trovato</div>';
                    results.classList.remove('hidden');
                    return;
                }

                results.innerHTML = matches.map((patient) => `
                    <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-mist" data-link-patient-result-id="${patient.id}">
                        <span class="block font-bold text-ink">${escapeHtml(patient.name)}</span>
                        <span class="block text-xs text-muted">${escapeHtml([patient.phone, patient.email].filter(Boolean).join(' - '))}</span>
                    </button>
                `).join('');
                results.classList.remove('hidden');
            };

            const closeAgendaModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };

            const openAgendaModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
            };

            let nativeDragState = null;
            let nativeDragRecentlyFinished = false;

            const finishNativeDrag = () => {
                nativeDragState?.appointment?.classList.remove('opacity-40', 'ring-2', 'ring-sage');
                nativeDragState = null;
                document.querySelectorAll('[data-agenda-slot-preview]').forEach((preview) => preview.classList.add('hidden'));
            };

            document.addEventListener('dragstart', (event) => {
                const appointment = event.target.closest('[data-draggable-appointment]');

                if (!appointment) {
                    return;
                }

                nativeDragState = {
                    appointment,
                    appointmentId: appointment.dataset.appointmentId,
                    start: new Date(appointment.dataset.startsAt),
                    end: new Date(appointment.dataset.endsAt),
                };
                appointment.classList.add('opacity-40', 'ring-2', 'ring-sage');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', appointment.dataset.appointmentId || '');
            });

            document.addEventListener('dragover', (event) => {
                if (!nativeDragState) {
                    return;
                }

                const dayColumn = event.target.closest('[data-agenda-day]');

                if (!dayColumn) {
                    return;
                }

                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';

                const { slotIndex, slotHeight } = slotDateFromClick(dayColumn, event.clientY);
                const preview = dayColumn.querySelector('[data-agenda-slot-preview]');
                document.querySelectorAll('[data-agenda-slot-preview]').forEach((item) => {
                    if (item !== preview) {
                        item.classList.add('hidden');
                    }
                });

                if (preview) {
                    preview.style.top = `${slotIndex * slotHeight}px`;
                    preview.style.height = `${Math.max(slotHeight, nativeDragState.appointment.getBoundingClientRect().height)}px`;
                    preview.classList.remove('hidden');
                }
            });

            document.addEventListener('drop', (event) => {
                if (!nativeDragState) {
                    return;
                }

                const dayColumn = event.target.closest('[data-agenda-day]');

                if (!dayColumn) {
                    finishNativeDrag();
                    return;
                }

                event.preventDefault();
                nativeDragRecentlyFinished = true;
                window.setTimeout(() => {
                    nativeDragRecentlyFinished = false;
                }, 300);

                const duration = minutesBetween(nativeDragState.start, nativeDragState.end);
                const { start } = slotDateFromClick(dayColumn, event.clientY);
                const end = new Date(start);
                const appointmentId = nativeDragState.appointmentId;
                end.setMinutes(end.getMinutes() + duration);
                finishNativeDrag();
                moveAppointment(appointmentId, start, end);
            });

            document.addEventListener('dragend', finishNativeDrag);

            document.addEventListener('click', (event) => {
                const opener = event.target.closest('[data-appointment-modal]');
                const dayColumn = event.target.closest('[data-agenda-day]');
                const closer = event.target.closest('[data-close-appointment-modal], [data-close-agenda-modal], [data-close-patient-match-modal]');
                const patientResult = event.target.closest('[data-patient-result-id]');
                const toggleLinkPatient = event.target.closest('[data-toggle-link-patient]');
                const linkPatientResult = event.target.closest('[data-link-patient-result-id]');

                if (toggleLinkPatient) {
                    const form = toggleLinkPatient.closest('form');
                    const panel = form?.querySelector('[data-link-patient-panel]');
                    const input = form?.querySelector('[data-link-patient-search]');

                    panel?.classList.toggle('hidden');

                    if (panel && !panel.classList.contains('hidden')) {
                        input?.focus();
                    }

                    return;
                }

                if (linkPatientResult) {
                    const form = linkPatientResult.closest('form');
                    const patient = agendaPatients.find((item) => String(item.id) === String(linkPatientResult.dataset.linkPatientResultId));
                    const patientId = form?.querySelector('[data-appointment-patient-id]');
                    const titleInput = form?.querySelector('input[name="title"]');
                    const label = form?.querySelector('[data-appointment-patient-label]');
                    const input = form?.querySelector('[data-link-patient-search]');
                    const results = form?.querySelector('[data-link-patient-results]');
                    const folderLink = form?.querySelector('[data-patient-folder-link]');

                    if (patientId) {
                        patientId.value = linkPatientResult.dataset.linkPatientResultId || '';
                    }

                    if (titleInput && patient?.name) {
                        titleInput.value = patient.name;
                    }

                    if (label && patient?.name) {
                        label.textContent = patient.name;
                        label.classList.remove('text-muted');
                        label.classList.add('text-ink');
                    }

                    if (input && patient?.name) {
                        input.value = patient.name;
                    }

                    if (results) {
                        results.classList.add('hidden');
                    }

                    if (folderLink && patient?.id) {
                        folderLink.href = `{{ url('/patients') }}/${patient.id}`;
                        folderLink.classList.remove('hidden');
                        folderLink.classList.add('inline-flex');
                    }

                    return;
                }

                if (patientResult) {
                    const modal = document.getElementById('new-appointment-modal');
                    const input = modal?.querySelector('#new_title');
                    const patientId = modal?.querySelector('#new_patient_id');
                    const results = modal?.querySelector('[data-patient-results]');
                    const patient = agendaPatients.find((item) => String(item.id) === String(patientResult.dataset.patientResultId));

                    if (input) {
                        input.value = patient?.name || '';
                    }

                    if (patientId) {
                        patientId.value = patientResult.dataset.patientResultId || '';
                    }

                    if (results) {
                        results.classList.add('hidden');
                    }

                    return;
                }

                if (opener) {
                    if (nativeDragRecentlyFinished) {
                        event.preventDefault();
                        return;
                    }

                    const modal = document.getElementById(opener.dataset.appointmentModal);
                    openAgendaModal(modal);
                    return;
                }

                if (closer) {
                    closeAgendaModal(closer.closest('[id^="appointment-modal-"], #new-appointment-modal, #patient-match-modal'));
                    return;
                }

                if (event.target.matches('[id^="appointment-modal-"], #new-appointment-modal, #patient-match-modal')) {
                    closeAgendaModal(event.target);
                    return;
                }

                if (dayColumn && !nativeDragRecentlyFinished) {
                    const { start, end } = slotDateFromClick(dayColumn, event.clientY);
                    fillNewAppointmentModal(start, end);
                }
            });

            document.addEventListener('mousemove', (event) => {
                const dayColumn = event.target.closest('[data-agenda-day]');
                document.querySelectorAll('[data-agenda-slot-preview]').forEach((preview) => preview.classList.add('hidden'));

                if (!dayColumn || event.target.closest('[data-appointment-modal]')) {
                    return;
                }

                const { slotIndex, slotHeight } = slotDateFromClick(dayColumn, event.clientY);
                const preview = dayColumn.querySelector('[data-agenda-slot-preview]');

                if (preview) {
                    preview.style.top = `${slotIndex * slotHeight}px`;
                    preview.classList.remove('hidden');
                }
            });

            document.addEventListener('input', (event) => {
                const patientSearch = event.target.closest('[data-patient-search]');

                if (patientSearch) {
                    renderPatientResults(patientSearch);
                }

                const linkPatientSearch = event.target.closest('[data-link-patient-search]');

                if (linkPatientSearch) {
                    renderLinkPatientResults(linkPatientSearch);
                }
            });

            document.addEventListener('focusin', (event) => {
                const patientSearch = event.target.closest('[data-patient-search]');

                if (patientSearch) {
                    renderPatientResults(patientSearch);
                }

                const linkPatientSearch = event.target.closest('[data-link-patient-search]');

                if (linkPatientSearch) {
                    renderLinkPatientResults(linkPatientSearch);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('[id^="appointment-modal-"].flex, #new-appointment-modal.flex, #patient-match-modal.flex').forEach(closeAgendaModal);
            });

            document.addEventListener('DOMContentLoaded', () => {
                const patientMatchModal = document.getElementById('patient-match-modal');

                if (patientMatchModal) {
                    openAgendaModal(patientMatchModal);
                }
            });
        </script>
    @endpush
</x-app-layout>
