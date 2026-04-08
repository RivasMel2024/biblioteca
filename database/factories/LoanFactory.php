<?php

namespace Database\Factories;

use App\Models\BookCopy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $copy = BookCopy::factory();

        return [
            'user_id' => User::factory(),
            'book_id' => $copy->book_id,
            'book_copy_id' => $copy,
            'return_date' => now()->addDays(7),
            'returned_at' => null,
        ];
    }
}

