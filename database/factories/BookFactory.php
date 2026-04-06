<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->name,
            'description' => $this->faker->text(200),
            'ISBN' => $this->faker->unique()->numerify('#############'),
            'category_id' => Category::inRandomOrder()->first()?->id ?? null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterCreating(function (Book $book) {
            // Create 3-10 copies for each book
            $bookCopyCount = $this->faker->numberBetween(3, 10);
            $book->copies()->createMany(
                collect(range(1, $bookCopyCount))->map(fn($i) => [
                    'barcode' => 'BOOK-' . $book->id . '-COPY-' . $i,
                    'status' => 'DISPONIBLE',
                ])->toArray()
            );
        });
    }
}
