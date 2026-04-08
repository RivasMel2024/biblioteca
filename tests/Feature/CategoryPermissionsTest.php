<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_c1_bibliotecario_lista_categorias(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        Category::create(['name' => 'Novela', 'description' => 'Narrativa']);

        $response = $this->actingAs($bibliotecario)->getJson('/api/v1/categories');

        $response->assertStatus(200);

        $payload = $response->json();
        $categories = $payload['data'] ?? $payload;

        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);
        $this->assertArrayHasKey('id', $categories[0]);
        $this->assertArrayHasKey('name', $categories[0]);
    }

    public function test_c2_estudiante_lista_categorias(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Category::create(['name' => 'Historia', 'description' => 'Ensayo historico']);

        $response = $this->actingAs($estudiante)->getJson('/api/v1/categories');

        $response->assertStatus(200);
    }

    public function test_c3_admin_no_puede_listar_categorias(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->getJson('/api/v1/categories');

        $response->assertStatus(403);
    }

    public function test_c4_bibliotecario_crea_categoria(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $response = $this->actingAs($bibliotecario)->postJson('/api/v1/categories', [
            'name' => 'Ciencia',
            'description' => 'Libros de ciencia',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('name', 'Ciencia')
            ->assertJsonPath('description', 'Libros de ciencia');

        $this->assertDatabaseHas('categories', [
            'name' => 'Ciencia',
            'description' => 'Libros de ciencia',
        ]);
    }

    public function test_c5_estudiante_no_puede_crear_categoria(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $response = $this->actingAs($estudiante)->postJson('/api/v1/categories', [
            'name' => 'Prohibida',
            'description' => 'No debe poder crear',
        ]);

        $response->assertStatus(403);
    }

    public function test_c6_nombre_duplicado_retorna_422_en_creacion(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        Category::create([
            'name' => 'Tecnologia',
            'description' => 'Primera',
        ]);

        $response = $this->actingAs($bibliotecario)->postJson('/api/v1/categories', [
            'name' => 'Tecnologia',
            'description' => 'Duplicada',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_c7_bibliotecario_actualiza_categoria(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $category = Category::create([
            'name' => 'Filosofia',
            'description' => 'Original',
        ]);

        $response = $this->actingAs($bibliotecario)->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Filosofia Moderna',
            'description' => 'Actualizada',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('name', 'Filosofia Moderna')
            ->assertJsonPath('description', 'Actualizada');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Filosofia Moderna',
            'description' => 'Actualizada',
        ]);
    }

    public function test_c8_actualizar_con_nombre_duplicado_retorna_422(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $categoryA = Category::create([
            'name' => 'Arte',
            'description' => 'Categoria A',
        ]);

        $categoryB = Category::create([
            'name' => 'Musica',
            'description' => 'Categoria B',
        ]);

        $response = $this->actingAs($bibliotecario)->putJson("/api/v1/categories/{$categoryB->id}", [
            'name' => 'Arte',
            'description' => 'Intento duplicado',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseHas('categories', [
            'id' => $categoryA->id,
            'name' => 'Arte',
        ]);
    }

    public function test_c9_bibliotecario_elimina_categoria(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $category = Category::create([
            'name' => 'Infantil',
            'description' => 'Para ninos',
        ]);

        $response = $this->actingAs($bibliotecario)->deleteJson("/api/v1/categories/{$category->id}");

        $response
            ->assertStatus(200)
            ->assertJsonPath('message', 'Categoría eliminada con éxito.');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_c10_eliminar_categoria_con_libros_deja_category_id_en_null(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $category = Category::create([
            'name' => 'Referencia',
            'description' => 'Con libros asociados',
        ]);

        $book = Book::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($bibliotecario)->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'category_id' => null,
        ]);
    }
}
