<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * ========================================
     * TESTS PARA LIBROS - BIBLIOTECARIO
     * ========================================
     */

    public function test_bibliotecario_puede_crear_libro()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $response = $this->actingAs($bibliotecario)
            ->postJson('/api/v1/books', [
                'title' => 'Libro de Prueba',
                'description' => 'Descripción del libro',
                'ISBN' => '978-3-16-148410-0',
                'total_copies' => 5,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', ['title' => 'Libro de Prueba']);
    }

    public function test_bibliotecario_puede_actualizar_libro()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        $book = Book::factory()->create();

        $response = $this->actingAs($bibliotecario)
            ->putJson("/api/v1/books/{$book->id}", [
                'title' => 'Título Actualizado',
                'description' => $book->description,
                'ISBN' => $book->ISBN,
                'total_copies' => $book->total_copies,
                'available_copies' => $book->available_copies,
                'is_available' => $book->is_available,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('books', ['title' => 'Título Actualizado']);
    }

    public function test_bibliotecario_puede_eliminar_libro()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        $book = Book::factory()->create();

        $response = $this->actingAs($bibliotecario)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * ========================================
     * TESTS PARA LIBROS - ESTUDIANTE
     * ========================================
     */

    public function test_estudiante_no_puede_crear_libro()
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $response = $this->actingAs($estudiante)
            ->postJson('/api/v1/books', [
                'title' => 'Libro de Prueba',
                'description' => 'Descripción del libro',
                'ISBN' => '978-3-16-148410-0',
                'total_copies' => 5,
            ]);

        $response->assertStatus(403);
    }

    public function test_estudiante_no_puede_actualizar_libro()
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create();

        $response = $this->actingAs($estudiante)
            ->putJson("/api/v1/books/{$book->id}", [
                'title' => 'Título Actualizado',
                'description' => $book->description,
                'ISBN' => $book->ISBN,
                'total_copies' => $book->total_copies,
                'available_copies' => $book->available_copies,
                'is_available' => $book->is_available,
            ]);

        $response->assertStatus(403);
    }

    public function test_estudiante_no_puede_eliminar_libro()
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create();

        $response = $this->actingAs($estudiante)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(403);
    }

    public function test_estudiante_puede_ver_libros()
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Book::factory()->create();

        $response = $this->actingAs($estudiante)
            ->getJson('/api/v1/books');

        $response->assertStatus(200);
    }

    /**
     * ========================================
     * TESTS PARA LIBROS - DOCENTE
     * ========================================
     */

    public function test_docente_no_puede_crear_libro()
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');

        $response = $this->actingAs($docente)
            ->postJson('/api/v1/books', [
                'title' => 'Libro de Prueba',
                'description' => 'Descripción del libro',
                'ISBN' => '978-3-16-148410-0',
                'total_copies' => 5,
            ]);

        $response->assertStatus(403);
    }

    public function test_docente_no_puede_actualizar_libro()
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        $book = Book::factory()->create();

        $response = $this->actingAs($docente)
            ->putJson("/api/v1/books/{$book->id}", [
                'title' => 'Título Actualizado',
                'description' => $book->description,
                'ISBN' => $book->ISBN,
                'total_copies' => $book->total_copies,
                'available_copies' => $book->available_copies,
                'is_available' => $book->is_available,
            ]);

        $response->assertStatus(403);
    }

    public function test_docente_no_puede_eliminar_libro()
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        $book = Book::factory()->create();

        $response = $this->actingAs($docente)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(403);
    }

    public function test_docente_puede_ver_libros()
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        Book::factory()->create();

        $response = $this->actingAs($docente)
            ->getJson('/api/v1/books');

        $response->assertStatus(200);
    }

    /**
     * ========================================
     * TESTS PARA PRÉSTAMOS - ESTUDIANTE
     * ========================================
     */

    public function test_estudiante_puede_solicitar_prestamo()
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create([
            'is_available' => true,
            'available_copies' => 3,
        ]);

        $response = $this->actingAs($estudiante)
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
                'requester_name' => 'Juan Pérez',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('loans', ['book_id' => $book->id]);
    }

    public function test_estudiante_puede_ver_prestamos()
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $response = $this->actingAs($estudiante)
            ->getJson('/api/v1/loans');

        $response->assertStatus(200);
    }

    public function test_estudiante_puede_devolver_libro()
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $book = Book::factory()->create(['available_copies' => 2]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'return_at' => null,
        ]);

        $response = $this->actingAs($estudiante)
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(200);
    }

    /**
     * ========================================
     * TESTS PARA PRÉSTAMOS - DOCENTE
     * ========================================
     */

    public function test_docente_puede_solicitar_prestamo()
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        $book = Book::factory()->create([
            'is_available' => true,
            'available_copies' => 3,
        ]);

        $response = $this->actingAs($docente)
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
                'requester_name' => 'Prof. García',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('loans', ['book_id' => $book->id]);
    }

    public function test_docente_puede_ver_prestamos()
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');

        $response = $this->actingAs($docente)
            ->getJson('/api/v1/loans');

        $response->assertStatus(200);
    }

    public function test_docente_puede_devolver_libro()
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        $book = Book::factory()->create(['available_copies' => 2]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'return_at' => null,
        ]);

        $response = $this->actingAs($docente)
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(200);
    }

    /**
     * ========================================
     * TESTS PARA PRÉSTAMOS - BIBLIOTECARIO
     * ========================================
     */

    public function test_bibliotecario_no_puede_solicitar_prestamo()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        $book = Book::factory()->create([
            'is_available' => true,
            'available_copies' => 3,
        ]);

        $response = $this->actingAs($bibliotecario)
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
                'requester_name' => 'Bibliotecario Test',
            ]);

        $response->assertStatus(403);
    }

    public function test_bibliotecario_puede_ver_prestamos()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $response = $this->actingAs($bibliotecario)
            ->getJson('/api/v1/loans');

        $response->assertStatus(200);
    }

    public function test_bibliotecario_puede_devolver_libro()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        $book = Book::factory()->create(['available_copies' => 2]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'return_at' => null,
        ]);

        $response = $this->actingAs($bibliotecario)
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(200);
    }

    /**
     * ========================================
     * TESTS USUARIO SIN ROL
     * ========================================
     */

    public function test_usuario_sin_rol_no_puede_ver_prestamos()
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)
            ->getJson('/api/v1/loans');

        $response->assertStatus(403);
    }

    public function test_usuario_sin_rol_no_puede_solicitar_prestamo()
    {
        $usuario = User::factory()->create();
        $book = Book::factory()->create([
            'is_available' => true,
            'available_copies' => 3,
        ]);

        $response = $this->actingAs($usuario)
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
                'requester_name' => 'Usuario Test',
            ]);

        $response->assertStatus(403);
    }
}
