<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Services\Configuracion\ConfiguracionService;
use Mockery;

class ConfiguracionErrorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $mock = Mockery::mock(ConfiguracionService::class);
        $mock->shouldReceive('getTipoExplotacion')->andThrow(new \Exception('Test Error'));
        $mock->shouldReceive('getMetodoRiego')->andThrow(new \Exception('Test Error'));
        $mock->shouldReceive('getPhSuelo')->andThrow(new \Exception('Test Error'));
        $mock->shouldReceive('getTexturaSuelo')->andThrow(new \Exception('Test Error'));
        $mock->shouldReceive('getFuenteAgua')->andThrow(new \Exception('Test Error'));
        $mock->shouldReceive('getSexo')->andThrow(new \Exception('Test Error'));
        $mock->shouldReceive('getTipoRelieve')->andThrow(new \Exception('Test Error'));
        
        $this->app->instance(ConfiguracionService::class, $mock);
    }

    public function test_get_tipo_explotacion_error()
    {
        $response = $this->withHeader('X-API-VERSION', '2')->getJson('/api/configuracion/tipo-explotacion');
        $response->assertStatus(500)->assertJson(['success' => false]);
    }

    public function test_get_metodo_riego_error()
    {
        $response = $this->withHeader('X-API-VERSION', '2')->getJson('/api/configuracion/metodo-riego');
        $response->assertStatus(500)->assertJson(['success' => false]);
    }

    public function test_get_ph_suelo_error()
    {
        $response = $this->withHeader('X-API-VERSION', '2')->getJson('/api/configuracion/ph-suelo');
        $response->assertStatus(500)->assertJson(['success' => false]);
    }

    public function test_get_textura_suelo_error()
    {
        $response = $this->withHeader('X-API-VERSION', '2')->getJson('/api/configuracion/textura-suelo');
        $response->assertStatus(500)->assertJson(['success' => false]);
    }

    public function test_get_fuente_agua_error()
    {
        $response = $this->withHeader('X-API-VERSION', '2')->getJson('/api/configuracion/fuente-agua');
        $response->assertStatus(500)->assertJson(['success' => false]);
    }

    public function test_get_sexo_error()
    {
        $response = $this->withHeader('X-API-VERSION', '2')->getJson('/api/configuracion/sexo');
        $response->assertStatus(500)->assertJson(['success' => false]);
    }

    public function test_get_tipo_relieve_error()
    {
        $response = $this->withHeader('X-API-VERSION', '2')->getJson('/api/configuracion/tipo-relieve');
        $response->assertStatus(500)->assertJson(['success' => false]);
    }
}
