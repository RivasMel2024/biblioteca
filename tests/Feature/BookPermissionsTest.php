<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_b1_bibliotecario_lista_libros(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $book = Book::factory()->create();

        $response = $this->actingAs($bibliotecario)->getJson('/api/v1/books');

        $response->assertStatus(200);

        $listedBook = $this->findBookInResponse($response->json(), $book->id);
        $this->assertNotNull($listedBook);
        $this->assertArrayHasKey('title', $listedBook);
        $this->assertArrayHasKey('ISBN', $listedBook);
        $this->assertArrayHasKey('available_copies', $listedBook);
    }

    public function test_b2_estudiante_lista_libros(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Book::factory()->create();

        $this->actingAs($estudiante)
            ->getJson('/api/v1/books')
            ->assertStatus(200);
    }

    public function test_b3_admin_no_puede_listar_libros(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/books')
            ->assertStatus(403);
    }

    public function test_b4_bibliotecario_crea_libro_con_n_copias(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $response = $this->actingAs($bibliotecario)->postJson('/api/v1/books', [
            'title' => 'Arquitectura Limpia',
            'description' => 'Libro de prueba',
            'ISBN' => 'ISBN-B4-0001',
            'total_copies' => 4,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('title', 'Arquitectura Limpia')
            ->assertJsonPath('ISBN', 'ISBN-B4-0001')
            ->assertJsonPath('total_copies', 4);
    }

    public function test_b5_al_crear_libro_se_generan_n_copias_con_barcode_unico(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $response = $this->actingAs($bibliotecario)->postJson('/api/v1/books', [
            'title' => 'DDD Practico',
            'description' => 'Libro para validar copias',
            'ISBN' => 'ISBN-B5-0001',
            'total_copies' => 5,
        ]);

        $response->assertStatus(201);

        $bookId = $response->json('id');
        $copies = BookCopy::where('book_id', $bookId)->get();

        $this->assertCount(5, $copies);
        $this->assertCount(5, $copies->pluck('barcode')->unique());
    }

    public function test_b6_listado_muestra_available_copies_correcto(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $book = Book::factory()->create();

        $copies = BookCopy::where('book_id', $book->id)->get();
        $initialTotalCopies = $copies->count();
        $copies[0]->update(['status' => BookCopy::STATUS_LOANED]);
        $copies[1]->update(['status' => BookCopy::STATUS_DAMAGED]);

        $response = $this->actingAs($bibliotecario)->getJson('/api/v1/books');

        $response->assertStatus(200);

        $listedBook = $this->findBookInResponse($response->json(), $book->id);
        $this->assertNotNull($listedBook);
        $this->assertEquals($initialTotalCopies, $listedBook['total_copies']);
        $this->assertEquals($initialTotalCopies - 2, $listedBook['available_copies']);
    }

    public function test_b7_detalle_libro_devuelve_estado_de_disponibilidad_correcto(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $book = Book::factory()->create();
        BookCopy::where('book_id', $book->id)->update(['status' => BookCopy::STATUS_LOANED]);

        $response = $this->actingAs($bibliotecario)
            ->getJson("/api/v1/books/{$book->id}");

        $response
            ->assertStatus(200)
            ->assertJsonPath('id', $book->id)
            ->assertJsonPath('is_available', 'No Disponible')
            ->assertJsonPath('available_copies', 0);
    }

    public function test_b8_endpoint_copias_retorna_solo_disponibles(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $book = Book::factory()->create();
        $copies = BookCopy::where('book_id', $book->id)->get();

        $copies[0]->update(['status' => BookCopy::STATUS_AVAILABLE]);
        $copies[1]->update(['status' => BookCopy::STATUS_LOANED]);
        $copies[2]->update(['status' => BookCopy::STATUS_DAMAGED]);

        $response = $this->actingAs($bibliotecario)
            ->getJson("/api/v1/books/{$book->id}/copies");

        $response->assertStatus(200);

        $copyList = $response->json('data') ?? $response->json();

        $this->assertNotEmpty($copyList);
        foreach ($copyList as $copy) {
            $this->assertEquals(BookCopy::STATUS_AVAILABLE, $copy['status']);
            $this->assertTrue($copy['is_available']);
        }
    }

    public function test_b9_estudiante_no_puede_crear_libro(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $this->actingAs($estudiante)->postJson('/api/v1/books', [
            'title' => 'No Permitido',
            'description' => 'Intento',
            'ISBN' => 'ISBN-B9-0001',
            'total_copies' => 2,
        ])->assertStatus(403);
    }

    public function test_b10_estudiante_no_puede_actualizar_libro(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $book = Book::factory()->create();

        $this->actingAs($estudiante)->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Titulo Cambiado',
            'description' => 'Cambio',
            'ISBN' => $book->ISBN,
            'category_id' => $book->category_id,
        ])->assertStatus(403);
    }

    public function test_b11_estudiante_no_puede_eliminar_libro(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $book = Book::factory()->create();

        $this->actingAs($estudiante)
            ->deleteJson("/api/v1/books/{$book->id}")
            ->assertStatus(403);
    }

    public function test_b12_admin_no_puede_crear_actualizar_ni_eliminar_libros(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $book = Book::factory()->create();

        $this->actingAs($admin)->postJson('/api/v1/books', [
            'title' => 'Creacion Admin',
            'description' => 'No permitido',
            'ISBN' => 'ISBN-B12-0001',
            'total_copies' => 3,
        ])->assertStatus(403);

        $this->actingAs($admin)->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Update Admin',
            'description' => 'No permitido',
            'ISBN' => $book->ISBN,
            'category_id' => $book->category_id,
        ])->assertStatus(403);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/books/{$book->id}")
            ->assertStatus(403);
    }

    public function test_b13_isbn_duplicado_retorna_422(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        Book::factory()->create(['ISBN' => 'ISBN-DUP-001']);

        $response = $this->actingAs($bibliotecario)->postJson('/api/v1/books', [
            'title' => 'Duplicado',
            'description' => 'Test',
            'ISBN' => 'ISBN-DUP-001',
            'total_copies' => 2,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ISBN']);
    }

    public function test_b14_total_copies_menor_a_1_retorna_422(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $response = $this->actingAs($bibliotecario)->postJson('/api/v1/books', [
            'title' => 'Sin Copias',
            'description' => 'Debe fallar',
            'ISBN' => 'ISBN-B14-0001',
            'total_copies' => 0,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['total_copies']);
    }

    public function test_b15_eliminar_libro_con_prestamos_activos_retorna_422(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $book = Book::factory()->create();
        $copy = BookCopy::where('book_id', $book->id)->firstOrFail();

        Loan::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'return_date' => now()->addDays(7),
            'returned_at' => null,
        ]);

        $response = $this->actingAs($bibliotecario)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_b16_libro_sin_prestamos_activos_se_elimina_correctamente(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $book = Book::factory()->create();

        $response = $this->actingAs($bibliotecario)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response
            ->assertStatus(200)
            ->assertJsonPath('message', 'Libro eliminado con éxito.');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    private function findBookInResponse(array $payload, int $bookId): ?array
    {
        $books = $payload['data'] ?? $payload;

        if (!is_array($books)) {
            return null;
        }

        foreach ($books as $book) {
            if (is_array($book) && ($book['id'] ?? null) === $bookId) {
                return $book;
            }
        }

        return null;
    }
}
