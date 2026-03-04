<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibroUnitTest extends TestCase
{
    use RefreshDatabase;

    /** 8. Unit Test: Book loans relationship */
    public function test_book_loans_relationship_returns_correct_association()
    {
        $book = Book::factory()->create();
        $loan = Loan::factory()->create(['book_id' => $book->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $book->loans);
        
        $this->assertTrue($book->loans->contains($loan));
        
        $this->assertEquals(1, $book->loans->count());
    }
}