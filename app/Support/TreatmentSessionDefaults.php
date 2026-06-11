<?php

namespace App\Support;

use App\Models\Setting;

class TreatmentSessionDefaults
{
    public static function rates(): array
    {
        $rates = json_decode(Setting::getValue('treatment_session_rates', '[]'), true) ?: [];

        if ($rates !== []) {
            return $rates;
        }

        return [
            [
                'name' => 'Seduta di manipolazione osteopatica',
                'amount' => 40.00,
                'active' => true,
                'default' => true,
            ],
            [
                'name' => 'Prima visita osteopatica',
                'amount' => 70.00,
                'active' => true,
                'default' => false,
            ],
        ];
    }

    public static function activeRates(): array
    {
        $active = collect(self::rates())
            ->filter(fn (array $rate) => (bool) ($rate['active'] ?? false))
            ->values()
            ->all();

        return $active !== [] ? $active : self::rates();
    }

    public static function defaultRate(): array
    {
        return collect(self::activeRates())
            ->first(fn (array $rate) => (bool) ($rate['default'] ?? false))
            ?? self::activeRates()[0]
            ?? [
                'name' => 'Seduta osteopatica',
                'amount' => 0,
                'active' => true,
                'default' => true,
            ];
    }
}
