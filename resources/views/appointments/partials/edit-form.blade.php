<form method="POST" action="{{ route('appointments.update', $appointment) }}" class="mt-3 grid gap-2 border-t border-line pt-3 text-sm">
    @csrf
    @method('PATCH')

    <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
    <div class="rounded-xl border border-line bg-mist px-3 py-2 text-xs font-bold text-muted">
        {{ $appointment->patient?->list_name ?: 'Impegno personale / evento Google' }}
    </div>
    <input name="title" class="app-field py-2" value="{{ $appointment->title }}" required>
    <div class="grid gap-2 sm:grid-cols-2">
        <input name="starts_at" type="datetime-local" class="app-field py-2" value="{{ $appointment->starts_at->format('Y-m-d\TH:i') }}" required>
        <input name="ends_at" type="datetime-local" class="app-field py-2" value="{{ $appointment->ends_at->format('Y-m-d\TH:i') }}" required>
    </div>
    <div class="grid gap-2 sm:grid-cols-2">
        <select name="type" class="app-field py-2">
            @foreach ($categories as $category)
                <option value="{{ $category['key'] }}" @selected($appointment->type === $category['key'])>{{ $category['label'] }}</option>
            @endforeach
        </select>
        <select name="status" class="app-field py-2">
            @foreach ($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected($appointment->status === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <input name="notes" class="app-field py-2" value="{{ $appointment->notes }}" placeholder="Note">
    <input type="hidden" name="color" value="{{ $appointment->color }}">

    <div class="flex items-center justify-between gap-3">
        <button form="delete-appointment-{{ $appointment->id }}" class="text-sm font-bold text-red-700 hover:text-red-900" onclick="return confirm('Eliminare questo appuntamento?')">Elimina</button>
        <button class="rounded-xl bg-sage px-3 py-2 text-sm font-bold text-white hover:bg-[#4f7f75]">Salva</button>
    </div>
</form>
<form id="delete-appointment-{{ $appointment->id }}" method="POST" action="{{ route('appointments.destroy', $appointment) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
