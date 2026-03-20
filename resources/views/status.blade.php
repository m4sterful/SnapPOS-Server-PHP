@extends('layouts.app')

@section('title', 'SnapPOS Ready')

@section('content')
    <h1>SnapPOS Server is installed</h1>

    @if (session('status_message'))
        <div class="notice success">{{ session('status_message') }}</div>
    @endif

    <div class="notice">
        <strong>Laravel connection:</strong>
        <span class="mono">{{ $connection }}</span><br>
        <strong>Configured database:</strong>
        <span class="mono">{{ $database }}</span>
    </div>

    <p>
        Database setup is now managed by Laravel’s native migration and seeding workflow. Update the JSON schema,
        regenerate artifacts with <span class="mono">php artisan schema:generate-migrations</span>, review the generated
        files, then run <span class="mono">php artisan migrate</span> and <span class="mono">php artisan db:seed</span>.
    </p>
@endsection
