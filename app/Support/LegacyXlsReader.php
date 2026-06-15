<?php

namespace App\Support;

use RuntimeException;

class LegacyXlsReader
{
    private const END_OF_CHAIN = 0xFFFFFFFE;
    private const FREE_SECTOR = 0xFFFFFFFF;

    public static function rows(string $path): array
    {
        $bytes = file_get_contents($path);

        if ($bytes === false || substr($bytes, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            throw new RuntimeException('File XLS non leggibile.');
        }

        $reader = new self($bytes);

        return $reader->readRows();
    }

    private function __construct(private string $bytes) {}

    private function readRows(): array
    {
        $workbook = $this->workbookStream();
        $records = $this->records($workbook);
        $strings = $this->sharedStrings($records);
        $rows = [];

        foreach ($records as [$id, $payload]) {
            try {
                match ($id) {
                    0x00FD => $this->readLabelSst($payload, $strings, $rows),
                    0x0204 => $this->readLabel($payload, $rows),
                    0x0203 => $this->readNumber($payload, $rows),
                    0x027E => $this->readRk($payload, $rows),
                    0x00BD => $this->readMulRk($payload, $rows),
                    0x0006 => $this->readFormulaNumber($payload, $rows),
                    default => null,
                };
            } catch (\Throwable) {
                continue;
            }
        }

        ksort($rows);

        return array_map(function (array $columns) {
            ksort($columns);
            $max = max(array_keys($columns));

            return array_map(fn ($index) => $columns[$index] ?? '', range(0, $max));
        }, $rows);
    }

    private function workbookStream(): string
    {
        $sectorSize = 1 << $this->u16(30);
        $miniSectorSize = 1 << $this->u16(32);
        $fatCount = $this->u32(44);
        $directoryStart = $this->u32(48);
        $miniCutoff = $this->u32(56);
        $miniFatStart = $this->u32(60);
        $fatSectorIds = [];

        for ($i = 0; $i < 109; $i++) {
            $sectorId = $this->u32(76 + ($i * 4));
            if ($sectorId !== self::FREE_SECTOR) {
                $fatSectorIds[] = $sectorId;
            }
        }

        $fat = [];
        foreach (array_slice($fatSectorIds, 0, $fatCount) as $sectorId) {
            $sector = $this->sector($sectorId, $sectorSize);
            for ($offset = 0; $offset < strlen($sector); $offset += 4) {
                $fat[] = $this->u32From($sector, $offset);
            }
        }

        $directory = $this->readChain($directoryStart, $fat, $sectorSize);
        $entries = $this->directoryEntries($directory);
        $root = $entries['Root Entry'] ?? null;
        $workbook = $entries['Workbook'] ?? $entries['Book'] ?? null;

        if (! $workbook) {
            throw new RuntimeException('Cartella XLS non trovata.');
        }

        if ($workbook['size'] >= $miniCutoff) {
            return substr($this->readChain($workbook['start'], $fat, $sectorSize), 0, $workbook['size']);
        }

        if (! $root) {
            throw new RuntimeException('Mini stream XLS non trovato.');
        }

        $miniStream = substr($this->readChain($root['start'], $fat, $sectorSize), 0, $root['size']);
        $miniFatBytes = $this->readChain($miniFatStart, $fat, $sectorSize);
        $miniFat = [];

        for ($offset = 0; $offset < strlen($miniFatBytes); $offset += 4) {
            $miniFat[] = $this->u32From($miniFatBytes, $offset);
        }

        $output = '';
        $sectorId = $workbook['start'];
        $seen = [];

        while ($sectorId !== self::END_OF_CHAIN && $sectorId !== self::FREE_SECTOR && isset($miniFat[$sectorId]) && ! isset($seen[$sectorId])) {
            $seen[$sectorId] = true;
            $output .= substr($miniStream, $sectorId * $miniSectorSize, $miniSectorSize);
            $sectorId = $miniFat[$sectorId];
        }

        return substr($output, 0, $workbook['size']);
    }

    private function records(string $workbook): array
    {
        $records = [];
        $offset = 0;
        $length = strlen($workbook);

        while ($offset + 4 <= $length) {
            $id = $this->u16From($workbook, $offset);
            $size = $this->u16From($workbook, $offset + 2);
            $offset += 4;
            $records[] = [$id, substr($workbook, $offset, $size)];
            $offset += $size;
        }

        return $records;
    }

    private function sharedStrings(array $records): array
    {
        $strings = [];

        for ($i = 0; $i < count($records); $i++) {
            [$id, $payload] = $records[$i];
            if ($id !== 0x00FC) {
                continue;
            }

            $parts = [$payload];
            while (isset($records[$i + 1]) && $records[$i + 1][0] === 0x003C) {
                $parts[] = $records[++$i][1];
            }

            $blob = $parts[0];
            $unique = $this->u32From($blob, 4);
            $partIndex = 0;
            $offset = 8;
            $readBytes = function (int $length) use (&$parts, &$partIndex, &$offset): string {
                $output = '';

                while ($length > 0 && isset($parts[$partIndex])) {
                    $available = strlen($parts[$partIndex]) - $offset;

                    if ($available <= 0) {
                        $partIndex++;
                        $offset = 0;
                        continue;
                    }

                    $take = min($length, $available);
                    $output .= substr($parts[$partIndex], $offset, $take);
                    $offset += $take;
                    $length -= $take;
                }

                return $output;
            };
            $readU16 = fn () => unpack('v', $readBytes(2))[1] ?? 0;
            $readU32 = fn () => unpack('V', $readBytes(4))[1] ?? 0;

            for ($index = 0; $index < $unique; $index++) {
                $length = $readU16();
                $flagsByte = $readBytes(1);
                $flags = $flagsByte === '' ? 0 : ord($flagsByte);
                $hasRichText = ($flags & 0x08) !== 0;
                $hasExt = ($flags & 0x04) !== 0;
                $isUnicode = ($flags & 0x01) !== 0;
                $richRuns = 0;
                $extSize = 0;

                if ($hasRichText) {
                    $richRuns = $readU16();
                }

                if ($hasExt) {
                    $extSize = $readU32();
                }

                $value = '';
                $remaining = $length;
                $currentUnicode = $isUnicode;

                while ($remaining > 0 && isset($parts[$partIndex])) {
                    $available = strlen($parts[$partIndex]) - $offset;

                    if ($available <= 0) {
                        $partIndex++;
                        $offset = 0;
                        if (! isset($parts[$partIndex])) {
                            break;
                        }
                        $continueFlags = $readBytes(1);
                        $currentUnicode = $continueFlags !== '' && (ord($continueFlags) & 0x01) !== 0;
                        continue;
                    }

                    $bytesPerChar = $currentUnicode ? 2 : 1;
                    $charsAvailable = intdiv($available, $bytesPerChar);

                    if ($charsAvailable <= 0) {
                        $partIndex++;
                        $offset = 0;
                        if (isset($parts[$partIndex])) {
                            $continueFlags = $readBytes(1);
                            $currentUnicode = $continueFlags !== '' && (ord($continueFlags) & 0x01) !== 0;
                        }
                        continue;
                    }

                    $chars = min($remaining, $charsAvailable);
                    $raw = $readBytes($chars * $bytesPerChar);
                    $value .= $currentUnicode
                        ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE')
                        : mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
                    $remaining -= $chars;
                }

                if ($richRuns > 0) {
                    $readBytes($richRuns * 4);
                }

                if ($extSize > 0) {
                    $readBytes($extSize);
                }

                $strings[] = $value;
            }
        }

        return $strings;
    }

    private function readLabelSst(string $payload, array $strings, array &$rows): void
    {
        $row = $this->u16From($payload, 0);
        $column = $this->u16From($payload, 2);
        $index = $this->u32From($payload, 6);
        $rows[$row][$column] = trim((string) ($strings[$index] ?? ''));
    }

    private function readLabel(string $payload, array &$rows): void
    {
        $row = $this->u16From($payload, 0);
        $column = $this->u16From($payload, 2);
        $length = $this->u16From($payload, 6);
        $rows[$row][$column] = trim(mb_convert_encoding(substr($payload, 8, $length), 'UTF-8', 'ISO-8859-1'));
    }

    private function readNumber(string $payload, array &$rows): void
    {
        $rows[$this->u16From($payload, 0)][$this->u16From($payload, 2)] = $this->doubleFrom($payload, 6);
    }

    private function readFormulaNumber(string $payload, array &$rows): void
    {
        $rows[$this->u16From($payload, 0)][$this->u16From($payload, 2)] = $this->doubleFrom($payload, 6);
    }

    private function readRk(string $payload, array &$rows): void
    {
        $rows[$this->u16From($payload, 0)][$this->u16From($payload, 2)] = $this->rkValue($this->u32From($payload, 6));
    }

    private function readMulRk(string $payload, array &$rows): void
    {
        $row = $this->u16From($payload, 0);
        $firstColumn = $this->u16From($payload, 2);
        $lastColumn = $this->u16From($payload, strlen($payload) - 2);
        $offset = 4;

        for ($column = $firstColumn; $column <= $lastColumn; $column++) {
            $rows[$row][$column] = $this->rkValue($this->u32From($payload, $offset + 2));
            $offset += 6;
        }
    }

    private function rkValue(int $raw): float
    {
        $divideBy100 = ($raw & 1) !== 0;
        $isInteger = ($raw & 2) !== 0;
        $valueBits = $raw & 0xFFFFFFFC;

        if ($isInteger) {
            $value = $valueBits >> 2;
            if ($value & (1 << 29)) {
                $value -= 1 << 30;
            }
            $value = (float) $value;
        } else {
            $value = unpack('d', pack('P', $valueBits << 32))[1];
        }

        return $divideBy100 ? $value / 100 : $value;
    }

    private function directoryEntries(string $directory): array
    {
        $entries = [];

        for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
            $entry = substr($directory, $offset, 128);
            $nameLength = $this->u16From($entry, 64);
            if ($nameLength < 2) {
                continue;
            }

            $name = mb_convert_encoding(substr($entry, 0, $nameLength - 2), 'UTF-8', 'UTF-16LE');
            $entries[$name] = [
                'type' => ord($entry[66]),
                'start' => $this->u32From($entry, 116),
                'size' => unpack('P', substr($entry, 120, 8))[1],
            ];
        }

        return $entries;
    }

    private function readChain(int $start, array $fat, int $sectorSize): string
    {
        $output = '';
        $sectorId = $start;
        $seen = [];

        while ($sectorId !== self::END_OF_CHAIN && $sectorId !== self::FREE_SECTOR && isset($fat[$sectorId]) && ! isset($seen[$sectorId])) {
            $seen[$sectorId] = true;
            $output .= $this->sector($sectorId, $sectorSize);
            $sectorId = $fat[$sectorId];
        }

        return $output;
    }

    private function sector(int $sectorId, int $sectorSize): string
    {
        return substr($this->bytes, ($sectorId + 1) * $sectorSize, $sectorSize);
    }

    private function u16(int $offset): int
    {
        return $this->u16From($this->bytes, $offset);
    }

    private function u32(int $offset): int
    {
        return $this->u32From($this->bytes, $offset);
    }

    private function u16From(string $bytes, int $offset): int
    {
        return unpack('v', substr($bytes, $offset, 2))[1];
    }

    private function u32From(string $bytes, int $offset): int
    {
        return unpack('V', substr($bytes, $offset, 4))[1];
    }

    private function doubleFrom(string $bytes, int $offset): float
    {
        return unpack('d', substr($bytes, $offset, 8))[1];
    }
}
