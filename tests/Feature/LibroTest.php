<?php

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

// DL01: Usuario autenticado puede ver el detalle de un libro existente
test('usuario autenticado puede ver detalle libro', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create([
        'title' => 'El Quijote',
        'ISBN' => '9788420412146',
    ]);

    $response = $this->actingAs($user)
        ->getJson("/api/v1/books/{$book->id}");

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'id' => $book->id,
        'title' => 'El Quijote',
        'ISBN' => '9788420412146',
    ]);
});

// DL02: Verificar estructura de respuesta del detalle de libro
test('estructura respuesta detalle libro', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $response = $this->actingAs($user)
        ->getJson("/api/v1/books/{$book->id}");

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
});

// DL03: Usuario no autenticado es rechazado
test('usuario no autenticado no puede ver detalle libro', function () {
    $book = Book::factory()->create();

    $response = $this->getJson("/api/v1/books/{$book->id}");

    $response->assertStatus(401);
});

// DL04: Libro no encontrado retorna 404
test('libro no encontrado retorna 404', function () {
    $user = User::factory()->create();
    $idInexistente = 99999;

    $response = $this->actingAs($user)
        ->getJson("/api/v1/books/{$idInexistente}");

    $response->assertStatus(404);
});

// DL05: ID con formato inválido retorna 404
test('id invalido retorna 404', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/v1/books/abc-invalid');

    $response->assertStatus(404);
});

// DL06: Libro disponible muestra estado "Disponible"
test('libro disponible muestra estado disponible', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create([
        'is_available' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson("/api/v1/books/{$book->id}");

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'is_available' => 'Disponible',
    ]);
});

// DL07: Libro no disponible muestra estado "No Disponible"
test('libro no disponible muestra estado no disponible', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create([
        'is_available' => false,
    ]);

    $response = $this->actingAs($user)
        ->getJson("/api/v1/books/{$book->id}");

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'is_available' => 'No Disponible',
    ]);
});
