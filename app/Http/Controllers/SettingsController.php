<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'settings' => $this->settings(),
            'values' => $this->values(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate($this->rules());

        foreach ($this->settings() as $key => $definition) {
            Setting::setValue($key, $validated[$key] ?? null, $definition['group']);
        }

        return back()->with('status', 'Impostazioni aggiornate.');
    }

    private function values(): array
    {
        return collect($this->settings())
            ->mapWithKeys(fn (array $definition, string $key) => [
                $key => Setting::getValue($key, $definition['default'] ?? null),
            ])
            ->all();
    }

    private function rules(): array
    {
        return collect($this->settings())
            ->mapWithKeys(fn (array $definition, string $key) => [$key => $definition['rules']])
            ->all();
    }

    private function settings(): array
    {
        return [
            'practice_name' => [
                'label' => 'Nome studio',
                'group' => 'studio',
                'type' => 'text',
                'rules' => ['required', 'string', 'max:255'],
                'default' => config('app.name', 'Studio Osteopatico'),
            ],
            'practice_owner' => [
                'label' => 'Titolare / professionista',
                'group' => 'studio',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
                'default' => null,
            ],
            'practice_email' => [
                'label' => 'Email studio',
                'group' => 'studio',
                'type' => 'email',
                'rules' => ['nullable', 'email', 'max:255'],
                'default' => null,
            ],
            'practice_phone' => [
                'label' => 'Telefono studio',
                'group' => 'studio',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
                'default' => null,
            ],
            'practice_address' => [
                'label' => 'Indirizzo studio',
                'group' => 'studio',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
                'default' => null,
            ],
            'vat_number' => [
                'label' => 'Partita IVA',
                'group' => 'billing',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:50'],
                'default' => null,
            ],
            'tax_code' => [
                'label' => 'Codice fiscale studio',
                'group' => 'billing',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:50'],
                'default' => null,
            ],
            'invoice_prefix' => [
                'label' => 'Prefisso fatture',
                'group' => 'billing',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:20'],
                'default' => 'F',
            ],
            'default_session_fee' => [
                'label' => 'Importo seduta predefinito',
                'group' => 'operations',
                'type' => 'number',
                'rules' => ['nullable', 'numeric', 'min:0'],
                'default' => null,
            ],
            'appointment_duration' => [
                'label' => 'Durata seduta predefinita (minuti)',
                'group' => 'operations',
                'type' => 'number',
                'rules' => ['nullable', 'integer', 'min:1'],
                'default' => '60',
            ],
        ];
    }
}
