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
        Book::factory()->count(90)->create();

        Book::create([
            'title' => 'Cien años de soledad',
            'description'   => 'Narra la vida de Jose Arcadio Buendía y su familia a lo largo de siete generaciones en el pueblo ficticio de Macondo.',
            'ISBN' => '90292040123',
            'total_copies' => 10,
            'available_copies' => 10,
            'is_available' => true,
        ]);
    }
}
