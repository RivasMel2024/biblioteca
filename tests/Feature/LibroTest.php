<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibroTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Matriz de Pruebas - Endpoint: Detalle Libro (GET /api/v1/books/{book})
     *
     * | ID   | Escenario                                    | Entrada                  | Resultado Esperado       |
     * |------|----------------------------------------------|--------------------------|--------------------------|
     * | DL01 | Usuario autenticado ve libro existente       | ID válido, token válido  | 200 + datos del libro    |
     * | DL02 | Verificar estructura de respuesta            | ID válido, token válido  | JSON con campos esperados|
     * | DL03 | Usuario no autenticado es rechazado          | ID válido, sin token     | 401 Unauthorized         |
     * | DL04 | Libro no encontrado                          | ID inexistente           | 404 Not Found            |
     * | DL05 | ID con formato inválido                      | ID no numérico           | 404 Not Found            |
     * | DL06 | Libro disponible muestra estado correcto     | Libro is_available=true  | "Disponible"             |
     * | DL07 | Libro no disponible muestra estado correcto  | Libro is_available=false | "No Disponible"          |
     */

    public function test_usuario_autenticado_puede_ver_detalle_libro()
    {
        // Preparacion
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'title' => 'El Quijote',
            'ISBN' => '9788420412146',
        ]);

        // Ejecucion
        $response = $this->actingAs($user)
            ->getJson("/api/v1/books/{$book->id}");

        // Verificacion
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $book->id,
            'title' => 'El Quijote',
            'ISBN' => '9788420412146',
        ]);
    }

    public function test_estructura_respuesta_detalle_libro()
    {
        // Preparacion
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // Ejecucion
        $response = $this->actingAs($user)
            ->getJson("/api/v1/books/{$book->id}");

        // Verificacion
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'title',
            'description',
            'ISBN',
            'total_copies',
            'available_copies',
            'is_available',
        ]);
    }

    public function test_usuario_no_autenticado_no_puede_ver_detalle_libro()
    {
        // Preparacion
        $book = Book::factory()->create();

        // Ejecucion
        $response = $this->getJson("/api/v1/books/{$book->id}");

        // Verificacion
        $response->assertStatus(401);
    }

    public function test_libro_no_encontrado_retorna_404()
    {
        // Preparacion
        $user = User::factory()->create();
        $idInexistente = 99999;

        // Ejecucion
        $response = $this->actingAs($user)
            ->getJson("/api/v1/books/{$idInexistente}");

        // Verificacion
        $response->assertStatus(404);
    }

    public function test_id_invalido_retorna_404()
    {
        // Preparacion
        $user = User::factory()->create();

        // Ejecucion
        $response = $this->actingAs($user)
            ->getJson('/api/v1/books/abc-invalid');

        // Verificacion
        $response->assertStatus(404);
    }

    public function test_libro_disponible_muestra_estado_disponible()
    {
        // Preparacion
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'is_available' => true,
        ]);

        // Ejecucion
        $response = $this->actingAs($user)
            ->getJson("/api/v1/books/{$book->id}");

        // Verificacion
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'is_available' => 'Disponible',
        ]);
    }

    public function test_libro_no_disponible_muestra_estado_no_disponible()
    {
        // Preparacion
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'is_available' => false,
        ]);

        // Ejecucion
        $response = $this->actingAs($user)
            ->getJson("/api/v1/books/{$book->id}");

        // Verificacion
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'is_available' => 'No Disponible',
        ]);
    }
}
