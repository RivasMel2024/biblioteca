<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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

    // ============================================================================
    // PRUEBAS DE CREACIÓN DE LIBRO (POST /api/v1/books)
    // ============================================================================

    /**
     * Matriz de Pruebas - Endpoint: Crear Libro (POST /api/v1/books)
     *
     * | ID   | Escenario                                           | Entrada                           | Resultado Esperado       |
     * |------|-----------------------------------------------------|-----------------------------------|--------------------------|
     * | CB01 | Crear libro con todos los campos requeridos         | Datos válidos completos           | 201 + libro creado       |
     * | CB02 | Verificar persistencia del título                   | title enviado                     | title guardado en BD     |
     * | CB03 | Verificar persistencia de descripción               | description enviada               | description en BD        |
     * | CB04 | Verificar persistencia de ISBN                      | ISBN enviado                      | ISBN guardado en BD      |
     * | CB05 | Verificar persistencia de total_copies              | total_copies enviado              | total_copies en BD       |
     * | CB06 | available_copies se inicializa igual a total_copies | total_copies = 10                 | available_copies = 10    |
     * | CB07 | is_available es true por defecto                    | Crear libro                       | is_available = true      |
     * | CB08 | Retorna código 201 en éxito                         | Datos válidos                     | 201 Created              |
     * | CB09 | Validar title es requerido                          | Sin title                         | 422 + error              |
     * | CB10 | Validar ISBN es requerido                           | Sin ISBN                          | 422 + error              |
     * | CB11 | Validar total_copies es requerido                   | Sin total_copies                  | 422 + error              |
     * | CB12 | Validar ISBN es único                               | ISBN duplicado                    | 422 + error              |
     * | CB13 | Validar total_copies es entero                      | total_copies no entero            | 422 + error              |
     * | CB14 | Validar title no es cadena vacía                    | title = ""                        | 422 + error              |
     * | CB15 | Validar total_copies es positivo                    | total_copies < 1                  | 422 + error              |
     */

    public function test_it_can_create_a_book()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Cien años de soledad',
            'description' => 'Obra maestra de Gabriel García Márquez',
            'ISBN' => '9780307474728',
            'total_copies' => 5,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'title',
            'description',
            'ISBN',
            'total_copies',
            'available_copies',
            'is_available',
        ]);
        $response->assertJsonFragment([
            'title' => 'Cien años de soledad',
            'ISBN' => '9780307474728',
        ]);
        $this->assertDatabaseHas('books', [
            'title' => 'Cien años de soledad',
            'ISBN' => '9780307474728',
        ]);
    }

    public function test_it_stores_title_correctly()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'El Principito',
            'description' => 'Una fábula poética sobre la amistad y el amor',
            'ISBN' => '9780156012195',
            'total_copies' => 3,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $this->assertDatabaseHas('books', [
            'title' => 'El Principito',
        ]);
    }

    public function test_it_stores_description_correctly()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Don Quijote',
            'description' => 'Clásico de la literatura española',
            'ISBN' => '9788424116927',
            'total_copies' => 2,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $this->assertDatabaseHas('books', [
            'description' => 'Clásico de la literatura española',
        ]);
    }

    public function test_it_stores_ISBN_correctly()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => '1984',
            'description' => 'Novela distópica de George Orwell',
            'ISBN' => '9780451524935',
            'total_copies' => 4,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $this->assertDatabaseHas('books', [
            'ISBN' => '9780451524935',
        ]);
    }

    public function test_it_stores_total_copies_correctly()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Orgullo y Prejuicio',
            'description' => 'Romance clásico de Jane Austen',
            'ISBN' => '9780141439518',
            'total_copies' => 10,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $this->assertDatabaseHas('books', [
            'total_copies' => 10,
        ]);
    }

    public function test_it_initializes_available_copies_equal_to_total_copies()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Harry Potter',
            'description' => 'Primera novela de la saga de J.K. Rowling',
            'ISBN' => '9780439708180',
            'total_copies' => 7,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $this->assertDatabaseHas('books', [
            'total_copies' => 7,
            'available_copies' => 7,
        ]);
    }

    public function test_it_sets_is_available_true_by_default()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'La Metamorfosis',
            'description' => 'Obra de Franz Kafka sobre la transformación',
            'ISBN' => '9780553213690',
            'total_copies' => 3,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $this->assertDatabaseHas('books', [
            'is_available' => true,
        ]);
    }

    public function test_it_returns_201_on_success()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'El Hobbit',
            'description' => 'Precuela de El Señor de los Anillos',
            'ISBN' => '9780345339683',
            'total_copies' => 6,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(201);
    }

    public function test_it_validates_title_is_required()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'ISBN' => '9780747532743',
            'total_copies' => 5,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_it_validates_ISBN_is_required()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Libro sin ISBN',
            'total_copies' => 5,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ISBN');
    }

    public function test_it_validates_total_copies_is_required()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Libro sin copias',
            'ISBN' => '9780747532744',
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('total_copies');
    }

    public function test_it_validates_ISBN_is_unique()
    {
        // Preparacion
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

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ISBN');
    }

    public function test_it_validates_total_copies_is_integer()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Libro con copias inválidas',
            'ISBN' => '9780061120085',
            'total_copies' => 'no-es-numero',
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('total_copies');
    }

    public function test_it_validates_title_is_not_empty_string()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => '',
            'ISBN' => '9780061120086',
            'total_copies' => 5,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_it_validates_total_copies_is_positive()
    {
        // Preparacion
        $user = User::factory()->create();
        $user->assignRole('bibliotecario');
        
        $bookData = [
            'title' => 'Libro con copias negativas',
            'ISBN' => '9780061120087',
            'total_copies' => 0,
        ];

        // Ejecucion
        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', $bookData);

        // Verificacion
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('total_copies');
    }
}
