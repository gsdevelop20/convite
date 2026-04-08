<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ZApiSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ZApiConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_trigger_zapi_connection_test(): void
    {
        $user = User::factory()->create();

        $service = Mockery::mock(ZApiSettingsService::class);
        $service->shouldReceive('getConfig')
            ->once()
            ->andReturn([
                'instance_id' => 'instance-123',
                'token' => 'stored-token',
                'client_token' => 'stored-client-token',
                'base_url' => 'https://api.z-api.io',
            ]);
        $service->shouldReceive('testConnection')
            ->once()
            ->with([
                'instance_id' => 'instance-123',
                'token' => 'stored-token',
                'client_token' => 'stored-client-token',
            ])
            ->andReturn([
                'ok' => true,
                'message' => 'Conexao com a Z-API validada com sucesso. Instancia conectada.',
            ]);

        $this->app->instance(ZApiSettingsService::class, $service);

        $response = $this->actingAs($user)->post(route('settings.zapi.test'), [
            'instance_id' => 'instance-123',
            'token' => '',
            'client_token' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('zapi_test');
    }
}
