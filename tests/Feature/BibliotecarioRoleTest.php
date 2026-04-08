<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BibliotecarioRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_bi3_bibliotecario_genera_copias_correctamente(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $response = $this->actingAs($bibliotecario)->postJson('/api/v1/books', [
            'title' => 'Libro BI3',
            'description' => 'Prueba BI3',
            'ISBN' => 'ISBN-BI3-0001',
            'total_copies' => 4,
        ]);

        $response->assertStatus(201);

        $bookId = $response->json('id');
        $copies = BookCopy::where('book_id', $bookId)->get();

        $this->assertCount(4, $copies);
        $this->assertCount(4, $copies->pluck('barcode')->unique());

        foreach ($copies as $copy) {
            $this->assertMatchesRegularExpression('/^BOOK-\d{6}-COPY-\d{3}$/', $copy->barcode);
            $this->assertEquals(BookCopy::STATUS_AVAILABLE, $copy->status);
        }
    }

    public function test_bi4_bibliotecario_actualiza_libro_retorna_200(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $book = Book::factory()->create([
            'ISBN' => 'ISBN-BI4-ORIG',
        ]);

        $response = $this->actingAs($bibliotecario)->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Titulo Actualizado BI4',
            'description' => 'Descripcion actualizada',
            'ISBN' => 'ISBN-BI4-NEW',
            'category_id' => $book->category_id,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('message', 'Libro actualizado exitosamente.')
            ->assertJsonPath('data.title', 'Titulo Actualizado BI4')
            ->assertJsonPath('data.ISBN', 'ISBN-BI4-NEW');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Titulo Actualizado BI4',
            'ISBN' => 'ISBN-BI4-NEW',
        ]);
    }

    public function test_bi5_bibliotecario_elimina_libro_sin_prestamos_activos_retorna_200(): void
    {
        $bibliotecario = User::factory()->create();
        $bibliotecario->assignRole('bibliotecario');

        $book = Book::factory()->create();

        $response = $this->actingAs($bibliotecario)
            ->deleteJson("/api/v1/books/{$book->id}");

        $response
            ->assertStatus(200)
            ->assertJsonPath('message', 'Libro eliminado con éxito.');

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_bi6_bibliotecario_no_puede_eliminar_libro_con_prestamos_activos_retorna_422(): void
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

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'No se puede eliminar el libro porque tiene préstamos activos pendientes de devolución.'
            );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}