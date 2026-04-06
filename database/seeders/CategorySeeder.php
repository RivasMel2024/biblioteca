<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::firstOrCreate(
            ['name' => 'Novela'],
            ['description' => 'Novelas de ficción y narrativa literaria']
        );

        Category::firstOrCreate(
            ['name' => 'Ciencia Ficción'],
            ['description' => 'Historias de ciencia ficción y futuros alternativos']
        );

        Category::firstOrCreate(
            ['name' => 'Misterio'],
            ['description' => 'Novelas de misterio y suspenso']
        );

        Category::firstOrCreate(
            ['name' => 'Historia'],
            ['description' => 'Libros sobre eventos históricos y personajes']
        );

        Category::firstOrCreate(
            ['name' => 'Técnico'],
            ['description' => 'Libros técnicos y referencia científica']
        );
    }
}
