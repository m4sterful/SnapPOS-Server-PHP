<?php

namespace App\Support\Setup;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallationStatus
{
    public function __construct(private readonly EnvironmentFileManager $environmentFileManager) {}

    /**
     * @return array{installed: bool, reasons: array<int, string>}
     */
    public function evaluate(): array
    {
        $reasons = [];

        if (! $this->environmentFileManager->exists()) {
            $reasons[] = 'The .env file is missing.';

            return ['installed' => false, 'reasons' => $reasons];
        }

        $connection = (string) env('DB_CONNECTION', 'sqlite');

        if (! in_array($connection, ['mysql', 'mariadb', 'sqlite'], true)) {
            $reasons[] = 'DB_CONNECTION must be mysql, mariadb, or sqlite.';
        }

        if (blank((string) env('APP_KEY'))) {
            $reasons[] = 'APP_KEY is missing.';
        }

        if (blank((string) env('APP_NAME'))) {
            $reasons[] = 'APP_NAME is missing.';
        }

        if (blank((string) env('APP_URL'))) {
            $reasons[] = 'APP_URL is missing.';
        }

        if ($connection === 'sqlite') {
            if (blank((string) env('DB_DATABASE'))) {
                $reasons[] = 'DB_DATABASE is missing for the sqlite connection.';
            }
        } else {
            foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'] as $key) {
                if (blank((string) env($key))) {
                    $reasons[] = sprintf('%s is missing for the %s connection.', $key, $connection);
                }
            }
        }

        if ($reasons !== []) {
            return ['installed' => false, 'reasons' => $reasons];
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $throwable) {
            return [
                'installed' => false,
                'reasons' => ['The configured database connection failed: '.$throwable->getMessage()],
            ];
        }

        try {
            if (! Schema::hasTable(Config::get('database.migrations.table', 'migrations'))) {
                $reasons[] = 'The migrations table does not exist yet.';
            }
        } catch (Throwable $throwable) {
            $reasons[] = 'The migrations table check failed: '.$throwable->getMessage();
        }

        return ['installed' => $reasons === [], 'reasons' => $reasons];
    }

    public function installed(): bool
    {
        return $this->evaluate()['installed'];
    }
}
