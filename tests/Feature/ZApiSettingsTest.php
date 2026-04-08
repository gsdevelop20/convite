<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\ZApiSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ZApiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_save_zapi_settings(): void
    {
        $user = User::factory()->create();

        $service = Mockery::mock(ZApiSettingsService::class)->makePartial();
        $service->shouldReceive('getConfig')->andReturn([
            'instance_id' => 'instance-123',
            'token' => 'stored-token',
            'client_token' => 'stored-client-token',
            'base_url' => 'https://api.z-api.io',
        ]);
        $service->shouldReceive('save')->once()->with([
            'instance_id' => 'instance-123',
            'token' => 'stored-token',
            'client_token' => 'stored-client-token',
        ]);
        $this->app->instance(ZApiSettingsService::class, $service);

        $response = $this->actingAs($user)->put(route('settings.zapi.update'), [
            'instance_id' => 'instance-123',
            'token' => '',
            'client_token' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
    }
}
