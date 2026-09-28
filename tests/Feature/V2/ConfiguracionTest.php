<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;

class ConfiguracionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Assuming we need an authenticated user
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
    }

    public function test_get_tipo_explotacion_v2()
    {
        $response = $this->withHeader('X-API-VERSION', '2')
            ->getJson('/api/configuracion/tipo-explotacion');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_metodo_riego_v2()
    {
        $response = $this->withHeader('X-API-VERSION', '2')
            ->getJson('/api/configuracion/metodo-riego');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_ph_suelo_v2()
    {
        $response = $this->withHeader('X-API-VERSION', '2')
            ->getJson('/api/configuracion/ph-suelo');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_textura_suelo_v2()
    {
        $response = $this->withHeader('X-API-VERSION', '2')
            ->getJson('/api/configuracion/textura-suelo');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_fuente_agua_v2()
    {
        $response = $this->withHeader('X-API-VERSION', '2')
            ->getJson('/api/configuracion/fuente-agua');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_sexo_v2()
    {
        $response = $this->withHeader('X-API-VERSION', '2')
            ->getJson('/api/configuracion/sexo');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_tipo_relieve_v2()
    {
        $response = $this->withHeader('X-API-VERSION', '2')
            ->getJson('/api/configuracion/tipo-relieve');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }
}
