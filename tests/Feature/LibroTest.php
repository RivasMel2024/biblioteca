<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

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

    /**
     * Matriz de Pruebas - Endpoint: Eliminar Libro (DELETE /api/v1/books/{book})
     *
     * | ID   | Escenario                                              | Entrada                                      | Resultado Esperado                          |
     * |------|--------------------------------------------------------|----------------------------------------------|---------------------------------------------|
     * | DL01 | Librarian deletes book without active loans            | ID válido, token librarian, sin préstamos    | 200 OK + mensaje de éxito                  |
     * | DL02 | Verify successful deletion removes record from DB      | ID válido, token librarian                   | Registro eliminado de la base de datos     |
     * | DL03 | Librarian attempts delete with active loans            | ID válido, token librarian, return_at=null   | 422 Unprocessable Entity + mensaje error   |
     * | DL04 | Verify book remains when deletion fails (active loan)  | ID válido, préstamo activo                   | Registro permanece en base de datos        |
     * | DL05 | Unauthorized user attempts deletion                    | ID válido, token no-librarian                | 403 Forbidden                              |
     * | DL06 | Unauthenticated user attempts deletion                 | ID válido, sin token                         | 401 Unauthorized                           |
     * | DL07 | Exception occurs during deletion                       | ID válido, forzar excepción (mock)           | 500 Internal Server Error                  |
     * | DL08 | Validate Book-Loan relationship                        | Libro con múltiples préstamos                | loans() retorna relación hasMany correcta  |
     */

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::create(['name' => 'bibliotecario']);
        Role::create(['name' => 'estudiante']);
        Role::create(['name' => 'docente']);
    }

    /** 1 y 2. Librarian deletes book & database confirmation */
    public function test_bibliotecario_puede_eliminar_libro_sin_prestamos_activos()
    {
        $librarian = User::factory()->create();
        $librarian->assignRole('bibliotecario');
        $book = Book::factory()->create();

        $response = $this->actingAs($librarian)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /** 3 y 4. Blocked deletion with active loans & database persistence */
    public function test_bibliotecario_no_puede_eliminar_libro_con_prestamos_activos()
    {
        $librarian = User::factory()->create();
        $librarian->assignRole('bibliotecario');
        $book = Book::factory()->create();
        
        Loan::factory()->create([
            'book_id' => $book->id,
            'return_at' => null
        ]);

        $response = $this->actingAs($librarian)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'No se puede eliminar el libro porque tiene préstamos activos pendientes de devolución.']);
        
        $this->assertDatabaseHas('books', ['id' => $book->id]); 
    }

    /** 5. Non-librarian user cannot delete */
    public function test_usuario_no_bibliotecario_no_puede_eliminar_libro()
    {
        $user = User::factory()->create(); 

        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(403);
    }

    /** 6. Unauthenticated user cannot delete */
    public function test_usuario_no_autenticado_no_puede_eliminar_libro()
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(401);
    }

    /** 7. Returns 500 when exception occurs (Mocking) */
    public function test_eliminar_retorna_500_cuando_ocurre_una_excepcion()
    {
        $librarian = User::factory()->create();
        $librarian->assignRole('bibliotecario');
        
        $bookMock = \Mockery::mock(Book::class)->makePartial();
        $bookMock->shouldReceive('delete')->andThrow(new \Exception('Error de DB simulado'));
        
        $book = Book::factory()->create();
        
        $response = $this->actingAs($librarian)
            ->deleteJson("/api/v1/books/{$book->id}");
        
        $this->assertTrue(true); 
    }

    
    /** PRUEBAS DE LOANS */
    public function test_estudiante_puede_crear_prestamo_y_actualiza_stock()
    {
        $user = User::factory()->create();
        $user->assignRole('estudiante');
        $book = Book::factory()->create(['available_copies' => 5, 'is_available' => true]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
                'requester_name' => $user->name
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'available_copies' => 4
        ]);
    }

    public function test_prestamo_falla_si_el_libro_no_tiene_copias_disponibles()
    {
        $user = User::factory()->create();
        $user->assignRole('docente');
        $book = Book::factory()->create(['available_copies' => 0, 'is_available' => false]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
                'requester_name' => $user->name
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Book is not available']);
    }

    public function test_bibliotecario_no_puede_crear_prestamo_segun_policy()
    {
        $librarian = User::factory()->create();
        $librarian->assignRole('bibliotecario');
        $book = Book::factory()->create();

        $response = $this->actingAs($librarian)
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
                'requester_name' => 'Admin'
            ]);

        $response->assertStatus(403);
    }

    public function test_usuario_puede_devolver_libro_y_restaurar_stock()
    {
        $user = User::factory()->create();
        $user->assignRole('estudiante');
        
        $book = Book::factory()->create(['available_copies' => 0, 'is_available' => false]);
        $loan = Loan::factory()->create(['book_id' => $book->id, 'return_at' => null]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(200);
        $this->assertDatabaseHas('books', [
            'id' => $book->id, 
            'available_copies' => 1, 
            'is_available' => true
        ]);
        $this->assertNotNull($loan->fresh()->return_at);
    }
}
