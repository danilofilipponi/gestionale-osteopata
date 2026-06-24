<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleCalendarClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const CALENDAR_URL = 'https://www.googleapis.com/calendar/v3/calendars';
    private const CALENDAR_LIST_URL = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';

    public static function configured(): bool
    {
        return filled(config('services.google_calendar.client_id'))
            && filled(config('services.google_calendar.client_secret'))
            && filled(config('services.google_calendar.redirect_uri'));
    }

    public static function connected(): bool
    {
        return filled(Setting::getValue('google_calendar_access_token'))
            && filled(Setting::getValue('google_calendar_refresh_token'));
    }

    public static function authorizationUrl(): string
    {
        $state = Str::random(40);
        session(['google_calendar_state' => $state]);

        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google_calendar.client_id'),
            'redirect_uri' => config('services.google_calendar.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/calendar.events',
                'https://www.googleapis.com/auth/calendar.readonly',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    public static function exchangeCode(string $code): void
    {
        $response = Http::timeout(20)->asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
            'redirect_uri' => config('services.google_calendar.redirect_uri'),
            'grant_type' => 'authorization_code',
        ])->throw()->json();

        self::storeToken($response);
    }

    public static function upsertAppointment(Appointment $appointment): ?string
    {
        if (! self::shouldPush()) {
            return null;
        }

        $payload = self::appointmentPayload($appointment);
        $token = self::accessToken();
        $targetCalendarId = self::calendarIdForType($appointment->type);
        $calendarId = rawurlencode($targetCalendarId);

        if ($appointment->google_event_id) {
            $response = Http::timeout(20)->withToken($token)
                ->patch(self::CALENDAR_URL.'/'.$calendarId.'/events/'.rawurlencode($appointment->google_event_id), $payload)
                ->throw()
                ->json();
        } else {
            $response = Http::timeout(20)->withToken($token)
                ->post(self::CALENDAR_URL.'/'.$calendarId.'/events', $payload)
                ->throw()
                ->json();
        }

        return $response['id'] ?? null;
    }

    public static function deleteAppointment(Appointment $appointment): void
    {
        if (! self::shouldPush() || ! $appointment->google_event_id) {
            return;
        }

        $response = Http::timeout(20)->withToken(self::accessToken())
            ->delete(self::CALENDAR_URL.'/'.rawurlencode($appointment->google_calendar_id ?: self::calendarIdForType($appointment->type)).'/events/'.rawurlencode($appointment->google_event_id));

        if (in_array($response->status(), [404, 410], true)) {
            return;
        }

        $response->throw();
    }

    public static function clearAppointmentColor(Appointment $appointment): void
    {
        if (! self::configured()
            || ! self::connected()
            || Setting::getValue('google_calendar_enabled', '0') !== '1'
            || ! $appointment->google_event_id
        ) {
            return;
        }

        $token = self::accessToken();

        foreach (self::calendarCandidates($appointment) as $calendarId) {
            $url = self::CALENDAR_URL.'/'.rawurlencode($calendarId).'/events/'.rawurlencode($appointment->google_event_id);
            $event = Http::timeout(20)->withToken($token)->get($url);

            if (! $event->successful()) {
                continue;
            }

            $payload = $event->json();
            unset($payload['colorId']);

            Http::timeout(20)
                ->withToken($token)
                ->put($url, $payload)
                ->throw();

            return;
        }

        throw new RuntimeException('Evento Google non trovato nei calendari selezionati.');
    }

    public static function events(Carbon $from, Carbon $to, ?string $calendarId = null): array
    {
        if (! self::shouldPull()) {
            return [];
        }

        $events = [];
        $pageToken = null;

        do {
            $response = Http::timeout(30)->withToken(self::accessToken())
            ->get(self::CALENDAR_URL.'/'.rawurlencode($calendarId ?: self::calendarId()).'/events', array_filter([
                'timeMin' => $from->copy()->startOfDay()->toRfc3339String(),
                'timeMax' => $to->copy()->endOfDay()->toRfc3339String(),
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => 2500,
                'pageToken' => $pageToken,
            ]))
            ->throw()
            ->json();

            $events = array_merge($events, $response['items'] ?? []);
            $pageToken = $response['nextPageToken'] ?? null;
        } while ($pageToken);

        return $events;
    }

    public static function refreshCalendarList(): array
    {
        if (! self::connected()) {
            return [];
        }

        $response = Http::timeout(20)
            ->withToken(self::accessToken())
            ->get(self::CALENDAR_LIST_URL, [
                'minAccessRole' => 'reader',
                'showHidden' => 'true',
            ])
            ->throw()
            ->json();

        $calendars = collect($response['items'] ?? [])
            ->map(fn (array $calendar) => [
                'id' => $calendar['id'] ?? '',
                'summary' => $calendar['summary'] ?? ($calendar['id'] ?? 'Calendario senza nome'),
                'primary' => (bool) ($calendar['primary'] ?? false),
                'backgroundColor' => $calendar['backgroundColor'] ?? '#64748b',
                'accessRole' => $calendar['accessRole'] ?? 'reader',
                'selected' => (bool) ($calendar['selected'] ?? true),
            ])
            ->filter(fn (array $calendar) => filled($calendar['id']))
            ->values()
            ->all();

        Setting::setValue('google_calendar_list', json_encode($calendars), 'agenda');

        $existingSelected = self::selectedCalendarIds();
        if ($existingSelected === []) {
            Setting::setValue('google_calendar_selected_ids', json_encode(
                collect($calendars)->where('selected', true)->pluck('id')->values()->all()
            ), 'agenda');
        }

        return $calendars;
    }

    public static function storedCalendarList(): array
    {
        return json_decode(Setting::getValue('google_calendar_list', '[]'), true) ?: [];
    }

    public static function selectedCalendarIds(): array
    {
        $selected = json_decode(Setting::getValue('google_calendar_selected_ids', '[]'), true) ?: [];

        if ($selected !== []) {
            return array_values(array_filter($selected));
        }

        $stored = self::storedCalendarList();

        if ($stored !== []) {
            return collect($stored)->where('selected', true)->pluck('id')->values()->all();
        }

        return [self::calendarId()];
    }

    public static function calendarIdForType(string $type): string
    {
        $categories = json_decode(Setting::getValue('agenda_categories', '[]'), true) ?: [];
        $category = collect($categories)->firstWhere('key', $type);

        return $category['google_calendar_id'] ?? self::calendarId();
    }

    public static function disconnect(): void
    {
        foreach ([
            'google_calendar_access_token',
            'google_calendar_refresh_token',
            'google_calendar_token_expires_at',
            'google_calendar_connected_at',
        ] as $key) {
            Setting::setValue($key, null, 'agenda');
        }
    }

    public static function status(): array
    {
        return [
            'configured' => self::configured(),
            'connected' => self::connected(),
            'connected_at' => Setting::getValue('google_calendar_connected_at'),
        ];
    }

    public static function authorizationFailed(Throwable $exception): bool
    {
        if (! $exception instanceof RequestException || ! $exception->response) {
            return false;
        }

        $body = $exception->response->body();

        return $exception->response->status() === 400 && str_contains($body, 'invalid_grant');
    }

    public static function syncMode(): string
    {
        return Setting::getValue('google_calendar_sync_mode', 'write') ?: 'write';
    }

    private static function shouldPush(): bool
    {
        return self::configured()
            && self::connected()
            && Setting::getValue('google_calendar_enabled', '0') === '1'
            && in_array(self::syncMode(), ['write', 'two_way'], true);
    }

    private static function shouldPull(): bool
    {
        return self::configured()
            && self::connected()
            && Setting::getValue('google_calendar_enabled', '0') === '1'
            && in_array(self::syncMode(), ['read', 'two_way'], true);
    }

    private static function accessToken(): string
    {
        $expiresAt = Setting::getValue('google_calendar_token_expires_at');
        $token = Setting::getValue('google_calendar_access_token');

        if ($token && $expiresAt && now()->lt(Carbon::parse($expiresAt)->subMinute())) {
            return $token;
        }

        return self::refreshToken();
    }

    private static function refreshToken(): string
    {
        $refreshToken = Setting::getValue('google_calendar_refresh_token');

        if (blank($refreshToken)) {
            throw new RuntimeException('Google Calendar non collegato.');
        }

        $response = Http::timeout(20)->asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ])->throw()->json();

        self::storeToken($response, $refreshToken);

        return (string) $response['access_token'];
    }

    private static function storeToken(array $token, ?string $existingRefreshToken = null): void
    {
        Setting::setValue('google_calendar_access_token', $token['access_token'] ?? null, 'agenda');
        Setting::setValue('google_calendar_refresh_token', $token['refresh_token'] ?? $existingRefreshToken, 'agenda');
        Setting::setValue('google_calendar_token_expires_at', now()->addSeconds((int) ($token['expires_in'] ?? 3600))->toDateTimeString(), 'agenda');
        Setting::setValue('google_calendar_connected_at', now()->toDateTimeString(), 'agenda');
    }

    private static function calendarId(): string
    {
        return Setting::getValue('google_calendar_id') ?: config('services.google_calendar.calendar_id', 'primary');
    }

    private static function calendarCandidates(Appointment $appointment): array
    {
        return collect([
            $appointment->google_calendar_id,
            self::calendarIdForType($appointment->type),
            self::calendarId(),
            ...self::selectedCalendarIds(),
        ])->filter()->unique()->values()->all();
    }

    private static function appointmentPayload(Appointment $appointment): array
    {
        $patient = $appointment->patient;
        $description = trim(collect([
            $patient ? 'Paziente: '.$patient->list_name : null,
            $patient?->phone ? 'Telefono: '.$patient->phone : null,
            $appointment->notes,
        ])->filter()->join("\n"));

        return [
            'summary' => $appointment->title,
            'description' => $description,
            'start' => [
                'dateTime' => $appointment->starts_at->toRfc3339String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ],
            'end' => [
                'dateTime' => $appointment->ends_at->toRfc3339String(),
                'timeZone' => config('app.timezone', 'Europe/Rome'),
            ],
        ];
    }
}
