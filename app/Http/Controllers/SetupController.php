<?php

namespace App\Http\Controllers;

use App\Support\Setup\EnvironmentFileManager;
use Illuminate\Http\RedirectResponse;
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

    public function show(): View
    {
        return view('setup', [
            'defaults' => [
                'app_name' => old('app_name', env('APP_NAME', 'SnapPOS Server')),
                'app_url' => old('app_url', env('APP_URL', 'http://localhost')),
                'app_env' => old('app_env', env('APP_ENV', 'production')),
                'db_connection' => old('db_connection', env('DB_CONNECTION', 'mysql')),
                'db_host' => old('db_host', env('DB_HOST', '127.0.0.1')),
                'db_port' => old('db_port', env('DB_PORT', '3306')),
                'db_database' => old('db_database', env('DB_DATABASE', database_path('database.sqlite'))),
                'db_username' => old('db_username', env('DB_USERNAME', '')),
                'db_password' => old('db_password', env('DB_PASSWORD', '')),
                'seed_database' => old('seed_database', false),
            ],
            'errors' => session('setup_errors', []),
            'status_message' => session('status_message'),
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            return redirect()->route('setup.show')
                ->withInput()
                ->with('setup_errors', $validator->errors()->all());
        }

        $values = [
            'APP_NAME' => (string) $request->string('app_name'),
            'APP_URL' => (string) $request->string('app_url'),
            'APP_ENV' => (string) $request->string('app_env'),
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => (string) $request->string('db_connection'),
            'DB_HOST' => $request->string('db_connection')->value() === 'sqlite' ? '' : (string) $request->string('db_host'),
            'DB_PORT' => $request->string('db_connection')->value() === 'sqlite' ? '' : (string) $request->input('db_port', ''),
            'DB_DATABASE' => (string) $request->string('db_database'),
            'DB_USERNAME' => $request->string('db_connection')->value() === 'sqlite' ? '' : (string) $request->string('db_username'),
            'DB_PASSWORD' => $request->string('db_connection')->value() === 'sqlite' ? '' : (string) $request->input('db_password', ''),
        ];

        try {
            $this->environmentFileManager->write($values);
            $this->applyRuntimeConfiguration($values);

            if ($values['DB_CONNECTION'] === 'sqlite') {
                $this->ensureSqliteDatabaseExists($values['DB_DATABASE']);
            }

            Artisan::call('key:generate', ['--force' => true]);
            Artisan::call('config:clear');
            Artisan::call('migrate', ['--force' => true]);

            if ($request->boolean('seed_database')) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\GeneratedLocalSchemaSeeder', '--force' => true]);
            }
        } catch (Throwable $throwable) {
            return redirect()->route('setup.show')
                ->withInput()
                ->with('setup_errors', [$throwable->getMessage()]);
        }

        return redirect()->route('home')->with('status_message', 'Installation completed successfully.');
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
        Config::set('database.default', $values['DB_CONNECTION']);

        Config::set('database.connections.sqlite.database', $values['DB_DATABASE']);

        foreach (['mysql', 'mariadb'] as $connection) {
            Config::set("database.connections.{$connection}.host", $values['DB_HOST']);
            Config::set("database.connections.{$connection}.port", $values['DB_PORT']);
            Config::set("database.connections.{$connection}.database", $values['DB_DATABASE']);
            Config::set("database.connections.{$connection}.username", $values['DB_USERNAME']);
            Config::set("database.connections.{$connection}.password", $values['DB_PASSWORD']);
        }

        DB::purge();
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
