<?php

namespace App\Http\Controllers;

use App\Support\Setup\EnvironmentFileManager;
use App\Support\Setup\InstallationStatus;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class SetupController extends Controller
{
    public function __construct(private readonly EnvironmentFileManager $environmentFileManager) {}

    public function show(InstallationStatus $installationStatus): View
    {
        $status = $installationStatus->evaluate();

        return view('setup', [
            'defaults' => [
                'app_name' => env('APP_NAME', 'SnapPOS Server'),
                'app_url' => env('APP_URL', 'http://localhost'),
                'app_env' => env('APP_ENV', 'production'),
                'db_connection' => env('DB_CONNECTION', 'mysql'),
                'db_host' => env('DB_HOST', '127.0.0.1'),
                'db_port' => env('DB_PORT', '3306'),
                'db_database' => env('DB_DATABASE', database_path('database.sqlite')),
                'db_username' => env('DB_USERNAME', ''),
                'db_password' => env('DB_PASSWORD', ''),
                'seed_database' => false,
            ],
            'errors' => $status['installed'] ? [] : $status['reasons'],
            'status_message' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'app_name' => ['required', 'string', 'max:255'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'app_env' => ['required', 'string', 'max:50'],
            'db_connection' => ['required', 'in:mysql,mariadb,sqlite'],
            'db_host' => ['nullable', 'string', 'max:255'],
            'db_port' => ['nullable', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['nullable', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'seed_database' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($request->string('db_connection')->value() !== 'sqlite') {
                foreach (['db_host', 'db_port', 'db_username'] as $field) {
                    if (blank($request->input($field))) {
                        $validator->errors()->add($field, strtoupper($field).' is required for mysql and mariadb installations.');
                    }
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The setup payload is invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $values = [
            'APP_NAME' => (string) $request->string('app_name'),
            'APP_URL' => (string) $request->string('app_url'),
            'APP_ENV' => (string) $request->string('app_env'),
            'APP_KEY' => $this->generateApplicationKey(),
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => (string) $request->string('db_connection'),
            'DB_HOST' => $request->string('db_connection')->value() === 'sqlite' ? '' : (string) $request->string('db_host'),
            'DB_PORT' => $request->string('db_connection')->value() === 'sqlite' ? '' : (string) $request->input('db_port', ''),
            'DB_DATABASE' => (string) $request->string('db_database'),
            'DB_USERNAME' => $request->string('db_connection')->value() === 'sqlite' ? '' : (string) $request->string('db_username'),
            'DB_PASSWORD' => $request->string('db_connection')->value() === 'sqlite' ? '' : (string) $request->input('db_password', ''),
        ];

        try {
            $this->applyRuntimeConfiguration($values);

            if ($values['DB_CONNECTION'] === 'sqlite') {
                $this->ensureSqliteDatabaseExists($values['DB_DATABASE']);
            }

            $this->assertDatabaseConnectionIsValid($values['DB_CONNECTION']);
        } catch (Throwable $throwable) {
            return response()->json([
                'message' => 'Database connection failed. Please review the setup values and try again.',
                'errors' => [$throwable->getMessage()],
            ], 422);
        }

        try {
            Artisan::call('config:clear');
            $this->applyRuntimeConfiguration($values);
            Artisan::call('migrate', ['--force' => true]);

            if ($request->boolean('seed_database')) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\GeneratedLocalSchemaSeeder', '--force' => true]);
            }

            $this->queueEnvironmentPersistence($values);
        } catch (Throwable $throwable) {
            return response()->json([
                'message' => 'Application setup failed after connecting successfully.',
                'errors' => [$throwable->getMessage()],
            ], 500);
        }

        return response()->json([
            'message' => 'Installation completed successfully.',
            'installed' => true,
            'api_url' => route('home'),
        ], 201);
    }

    /**
     * @param  array<string, string>  $values
     */
    protected function applyRuntimeConfiguration(array $values): void
    {
        foreach ($values as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        Config::set('app.name', $values['APP_NAME']);
        Config::set('app.url', $values['APP_URL']);
        Config::set('app.key', $values['APP_KEY']);
        Config::set('database.default', $values['DB_CONNECTION']);

        Config::set('database.connections.sqlite.database', $values['DB_DATABASE']);

        foreach (['mysql', 'mariadb'] as $connection) {
            Config::set("database.connections.{$connection}.host", $values['DB_HOST']);
            Config::set("database.connections.{$connection}.port", $values['DB_PORT']);
            Config::set("database.connections.{$connection}.database", $values['DB_DATABASE']);
            Config::set("database.connections.{$connection}.username", $values['DB_USERNAME']);
            Config::set("database.connections.{$connection}.password", $values['DB_PASSWORD']);
        }

        DB::purge($values['DB_CONNECTION']);
        DB::setDefaultConnection($values['DB_CONNECTION']);
    }

    /**
     * @param  array<string, string>  $values
     */
    protected function queueEnvironmentPersistence(array $values): void
    {
        app()->terminating(function () use ($values): void {
            $this->environmentFileManager->write($values);
        });
    }

    protected function generateApplicationKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    protected function assertDatabaseConnectionIsValid(string $connection): void
    {
        app(DatabaseManager::class)->connection($connection)->getPdo();
    }

    protected function ensureSqliteDatabaseExists(string $databasePath): void
    {
        if ($databasePath === ':memory:') {
            return;
        }

        $fullPath = str_starts_with($databasePath, DIRECTORY_SEPARATOR)
            ? $databasePath
            : base_path($databasePath);

        $directory = dirname($fullPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (! is_file($fullPath)) {
            touch($fullPath);
        }
    }
}
