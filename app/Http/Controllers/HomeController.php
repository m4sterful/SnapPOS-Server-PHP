<?php

namespace App\Http\Controllers;

use App\Support\LegacyConfig;
use App\Support\LocalSchemaManager;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{
    public function __construct(private readonly LocalSchemaManager $schemaManager) {}

    public function index(): View
    {
        $config = LegacyConfig::load();

        if (! LegacyConfig::isConfigured($config)) {
            return view('installer', [
                'config' => $config,
                'errors' => session('installer_errors', []),
            ]);
        }

        $status = null;
        $error = null;

        try {
            $status = $this->schemaManager->synchronize($config);
        } catch (Throwable $throwable) {
            $error = $throwable->getMessage();
        }

        return view('status', [
            'config' => $config,
            'status' => $status,
            'error' => $error,
        ]);
    }
}
