<?php

namespace Tests\Feature\V2;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected string $pngBytes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-API-VERSION' => '2']);

        Storage::fake('public');
        $this->user = User::factory()->create();

        // Bytes válidos de una imagen PNG 1x1
        $this->pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    }

    public function test_user_can_upload_profile_photo_successfully()
    {
        $file = UploadedFile::fake()->createWithContent('mi_avatar.png', $this->pngBytes);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/photo', [
                'foto' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Foto de perfil actualizada exitosamente.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'foto',
                        'avatar',
                        'profile_photo_url',
                    ],
                ],
            ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->foto);
        Storage::disk('public')->assertExists($this->user->foto);
    }

    public function test_uploading_new_photo_deletes_previous_photo()
    {
        // 1. Crear foto inicial
        $oldFile = UploadedFile::fake()->createWithContent('viejo_avatar.png', $this->pngBytes);
        $oldPath = Storage::disk('public')->putFile('avatars', $oldFile);
        $this->user->update(['foto' => $oldPath]);
        Storage::disk('public')->assertExists($oldPath);

        // 2. Subir nueva foto
        $newFile = UploadedFile::fake()->createWithContent('nuevo_avatar.png', $this->pngBytes);
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/photo', [
                'foto' => $newFile,
            ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertNotEquals($oldPath, $this->user->foto);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($this->user->foto);
    }

    public function test_user_can_delete_profile_photo()
    {
        // 1. Asignar foto existente
        $file = UploadedFile::fake()->createWithContent('para_borrar.png', $this->pngBytes);
        $path = Storage::disk('public')->putFile('avatars', $file);
        $this->user->update(['foto' => $path]);
        Storage::disk('public')->assertExists($path);

        // 2. Solicitar eliminación
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/profile/photo');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Foto de perfil eliminada exitosamente.',
            ])
            ->assertJsonPath('data.user.foto', null);

        $this->user->refresh();
        $this->assertNull($this->user->foto);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_upload_invalid_file_type_returns_422()
    {
        $file = UploadedFile::fake()->create('documento.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/photo', [
                'foto' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['foto']);
    }

    public function test_upload_oversized_file_returns_422()
    {
        // Archivo con cabecera de imagen pero que excede 5MB (ej: 6000 KB)
        $oversizedContent = $this->pngBytes . str_repeat('A', 6000 * 1024);
        $file = UploadedFile::fake()->createWithContent('foto_pesada.png', $oversizedContent);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/photo', [
                'foto' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['foto']);
    }

    public function test_unauthenticated_user_cannot_update_photo()
    {
        $file = UploadedFile::fake()->createWithContent('anonimo.png', $this->pngBytes);

        $response = $this->postJson('/api/profile/photo', [
            'foto' => $file,
        ]);

        $response->assertStatus(401);
    }
}
