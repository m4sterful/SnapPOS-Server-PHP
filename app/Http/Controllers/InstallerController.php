<?php

namespace App\Http\Controllers;

use App\Support\LegacyConfig;
use App\Support\LocalSchemaManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class InstallerController extends Controller
{
    public function __construct(private readonly LocalSchemaManager $schemaManager) {}

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'db_name' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\\-]+$/'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_user' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'db_prefix' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_]*$/'],
            'seed_test_data' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect('/')
                ->withInput()
                ->with('installer_errors', $validator->errors()->all());
        }

        $config = [
            'db_name' => (string) $request->string('db_name'),
            'db_host' => (string) $request->string('db_host'),
            'db_port' => (string) $request->integer('db_port'),
            'db_user' => (string) $request->string('db_user'),
            'db_password' => (string) $request->input('db_password', ''),
            'db_prefix' => (string) $request->string('db_prefix'),
            'seed_test_data' => $request->boolean('seed_test_data'),
        ];

        try {
            LegacyConfig::write($config);
            $this->schemaManager->synchronize($config);
        } catch (Throwable $throwable) {
            return redirect('/')
                ->withInput()
                ->with('installer_errors', [$throwable->getMessage()]);
        }

        return redirect('/')->with('status_message', 'Configuration saved and schema synchronized successfully.');
    }
}
