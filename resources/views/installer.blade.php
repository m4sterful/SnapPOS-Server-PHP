@extends('layouts.app')

@section('content')
    <h1>Welcome to SnapPOS PHP Server</h1>
    <p>
        Before getting started, we need some information about your database. This installer works like a classic
        WordPress-style setup screen and will save the credentials into <span class="mono">config.php</span>.
    </p>

    @if (!empty($errors))
        <div class="notice error">
            <strong>We hit a problem while saving your configuration:</strong>
            <ul>
                @foreach ($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="/install">
        @csrf
        <div class="grid">
            <div>
                <label for="db_name">Database Name</label>
                <input id="db_name" name="db_name" type="text" value="{{ old('db_name', $config['db_name']) }}" required>
            </div>
            <div>
                <label for="db_host">Database Server</label>
                <input id="db_host" name="db_host" type="text" value="{{ old('db_host', $config['db_host']) }}" required>
            </div>
            <div>
                <label for="db_port">Port</label>
                <input id="db_port" name="db_port" type="number" value="{{ old('db_port', $config['db_port']) }}" required>
            </div>
            <div>
                <label for="db_user">Database Username</label>
                <input id="db_user" name="db_user" type="text" value="{{ old('db_user', $config['db_user']) }}" required>
            </div>
            <div>
                <label for="db_password">Database Password</label>
                <input id="db_password" name="db_password" type="password" value="{{ old('db_password', $config['db_password']) }}">
            </div>
            <div>
                <label for="db_prefix">Table Prefix</label>
                <input id="db_prefix" name="db_prefix" type="text" value="{{ old('db_prefix', $config['db_prefix']) }}" placeholder="Optional prefix such as snappos_">
            </div>
        </div>

        <div style="margin: 20px 0 24px;">
            <label class="checkbox" for="seed_test_data">
                <input id="seed_test_data" name="seed_test_data" type="checkbox" value="1" @checked(old('seed_test_data', $config['seed_test_data']))>
                <span>
                    <strong>Seed database with test data</strong><br>
                    This will import the manifest seed files and also apply a database-specific seed file when one exists at
                    <span class="mono">Source/LocalDatabaseSchema/seeds/&lt;database name&gt;.seed.json</span>.
                </span>
            </label>
        </div>

        <button type="submit">Save configuration and continue</button>
    </form>
@endsection
