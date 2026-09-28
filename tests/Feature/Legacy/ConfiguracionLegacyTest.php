<?php

namespace Tests\Feature\Legacy;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;

class ConfiguracionLegacyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Assuming we need an authenticated user
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
    }

    public function test_get_tipo_explotacion_legacy()
    {
        $response = $this->getJson('/api/configuracion/tipo-explotacion');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_metodo_riego_legacy()
    {
        $response = $this->getJson('/api/configuracion/metodo-riego');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_ph_suelo_legacy()
    {
        $response = $this->getJson('/api/configuracion/ph-suelo');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_textura_suelo_legacy()
    {
        $response = $this->getJson('/api/configuracion/textura-suelo');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_fuente_agua_legacy()
    {
        $response = $this->getJson('/api/configuracion/fuente-agua');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_sexo_legacy()
    {
        $response = $this->getJson('/api/configuracion/sexo');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_get_tipo_relieve_legacy()
    {
        $response = $this->getJson('/api/configuracion/tipo-relieve');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }
}
