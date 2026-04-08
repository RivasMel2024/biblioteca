<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_u1_admin_lista_usuarios(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        User::factory()->count(3)->create()->each(function (User $user) {
            $user->assignRole('estudiante');
        });

        $response = $this->actingAs($admin)->getJson('/api/v1/users');

        $response->assertStatus(200);

        $payload = $response->json();
        $users = $payload['data'] ?? $payload;

        $this->assertIsArray($users);
        $this->assertNotEmpty($users);
        $this->assertArrayHasKey('id', $users[0]);
        $this->assertArrayHasKey('name', $users[0]);
        $this->assertArrayHasKey('email', $users[0]);
        $this->assertArrayHasKey('roles', $users[0]);
    }

    public function test_u2_admin_ve_un_usuario_por_id(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('bibliotecario');

        $response = $this->actingAs($admin)->getJson("/api/v1/users/{$targetUser->id}");

        $response
            ->assertStatus(200)
            ->assertJsonPath('id', $targetUser->id)
            ->assertJsonPath('email', $targetUser->email);
    }

    public function test_u3_admin_actualiza_usuario_y_puede_cambiar_rol(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('estudiante');

        $response = $this->actingAs($admin)->putJson("/api/v1/users/{$targetUser->id}", [
            'name' => 'Nombre Actualizado',
            'role' => 'bibliotecario',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('message', 'Usuario actualizado exitosamente.')
            ->assertJsonPath('data.name', 'Nombre Actualizado');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Nombre Actualizado',
        ]);

        $this->assertTrue($targetUser->fresh()->hasRole('bibliotecario'));
    }

    public function test_u4_admin_elimina_usuario_distinto_a_si_mismo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('estudiante');

        $response = $this->actingAs($admin)->deleteJson("/api/v1/users/{$targetUser->id}");

        $response
            ->assertStatus(200)
            ->assertJsonPath('message', 'Usuario eliminado exitosamente.');

        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_u5_admin_no_puede_eliminarse_a_si_mismo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->deleteJson("/api/v1/users/{$admin->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_u6_no_admin_no_puede_listar_usuarios(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $this->actingAs($estudiante)
            ->getJson('/api/v1/users')
            ->assertStatus(403);

        $this->actingAs($bibliotecario)
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    }

    public function test_u7_no_admin_no_puede_ver_otro_usuario(): void
    {
        $targetUser = User::factory()->create();

        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $this->actingAs($estudiante)
            ->getJson("/api/v1/users/{$targetUser->id}")
            ->assertStatus(403);

        $this->actingAs($bibliotecario)
            ->getJson("/api/v1/users/{$targetUser->id}")
            ->assertStatus(403);
    }

    public function test_u8_no_admin_no_puede_editar_otro_usuario(): void
    {
        $targetUser = User::factory()->create();

        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $payload = [
            'name' => 'Intento No Permitido',
        ];

        $this->actingAs($estudiante)
            ->putJson("/api/v1/users/{$targetUser->id}", $payload)
            ->assertStatus(403);

        $this->actingAs($bibliotecario)
            ->putJson("/api/v1/users/{$targetUser->id}", $payload)
            ->assertStatus(403);
    }

    public function test_u9_email_duplicado_al_actualizar_usuario_retorna_422(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $existingEmailUser = User::factory()->create([
            'email' => 'duplicado@example.com',
        ]);
        $existingEmailUser->assignRole('estudiante');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('estudiante');

        $response = $this->actingAs($admin)->putJson("/api/v1/users/{$targetUser->id}", [
            'email' => 'duplicado@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_u10_rol_invalido_en_actualizacion_retorna_422(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('estudiante');

        $response = $this->actingAs($admin)->putJson("/api/v1/users/{$targetUser->id}", [
            'role' => 'superadmin',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_no_admin_no_puede_eliminar_otro_usuario(): void
    {
        $targetUser = User::factory()->create();

        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $this->actingAs($estudiante)
            ->deleteJson("/api/v1/users/{$targetUser->id}")
            ->assertStatus(403);

        $this->actingAs($bibliotecario)
            ->deleteJson("/api/v1/users/{$targetUser->id}")
            ->assertStatus(403);
    }
}
