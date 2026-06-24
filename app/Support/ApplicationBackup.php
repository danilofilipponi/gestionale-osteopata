<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class ApplicationBackup
{
    public static function run(): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Estensione ZIP non disponibile: il backup non puo essere creato.');
        }

        $settings = self::settings();
        $backupDir = self::resolveBackupPath($settings['backup_path']);

        File::ensureDirectoryExists($backupDir);

        $filename = 'gestionale-osteopata-backup-'.now()->format('Ymd-His').'.zip';
        $path = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Non riesco a creare il file di backup.');
        }

        $included = [];
        $warnings = [];

        if ($settings['backup_database']) {
            $zip->addFromString('database/database.sql', self::databaseDump());
            $included[] = 'database';
        }

        if ($settings['backup_uploaded_files']) {
            self::addDirectory($zip, storage_path('app/public'), 'storage/app/public', $backupDir);
            $included[] = 'file caricati';
        }

        if ($settings['backup_generated_documents']) {
            self::addDirectory($zip, storage_path('app/private'), 'storage/app/private', $backupDir);
            $included[] = 'documenti generati';
        }

        if ($settings['backup_logs']) {
            self::addDirectory($zip, storage_path('logs'), 'storage/logs', $backupDir);
            $included[] = 'log';
        }

        if ($settings['backup_encrypt']) {
            $warnings[] = 'Crittografia non ancora applicata: il backup e stato creato senza password.';
        }

        $zip->addFromString('backup-manifest.json', json_encode([
            'created_at' => now()->toDateTimeString(),
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'database_connection' => DB::connection()->getDriverName(),
            'included' => $included,
            'retention_days' => $settings['backup_retention_days'],
            'warnings' => $warnings,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $zip->close();

        $deletedOld = self::deleteOldBackups($backupDir, $settings['backup_retention_days']);

        return [
            'path' => $path,
            'filename' => $filename,
            'size' => File::size($path),
            'included' => $included,
            'deleted_old' => $deletedOld,
            'warnings' => $warnings,
        ];
    }

    public static function restore(string $path, bool $restoreDatabase, bool $restoreFiles): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Estensione ZIP non disponibile: il backup non puo essere letto.');
        }

        if (! $restoreDatabase && ! $restoreFiles) {
            throw new RuntimeException('Seleziona almeno una parte da ripristinare.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Il file caricato non e uno ZIP leggibile.');
        }

        if ($zip->getFromName('backup-manifest.json') === false) {
            $zip->close();

            throw new RuntimeException('Questo file non sembra un backup creato dal gestionale.');
        }

        $restored = [];

        if ($restoreDatabase) {
            $sql = $zip->getFromName('database/database.sql');

            if ($sql === false || trim($sql) === '') {
                $zip->close();

                throw new RuntimeException('Nel backup non e presente il database.');
            }

            self::restoreDatabase($sql);
            $restored[] = 'database';
        }

        if ($restoreFiles) {
            $files = self::restoreFiles($zip);
            $restored[] = 'file e documenti: '.$files;
        }

        $zip->close();

        return [
            'restored' => $restored,
        ];
    }

    private static function settings(): array
    {
        return [
            'backup_path' => Setting::getValue('backup_path', 'storage/app/backups') ?: 'storage/app/backups',
            'backup_retention_days' => max(1, (int) Setting::getValue('backup_retention_days', '30')),
            'backup_database' => Setting::getValue('backup_database', '1') === '1',
            'backup_uploaded_files' => Setting::getValue('backup_uploaded_files', '1') === '1',
            'backup_generated_documents' => Setting::getValue('backup_generated_documents', '1') === '1',
            'backup_logs' => Setting::getValue('backup_logs', '0') === '1',
            'backup_encrypt' => Setting::getValue('backup_encrypt', '0') === '1',
        ];
    }

    private static function resolveBackupPath(string $path): string
    {
        $path = trim($path) ?: 'storage/app/backups';

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return base_path($path);
    }

    private static function addDirectory(ZipArchive $zip, string $directory, string $zipPrefix, string $backupDir): void
    {
        if (! File::isDirectory($directory)) {
            return;
        }

        $backupDir = rtrim(str_replace('\\', '/', realpath($backupDir) ?: $backupDir), '/');

        foreach (File::allFiles($directory) as $file) {
            $realPath = str_replace('\\', '/', $file->getRealPath());

            if (str_starts_with($realPath, $backupDir.'/')) {
                continue;
            }

            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $zip->addFile($file->getRealPath(), trim($zipPrefix.'/'.$relative, '/'));
        }
    }

    private static function databaseDump(): string
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $driver = $connection->getDriverName();
        $tables = self::tables($driver);
        $lines = [
            '-- Gestionale Osteopata database backup',
            '-- Creato il '.now()->toDateTimeString(),
            '',
        ];

        if ($driver === 'mysql') {
            $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
            $lines[] = '';
        }

        foreach ($tables as $table) {
            $quotedTable = self::quoteIdentifier($table, $driver);
            $lines[] = '-- Tabella '.$table;
            $lines[] = 'DROP TABLE IF EXISTS '.$quotedTable.';';
            $lines[] = self::createStatement($table, $driver).';';
            $lines[] = '';

            $statement = $pdo->query('SELECT * FROM '.$quotedTable);
            $insertRows = [];
            $columns = null;

            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $columns ??= array_keys($row);
                $insertRows[] = '('.implode(', ', array_map(
                    fn ($value) => self::quoteValue($pdo, $value),
                    array_values($row),
                )).')';

                if (count($insertRows) >= 100) {
                    $lines[] = self::insertStatement($table, $columns, $insertRows, $driver);
                    $insertRows = [];
                }
            }

            if ($insertRows !== [] && $columns !== null) {
                $lines[] = self::insertStatement($table, $columns, $insertRows, $driver);
            }

            $lines[] = '';
        }

        if ($driver === 'mysql') {
            $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        }

        return implode("\n", $lines)."\n";
    }

    private static function restoreDatabase(string $sql): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }

        foreach (self::splitSqlStatements($sql) as $statement) {
            DB::unprepared($statement);
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    private static function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $escaped = false;
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $next = $sql[$index + 1] ?? null;

            if ($quote === null && $char === '-' && $next === '-') {
                while ($index < $length && ! in_array($sql[$index], ["\n", "\r"], true)) {
                    $index++;
                }

                continue;
            }

            $buffer .= $char;

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if (in_array($char, ["'", '"', '`'], true)) {
                $quote = $char;

                continue;
            }

            if ($char === ';') {
                $statement = trim(substr($buffer, 0, -1));

                if ($statement !== '') {
                    $statements[] = $statement;
                }

                $buffer = '';
            }
        }

        $statement = trim($buffer);

        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    private static function restoreFiles(ZipArchive $zip): int
    {
        $restored = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! $name || str_ends_with($name, '/')) {
                continue;
            }

            if (! str_starts_with($name, 'storage/app/public/') && ! str_starts_with($name, 'storage/app/private/')) {
                continue;
            }

            $destination = self::restoreFileDestination($name);
            File::ensureDirectoryExists(dirname($destination));

            $stream = $zip->getStream($name);

            if (! $stream) {
                continue;
            }

            File::put($destination, stream_get_contents($stream));
            fclose($stream);
            $restored++;
        }

        return $restored;
    }

    private static function restoreFileDestination(string $zipName): string
    {
        $relative = str_replace('\\', '/', substr($zipName, strlen('storage/app/')));
        $relative = ltrim($relative, '/');

        if (str_contains($relative, '..')) {
            throw new RuntimeException('Il backup contiene un percorso file non valido.');
        }

        return storage_path('app/'.$relative);
    }

    private static function tables(string $driver): array
    {
        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')
                ->values()
                ->all();
        }

        if ($driver === 'mysql') {
            return collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
                ->map(function ($row) {
                    $values = array_values((array) $row);

                    return $values[0] ?? null;
                })
                ->filter()
                ->values()
                ->all();
        }

        throw new RuntimeException('Backup database non supportato per questa connessione: '.$driver);
    }

    private static function createStatement(string $table, string $driver): string
    {
        if ($driver === 'sqlite') {
            $row = DB::selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table]);

            return $row->sql ?? '';
        }

        $row = (array) DB::selectOne('SHOW CREATE TABLE '.self::quoteIdentifier($table, $driver));

        return $row['Create Table'] ?? array_values($row)[1] ?? '';
    }

    private static function insertStatement(string $table, array $columns, array $rows, string $driver): string
    {
        $columns = implode(', ', array_map(fn ($column) => self::quoteIdentifier($column, $driver), $columns));

        return 'INSERT INTO '.self::quoteIdentifier($table, $driver).' ('.$columns.') VALUES'."\n"
            .implode(",\n", $rows).';';
    }

    private static function quoteIdentifier(string $identifier, string $driver): string
    {
        $quote = $driver === 'mysql' ? '`' : '"';
        $escaped = str_replace($quote, $quote.$quote, $identifier);

        return $quote.$escaped.$quote;
    }

    private static function quoteValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    private static function deleteOldBackups(string $backupDir, int $retentionDays): int
    {
        if (! File::isDirectory($backupDir)) {
            return 0;
        }

        $limit = now()->subDays($retentionDays)->getTimestamp();
        $deleted = 0;

        foreach (File::files($backupDir) as $file) {
            if ($file->getExtension() !== 'zip' || $file->getMTime() >= $limit) {
                continue;
            }

            File::delete($file->getRealPath());
            $deleted++;
        }

        return $deleted;
    }
}
