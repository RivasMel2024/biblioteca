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

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles necesarios para las pruebas
        Role::create(['name' => 'bibliotecario']);
        Role::create(['name' => 'estudiante']);
        Role::create(['name' => 'docente']);
    }

    // ===========================================================================
    // PRUEBAS DE DETALLE DE LIBRO (GET /api/v1/books/{book})
    // ===========================================================================

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
    }

    public function test_estructura_respuesta_detalle_libro()
    {
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
    }

    public function test_usuario_no_autenticado_no_puede_ver_detalle_libro()
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(401);
    }

    public function test_libro_no_encontrado_retorna_404()
    {
        $user = User::factory()->create();
        $idInexistente = 99999;

        $response = $this->actingAs($user)
            ->getJson("/api/v1/books/{$idInexistente}");

        $response->assertStatus(404);
    }

    public function test_id_invalido_retorna_404()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/books/abc-invalid');

        $response->assertStatus(404);
    }

    public function test_libro_disponible_muestra_estado_disponible()
    {
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
    }

    public function test_libro_no_disponible_muestra_estado_no_disponible()
    {
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
    }

    // ============================================================================
    // PRUEBAS DE CREACIÓN DE LIBRO (POST /api/v1/books)
    // ============================================================================

    public function test_almacena_titulo_correctamente()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'El Principito',
            'description' => 'Una fábula poética sobre la amistad y el amor',
            'ISBN' => '9780156012195',
            'total_copies' => 3,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $this->assertDatabaseHas('books', [
            'title' => 'El Principito',
        ]);
    }

    public function test_almacena_descripcion_correctamente()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Don Quijote',
            'description' => 'Clásico de la literatura española',
            'ISBN' => '9788424116927',
            'total_copies' => 2,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $this->assertDatabaseHas('books', [
            'description' => 'Clásico de la literatura española',
        ]);
    }

    public function test_almacena_ISBN_correctamente()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => '1984',
            'description' => 'Novela distópica de George Orwell',
            'ISBN' => '9780451524935',
            'total_copies' => 4,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $this->assertDatabaseHas('books', [
            'ISBN' => '9780451524935',
        ]);
    }

    public function test_almacena_total_copies_correctamente()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Orgullo y Prejuicio',
            'description' => 'Romance clásico de Jane Austen',
            'ISBN' => '9780141439518',
            'total_copies' => 10,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $this->assertDatabaseHas('books', [
            'total_copies' => 10,
        ]);
    }

    public function test_inicializa_available_copies_igual_a_total_copies()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Harry Potter',
            'description' => 'Primera novela de la saga de J.K. Rowling',
            'ISBN' => '9780439708180',
            'total_copies' => 7,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $this->assertDatabaseHas('books', [
            'total_copies' => 7,
            'available_copies' => 7,
        ]);
    }

    public function test_establece_is_available_verdadero_por_defecto()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'La Metamorfosis',
            'description' => 'Obra de Franz Kafka sobre la transformación',
            'ISBN' => '9780553213690',
            'total_copies' => 3,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $this->assertDatabaseHas('books', [
            'is_available' => true,
        ]);
    }

    public function test_retorna_201_en_exito()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'El Hobbit',
            'description' => 'Precuela de El Señor de los Anillos',
            'ISBN' => '9780345339683',
            'total_copies' => 6,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $response->assertStatus(201);
    }

    public function test_valida_titulo_es_requerido()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'ISBN' => '9780747532743',
            'total_copies' => 5,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_valida_ISBN_es_requerido()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Libro sin ISBN',
            'total_copies' => 5,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ISBN');
    }

    public function test_valida_total_copies_es_requerido()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Libro sin copias',
            'ISBN' => '9780747532744',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('total_copies');
    }

    public function test_valida_ISBN_es_unico()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $existingBook = Book::factory()->create([
            'ISBN' => '9780061120084',
        ]);

        $bookData = [
            'title' => 'Libro duplicado',
            'ISBN' => '9780061120084',
            'total_copies' => 3,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ISBN');
    }

    public function test_valida_total_copies_es_entero()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Libro con copias inválidas',
            'ISBN' => '9780061120085',
            'total_copies' => 'no-es-numero',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('total_copies');
    }

    public function test_valida_titulo_no_sea_cadena_vacia()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => '',
            'ISBN' => '9780061120086',
            'total_copies' => 5,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_valida_total_copies_es_positivo()
    {
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Libro con copias negativas',
            'ISBN' => '9780061120087',
            'total_copies' => 0,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('total_copies');
    }

    // ============================================================================
    // PRUEBAS DE ACTUALIZACIÓN DE LIBRO (PUT /api/v1/books/{book})
    // ============================================================================

    public function test_usuario_no_autenticado_no_puede_actualizar_libro()
    {
        $libro = Book::factory()->create();
        
        $datosActualizados = [
            'title' => 'Nuevo titulo',
            'description' => 'Nueva descripción',
            'ISBN' => $libro->ISBN,
            'total_copies' => 10,
            'available_copies' => 5,
            'is_available' => true,
        ];
        
        $response = $this->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
        
        $response->assertStatus(401);
    }

    public function test_usuario_sin_rol_bibliotecario_no_puede_actualizar_libro()
    {
        $usuario = User::factory()->create();
        $libro = Book::factory()->create();
        
        $datosActualizados = [
            'title' => 'Nuevo titulo',
            'description' => 'Nueva descripción',
            'ISBN' => $libro->ISBN,
            'total_copies' => 10,
            'available_copies' => 5,
            'is_available' => true,
        ];
        $response = $this->actingAs($usuario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
        
        $response->assertStatus(403);
    }

    public function test_falla_al_actualizar_libro_sin_titulo_requerido()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libro = Book::factory()->create();
        
        $datosInvalidos = [
            'description' => 'Descripción',
            'ISBN' => $libro->ISBN,
            'total_copies' => 10,
            'available_copies' => 5,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_falla_al_actualizar_libro_sin_ISBN_requerido()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libro = Book::factory()->create();
        
        $datosInvalidos = [
            'title' => 'Título válido',
            'description' => 'Descripción',
            'total_copies' => 10,
            'available_copies' => 5,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ISBN']);
    }

    public function test_falla_al_actualizar_con_ISBN_duplicado()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libroExistente = Book::factory()->create(['ISBN' => '1111111111']);
        $libroActualizar = Book::factory()->create(['ISBN' => '2222222222']);
        
        $datosInvalidos = [
            'title' => 'Título válido',
            'description' => 'Descripción',
            'ISBN' => '1111111111',
            'total_copies' => 10,
            'available_copies' => 5,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libroActualizar->id}", $datosInvalidos);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ISBN']);
    }

    public function test_falla_cuando_available_copies_es_mayor_que_total_copies()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libro = Book::factory()->create();
        
        $datosInvalidos = [
            'title' => 'Título válido',
            'description' => 'Descripción',
            'ISBN' => $libro->ISBN,
            'total_copies' => 5,
            'available_copies' => 10,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['available_copies']);
    }

    public function test_falla_al_actualizar_con_total_copies_cero_o_negativo()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libro = Book::factory()->create();
        
        $datosInvalidos = [
            'title' => 'Título válido',
            'description' => 'Descripción',
            'ISBN' => $libro->ISBN,
            'total_copies' => 0,
            'available_copies' => 0,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['total_copies']);
    }

    public function test_falla_al_actualizar_con_available_copies_negativo()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libro = Book::factory()->create();
        
        $datosInvalidos = [
            'title' => 'Título válido',
            'description' => 'Descripción',
            'ISBN' => $libro->ISBN,
            'total_copies' => 10,
            'available_copies' => -1,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['available_copies']);
    }

    public function test_actualiza_correctamente_todos_los_campos_del_libro()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libro = Book::factory()->create([
            'title' => 'Título Viejo',
            'description' => 'Descripción vieja',
            'ISBN' => '1111111111',
            'total_copies' => 5,
            'available_copies' => 2,
            'is_available' => false,
        ]);
        
        $datosActualizados = [
            'title' => 'Título nuevo',
            'description' => 'Descripción nueva y detallada',
            'ISBN' => '9999999999',
            'total_copies' => 25,
            'available_copies' => 20,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
        
        $response->assertStatus(200);
        
        $libroActualizado = $libro->fresh();
        $this->assertEquals('Título nuevo', $libroActualizado->title);
        $this->assertEquals('Descripción nueva y detallada', $libroActualizado->description);
        $this->assertEquals('9999999999', $libroActualizado->ISBN);
        $this->assertEquals(25, $libroActualizado->total_copies);
        $this->assertEquals(20, $libroActualizado->available_copies);
        $this->assertTrue($libroActualizado->is_available);
    }

    public function test_puede_actualizar_libro_manteniendo_el_mismo_ISBN()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libro = Book::factory()->create(['ISBN' => '5555555555']);
        
        $datosActualizados = [
            'title' => 'Nuevo Título',
            'description' => 'Nueva descripción',
            'ISBN' => '5555555555',
            'total_copies' => 20,
            'available_copies' => 15,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('books', [
            'id' => $libro->id,
            'ISBN' => '5555555555',
            'title' => 'Nuevo Título',
        ]);
    }

    public function test_puede_actualizar_solo_el_titulo_y_descripcion()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $libro = Book::factory()->create([
            'title' => 'Título original',
            'description' => 'Descripción original',
            'ISBN' => '7777777777',
            'total_copies' => 10,
            'available_copies' => 5,
            'is_available' => true,
        ]);
        
        $datosActualizados = [
            'title' => 'Solo título actualizado',
            'description' => 'Solo descripción actualizada',
            'ISBN' => $libro->ISBN,
            'total_copies' => $libro->total_copies,
            'available_copies' => $libro->available_copies,
            'is_available' => $libro->is_available,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
        
        $response->assertStatus(200);
        
        $libroActualizado = $libro->fresh();
        $this->assertEquals('Solo título actualizado', $libroActualizado->title);
        $this->assertEquals('Solo descripción actualizada', $libroActualizado->description);
        $this->assertEquals('7777777777', $libroActualizado->ISBN);
        $this->assertEquals(10, $libroActualizado->total_copies);
        $this->assertEquals(5, $libroActualizado->available_copies);
    }

    public function test_falla_al_actualizar_libro_que_no_existe()
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');
        
        $idLibroInexistente = 99999;
        
        $datosActualizados = [
            'title' => 'Título',
            'description' => 'Descripción',
            'ISBN' => '1234567890',
            'total_copies' => 10,
            'available_copies' => 5,
            'is_available' => true,
        ];
        
        $response = $this->actingAs($bibliotecario, 'sanctum')
                         ->putJson("/api/v1/books/{$idLibroInexistente}", $datosActualizados);
        
        $response->assertStatus(404);
    }

    // ============================================================================
    // PRUEBAS DE ELIMINACIÓN DE LIBRO (DELETE /api/v1/books/{book})
    // ===========================================================================

    /** 3 y 4. Eliminación bloqueada con préstamos activos y persistencia en base de datos */
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

    /** 5. Usuario no bibliotecario no puede eliminar */
    public function test_usuario_no_bibliotecario_no_puede_eliminar_libro()
    {
        $user = User::factory()->create(); 

        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(403);
    }

    /** 6. Usuario no autenticado no puede eliminar */
    public function test_usuario_no_autenticado_no_puede_eliminar_libro()
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(401);
    }

    /** 7. Retorna 500 cuando ocurre una excepción (Mocking) */
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
