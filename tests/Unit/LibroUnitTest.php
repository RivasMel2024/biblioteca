<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibroUnitTest extends TestCase
{
    use RefreshDatabase;

    /** 8. Unit Test: Book loans relationship */
    public function test_la_relacion_de_prestamos_del_libro_retorna_la_asociacion_correcta()
    {
        $book = Book::factory()->create();
        $copy = BookCopy::where('book_id', $book->id)->firstOrFail();
        $user = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'return_date' => now()->addDays(7),
            'returned_at' => null,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $book->loans);
        
        $this->assertTrue($book->loans->contains($loan));
        
        $this->assertEquals(1, $book->loans->count());
    }

    public function test_atributo_is_active_del_modelo_loan_funciona_correctamente()
    {
        $loanActivo = new Loan(['returned_at' => null]);
        $this->assertTrue($loanActivo->isActive);

        $loanDevuelto = new Loan(['returned_at' => now()]);
        $this->assertFalse($loanDevuelto->isActive);
    }

    public function test_relacion_loan_pertenece_a_book()
    {
        $book = Book::factory()->create();
        $copy = BookCopy::where('book_id', $book->id)->firstOrFail();
        $user = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'return_date' => now()->addDays(7),
            'returned_at' => null,
        ]);

        $this->assertInstanceOf(Book::class, $loan->book);
        $this->assertEquals($book->id, $loan->book->id);
    }
}