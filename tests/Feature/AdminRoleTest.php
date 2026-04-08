<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_ad1_admin_login_valido_retorna_200(): void
    {
        $admin = User::factory()->create([
            'password' => bcrypt('admin12345'),
        ]);
        $admin->assignRole('admin');

        $response = $this->postJson('/api/v1/login', [
            'email' => $admin->email,
            'password' => 'admin12345',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'roles'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $admin->id);
    }

    public function test_ad8_admin_no_puede_crear_categorias_retorna_403(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->postJson('/api/v1/categories', [
            'name' => 'Categoria Admin',
            'description' => 'No permitido',
        ])->assertStatus(403);
    }
}