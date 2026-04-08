<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles required by register validation/assignment flow.
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('bibliotecario', 'web');
        Role::findOrCreate('estudiante', 'web');
    }

    public function test_a1_login_valido_retorna_token_con_usuario(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('test123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'test123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'roles'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonMissingPath('user.password');
    }

    public function test_a2_login_invalido_retorna_422(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-pass'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-pass',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_a3_registro_valido_retorna_201_con_token_y_rol(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Ana Tester',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'estudiante',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'roles'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'ana@example.com')
            ->assertJsonPath('user.roles.0.name', 'estudiante')
            ->assertJsonMissingPath('user.password');
    }

    public function test_a4_registro_con_email_duplicado_retorna_422(): void
    {
        User::factory()->create([
            'email' => 'duplicado@example.com',
        ]);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Otro Usuario',
            'email' => 'duplicado@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'estudiante',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_a5_logout_invalida_todos_los_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('active-token')->plainTextToken;
        $user->createToken('other-token');

        $this->assertEquals(2, $user->tokens()->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');

        $response
            ->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);

        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    public function test_a6_rutas_protegidas_sin_token_retorna_401(): void
    {
        $this->getJson('/api/v1/profile')->assertStatus(401);
        $this->postJson('/api/v1/logout')->assertStatus(401);
    }

    public function test_a7_profile_devuelve_datos_del_usuario_autenticado_con_roles_sin_password(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['estudiante']);

        $token = $user->createToken('profile-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/profile');

        $response
            ->assertStatus(200)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.roles.0.name', 'estudiante')
            ->assertJsonMissingPath('user.password');

        $otherUser = User::factory()->create();
        $response->assertJsonMissing(['email' => $otherUser->email]);
    }

    public function test_a8_password_no_se_expone_en_respuestas_de_register_login_y_profile(): void
    {
        $registerResponse = $this->postJson('/api/v1/register', [
            'name' => 'Seguro User',
            'email' => 'seguro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'estudiante',
        ]);

        $registerResponse
            ->assertStatus(201)
            ->assertJsonMissingPath('user.password');

        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'seguro@example.com',
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertStatus(200)
            ->assertJsonMissingPath('user.password');

        $profileToken = $loginResponse->json('access_token');
        $profileResponse = $this->withHeader('Authorization', 'Bearer ' . $profileToken)
            ->getJson('/api/v1/profile');

        $profileResponse
            ->assertStatus(200)
            ->assertJsonMissingPath('user.password');
    }

    public function test_it_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/v1/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logged out successfully']);
        
        // Verificar que los tokens fueron eliminados
        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_it_can_see_user_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/v1/profile');

        $response->assertStatus(200);
        $response->assertJson([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}
