<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only create factory books if table is empty
        if (Book::count() === 0) {
            Book::factory()->count(10)->create();
        }

        // Use firstOrCreate to avoid duplicates
        $book = Book::firstOrCreate(
            ['ISBN' => '90292040123'],
            [
                'title' => 'Cien años de soledad',
                'description' => 'Narra la vida de Jose Arcadio Buendía y su familia a lo largo de siete generaciones en el pueblo ficticio de Macondo.',
                'category_id' => 1,
            ]
        );

        // Create copies only if this book has none
        if ($book->copies()->count() === 0) {
            for ($i = 1; $i <= 10; $i++) {
                $book->copies()->create([
                    'barcode' => 'BOOK-' . $book->id . '-COPY-' . $i,
                    'status' => 'DISPONIBLE',
                ]);
            }
        }
    }
}
