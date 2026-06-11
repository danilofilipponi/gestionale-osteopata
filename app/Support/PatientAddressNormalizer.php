<?php

namespace App\Support;

class PatientAddressNormalizer
{
    public static function normalize(array $data): array
    {
        $address = self::clean($data['address'] ?? '');
        $streetNumber = self::clean($data['street_number'] ?? '');
        $city = self::clean($data['city'] ?? '');
        $province = strtoupper(self::clean($data['province'] ?? ''));
        $postalCode = self::clean($data['postal_code'] ?? '');

        $parsed = self::parseCombinedAddress($address);

        if ($parsed) {
            $address = $parsed['address'];
            $postalCode = $postalCode ?: $parsed['postal_code'];
            $city = $city ?: $parsed['city'];

            if ($province === '' && strlen($parsed['province']) === 2) {
                $province = strtoupper($parsed['province']);
            }
        }

        [$address, $detectedStreetNumber] = self::splitStreetNumber($address);
        $streetNumber = $streetNumber ?: $detectedStreetNumber;

        $data['address'] = self::clean($address);
        $data['street_number'] = mb_substr(self::clean($streetNumber), 0, 20);
        $data['city'] = $city;
        $data['province'] = mb_substr($province, 0, 2);
        $data['postal_code'] = mb_substr($postalCode, 0, 10);

        return $data;
    }

    public static function splitStreetNumber(string $address): array
    {
        $address = self::clean($address);

        if (preg_match('/^(.*?)[,\s]+((?:snc|s\/n)|\d+[a-zA-Z\/-]*)$/i', $address, $matches)) {
            return [self::clean($matches[1]), strtoupper(self::clean($matches[2]))];
        }

        return [$address, ''];
    }

    private static function parseCombinedAddress(string $address): ?array
    {
        $address = self::clean($address);

        if (! preg_match('/\b(?<postal_code>\d{5})\b/u', $address, $capMatch, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $street = self::clean(substr($address, 0, $capMatch['postal_code'][1]));
        $street = rtrim($street, " \t\n\r\0\x0B,;-");

        $tail = self::clean(substr($address, $capMatch['postal_code'][1] + 5));
        $tail = preg_replace('/\([A-Z]{2}\)$/i', '', $tail);
        $tail = self::clean($tail);

        $city = $tail;
        $province = '';

        if (preg_match('/^(?<city>.*?)(?:\s*[-,]\s*(?<province>[A-Za-z]{2}|[A-Za-zÀ-ÿ\' ]+))$/u', $tail, $tailMatch)) {
            $city = self::clean($tailMatch['city']);
            $province = self::clean($tailMatch['province']);
        }

        return [
            'address' => $street,
            'postal_code' => self::clean($capMatch['postal_code'][0]),
            'city' => $city,
            'province' => $province,
        ];
    }

    private static function clean(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }
}
