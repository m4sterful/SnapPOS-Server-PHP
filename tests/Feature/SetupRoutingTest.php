<?php

namespace Tests\Feature;

use App\Support\Setup\EnvironmentFileManager;
use App\Support\Setup\InstallationStatus;
use Mockery;
use Tests\TestCase;

class SetupRoutingTest extends TestCase
{
    public function test_root_redirects_to_setup_when_application_is_not_installed(): void
    {
        $status = Mockery::mock(InstallationStatus::class, [app(EnvironmentFileManager::class)]);
        $status->shouldReceive('installed')->andReturnFalse();
        $this->app->instance(InstallationStatus::class, $status);

        $this->get('/')
            ->assertRedirect(route('setup.show'));
    }

    public function test_setup_redirects_home_when_application_is_already_installed(): void
    {
        $status = Mockery::mock(InstallationStatus::class, [app(EnvironmentFileManager::class)]);
        $status->shouldReceive('installed')->andReturnTrue();
        $this->app->instance(InstallationStatus::class, $status);

        $this->get('/setup')
            ->assertRedirect(route('home'));
    }
}
