@extends('layouts.app')

@section('title', 'SnapPOS Setup')

@section('content')
    <h1>Install SnapPOS Server</h1>
    <p>
        Complete the Laravel first-run setup by saving your application environment settings, generating an
        application key, and running the committed migrations and seeders.
    </p>

    @if (!empty($errors))
        <div class="notice error">
            <strong>Setup could not be completed:</strong>
            <ul>
                @foreach ($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($status_message)
        <div class="notice success">{{ $status_message }}</div>
    @endif

    <form method="post" action="{{ route('setup.store') }}">
        @csrf

        <h2>Application</h2>
        <div class="grid">
            <div>
                <label for="app_name">App Name</label>
                <input id="app_name" name="app_name" type="text" value="{{ $defaults['app_name'] }}" required>
            </div>
            <div>
                <label for="app_url">App URL</label>
                <input id="app_url" name="app_url" type="text" value="{{ $defaults['app_url'] }}" required>
            </div>
            <div>
                <label for="app_env">App Environment</label>
                <input id="app_env" name="app_env" type="text" value="{{ $defaults['app_env'] }}" required>
            </div>
        </div>

        <h2>Database</h2>
        <div class="grid">
            <div>
                <label for="db_connection">Database Driver</label>
                <select id="db_connection" name="db_connection" required>
                    @foreach (['mysql' => 'MySQL', 'mariadb' => 'MariaDB', 'sqlite' => 'SQLite'] as $value => $label)
                        <option value="{{ $value }}" @selected($defaults['db_connection'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="db_host">Database Host</label>
                <input id="db_host" name="db_host" type="text" value="{{ $defaults['db_host'] }}">
            </div>
            <div>
                <label for="db_port">Database Port</label>
                <input id="db_port" name="db_port" type="number" value="{{ $defaults['db_port'] }}">
            </div>
            <div>
                <label for="db_database">Database Name / SQLite Path</label>
                <input id="db_database" name="db_database" type="text" value="{{ $defaults['db_database'] }}" required>
            </div>
            <div>
                <label for="db_username">Database Username</label>
                <input id="db_username" name="db_username" type="text" value="{{ $defaults['db_username'] }}">
            </div>
            <div>
                <label for="db_password">Database Password</label>
                <input id="db_password" name="db_password" type="password" value="{{ $defaults['db_password'] }}">
            </div>
        </div>

        <div style="margin: 20px 0 24px;">
            <label class="checkbox" for="seed_database">
                <input id="seed_database" name="seed_database" type="checkbox" value="1" @checked($defaults['seed_database'])>
                <span>
                    <strong>Run generated seeders after migration</strong><br>
                    This executes <span class="mono">Database\Seeders\GeneratedLocalSchemaSeeder</span> after the
                    Laravel migrations finish.
                </span>
            </label>
        </div>

        <button type="submit">Save .env and install</button>
    </form>
@endsection
