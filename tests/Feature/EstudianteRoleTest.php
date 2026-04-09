<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstudianteRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_es9_estudiante_no_puede_devolver_prestamos_si_no_tiene_permiso(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $copy = $this->createAvailableCopy();

        $copy->update(['status' => BookCopy::STATUS_LOANED]);

        $loan = Loan::create([
            'user_id' => $student->id,
            'book_id' => $copy->book_id,
            'book_copy_id' => $copy->id,
            'return_date' => now()->addDays(7),
            'returned_at' => null,
        ]);

        $this->actingAs($student)
            ->postJson("/api/v1/loans/{$loan->id}/return")
            ->assertStatus(403);
    }

    public function test_es12_estudiante_no_paga_multa_ajena(): void
    {
        $owner = $this->createUserWithRole('estudiante');
        $attacker = $this->createUserWithRole('estudiante');

        $fine = $this->createPendingFineForUser($owner, totalAmount: 9.00);

        $this->actingAs($attacker)
            ->postJson("/api/v1/fines/{$fine->id}/pay", [
                'amount_paid' => 9.00,
            ])
            ->assertStatus(403);
    }

    public function test_es13_estudiante_no_accede_a_usuarios(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $target = User::factory()->create();

        $this->actingAs($student)
            ->getJson('/api/v1/users')
            ->assertStatus(403);

        $this->actingAs($student)
            ->getJson("/api/v1/users/{$target->id}")
            ->assertStatus(403);
    }

    public function test_es14_estudiante_no_puede_pagar_montos_negativos_menores_o_mayores_al_total(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $fine = $this->createPendingFineForUser($student, totalAmount: 10.00);

        // Menor al total
        $this->actingAs($student)
            ->postJson("/api/v1/fines/{$fine->id}/pay", [
                'amount_paid' => 9.99,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El monto pagado debe ser exacto.');

        // Mayor al total
        $this->actingAs($student)
            ->postJson("/api/v1/fines/{$fine->id}/pay", [
                'amount_paid' => 10.01,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El monto pagado debe ser exacto.');

        // Negativo (falla por validación del request)
        $this->actingAs($student)
            ->postJson("/api/v1/fines/{$fine->id}/pay", [
                'amount_paid' => -1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount_paid']);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createAvailableCopy(): BookCopy
    {
        $book = Book::factory()->create();

        return BookCopy::where('book_id', $book->id)
            ->where('status', BookCopy::STATUS_AVAILABLE)
            ->firstOrFail();
    }

    private function createPendingFineForUser(User $user, int $daysOverdue = 2, float $totalAmount = 6.00): Fine
    {
        $copy = $this->createAvailableCopy();

        $loan = Loan::create([
            'user_id' => $user->id,
            'book_id' => $copy->book_id,
            'book_copy_id' => $copy->id,
            'return_date' => now()->subDays($daysOverdue),
            'returned_at' => now(),
        ]);

        return Fine::create([
            'loan_id' => $loan->id,
            'days_overdue' => $daysOverdue,
            'daily_amount' => 3,
            'total_amount' => $totalAmount,
            'status' => Fine::STATUS_PENDING,
        ]);
    }
}