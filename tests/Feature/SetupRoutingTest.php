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

    public function test_setup_page_is_available_when_application_is_not_installed(): void
    {
        $status = Mockery::mock(InstallationStatus::class, [app(EnvironmentFileManager::class)]);
        $status->shouldReceive('installed')->andReturnFalse();
        $status->shouldReceive('evaluate')->andReturn(['installed' => false, 'reasons' => []]);
        $this->app->instance(InstallationStatus::class, $status);

        $this->get('/setup')
            ->assertOk()
            ->assertSee('Install SnapPOS Server');
    }

    public function test_setup_page_is_not_available_after_installation(): void
    {
        $status = Mockery::mock(InstallationStatus::class, [app(EnvironmentFileManager::class)]);
        $status->shouldReceive('installed')->andReturnTrue();
        $this->app->instance(InstallationStatus::class, $status);

        $this->get('/setup')->assertNotFound();
    }

    public function test_api_root_requires_setup_when_application_is_not_installed(): void
    {
        $status = Mockery::mock(InstallationStatus::class, [app(EnvironmentFileManager::class)]);
        $status->shouldReceive('installed')->andReturnFalse();
        $this->app->instance(InstallationStatus::class, $status);

        $this->getJson('/api')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Application setup is required before API access is available.')
            ->assertJsonPath('setup_url', route('setup.show'));
    }

    public function test_stub_module_endpoint_returns_json_when_application_is_installed(): void
    {
        $status = Mockery::mock(InstallationStatus::class, [app(EnvironmentFileManager::class)]);
        $status->shouldReceive('installed')->andReturnTrue();
        $this->app->instance(InstallationStatus::class, $status);

        $this->getJson('/api/admin')
            ->assertOk()
            ->assertJson([
                'module' => 'admin',
                'status' => 'stub',
            ]);
    }

    public function test_system_endpoint_returns_plain_text_pong_for_get_requests(): void
    {
        $status = Mockery::mock(InstallationStatus::class, [app(EnvironmentFileManager::class)]);
        $status->shouldReceive('installed')->andReturnTrue();
        $this->app->instance(InstallationStatus::class, $status);

        $this->get('/api/system')
            ->assertOk()
            ->assertSeeText('pong')
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }

    public function test_system_endpoint_returns_plain_text_pong_for_non_get_requests(): void
    {
        $status = Mockery::mock(InstallationStatus::class, [app(EnvironmentFileManager::class)]);
        $status->shouldReceive('installed')->andReturnTrue();
        $this->app->instance(InstallationStatus::class, $status);

        $this->post('/api/system')
            ->assertOk()
            ->assertSeeText('pong')
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }
}
