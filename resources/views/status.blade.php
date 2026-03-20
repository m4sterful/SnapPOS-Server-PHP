@extends('layouts.app')

@section('content')
    <h1>SnapPOS PHP Server is configured</h1>

    @if (session('status_message'))
        <div class="notice success">{{ session('status_message') }}</div>
    @endif

    <div class="notice">
        <strong>Connection target:</strong>
        <span class="mono">{{ $config['db_host'] }}:{{ $config['db_port'] }}/{{ $config['db_name'] }}</span><br>
        <strong>Table prefix:</strong>
        <span class="mono">{{ $config['db_prefix'] !== '' ? $config['db_prefix'] : '(none)' }}</span>
    </div>

    @if ($error)
        <div class="notice error">
            <strong>Schema synchronization failed.</strong>
            <div class="mono">{{ $error }}</div>
        </div>
    @elseif ($status)
        <p>
            The database connection succeeded and the schema definition from
            <span class="mono">Source/LocalDatabaseSchema</span> has been validated against the configured MySQL database.
        </p>

        <div class="lists">
            <section>
                <h2>Created</h2>
                <ul>
                    @forelse ($status['created'] as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>No new tables were required.</li>
                    @endforelse
                </ul>
            </section>
            <section>
                <h2>Updated</h2>
                <ul>
                    @forelse ($status['updated'] as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>No table alterations were required.</li>
                    @endforelse
                </ul>
            </section>
            <section>
                <h2>Seeded</h2>
                <ul>
                    @forelse ($status['seeded'] as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>Test data seeding was not requested.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @endif
@endsection
