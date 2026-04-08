<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LoanPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_l1_estudiante_crea_prestamo_con_copia_disponible(): void
    {
        $estudiante = $this->createUserWithRole('estudiante');
        $copy = $this->createAvailableCopy();

        $response = $this->actingAs($estudiante)->postJson('/api/v1/loans', [
            'book_copy_id' => $copy->id,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('user_id', $estudiante->id)
            ->assertJsonPath('book_copy_id', $copy->id);
    }

    public function test_l2_bibliotecario_crea_prestamo_con_copia_disponible(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $copy = $this->createAvailableCopy();

        $response = $this->actingAs($bibliotecario)->postJson('/api/v1/loans', [
            'book_copy_id' => $copy->id,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('user_id', $bibliotecario->id)
            ->assertJsonPath('book_copy_id', $copy->id);
    }

    public function test_l3_al_crear_prestamo_la_copia_pasa_a_prestada(): void
    {
        $estudiante = $this->createUserWithRole('estudiante');
        $copy = $this->createAvailableCopy();

        $this->actingAs($estudiante)->postJson('/api/v1/loans', [
            'book_copy_id' => $copy->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('book_copies', [
            'id' => $copy->id,
            'status' => BookCopy::STATUS_LOANED,
        ]);
    }

    public function test_l4_al_crear_prestamo_se_guarda_return_date_a_25_dias(): void
    {
        $estudiante = $this->createUserWithRole('estudiante');
        $copy = $this->createAvailableCopy();

        $response = $this->actingAs($estudiante)->postJson('/api/v1/loans', [
            'book_copy_id' => $copy->id,
        ]);

        $response->assertStatus(201);

        $loan = Loan::findOrFail($response->json('id'));
        $days = now()->diffInDays($loan->return_date, false);

        $this->assertTrue($days >= 24 && $days <= 25);
    }

    public function test_l5_no_se_puede_prestar_una_copia_prestada(): void
    {
        $estudiante = $this->createUserWithRole('estudiante');
        $copy = $this->createAvailableCopy();
        $copy->update(['status' => BookCopy::STATUS_LOANED]);

        $this->actingAs($estudiante)->postJson('/api/v1/loans', [
            'book_copy_id' => $copy->id,
        ])->assertStatus(422);
    }

    public function test_l6_estudiante_ve_solo_sus_prestamos(): void
    {
        $estudiante = $this->createUserWithRole('estudiante');
        $otroEstudiante = $this->createUserWithRole('estudiante');

        $loanOwn = $this->createActiveLoanForUser($estudiante);
        $loanOther = $this->createActiveLoanForUser($otroEstudiante);

        $response = $this->actingAs($estudiante)->getJson('/api/v1/loans');

        $response->assertStatus(200);

        $ids = collect($this->extractLoansFromResponse($response->json()))->pluck('id')->all();
        $this->assertContains($loanOwn->id, $ids);
        $this->assertNotContains($loanOther->id, $ids);
    }

    public function test_l7_bibliotecario_ve_todos_los_prestamos(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $studentA = $this->createUserWithRole('estudiante');
        $studentB = $this->createUserWithRole('estudiante');

        $loanA = $this->createActiveLoanForUser($studentA);
        $loanB = $this->createActiveLoanForUser($studentB);

        $response = $this->actingAs($bibliotecario)->getJson('/api/v1/loans');

        $response->assertStatus(200);

        $ids = collect($this->extractLoansFromResponse($response->json()))->pluck('id')->all();
        $this->assertContains($loanA->id, $ids);
        $this->assertContains($loanB->id, $ids);
    }

    public function test_l8_estudiante_no_puede_ver_prestamo_ajeno(): void
    {
        $estudiante = $this->createUserWithRole('estudiante');
        $otroEstudiante = $this->createUserWithRole('estudiante');
        $otherLoan = $this->createActiveLoanForUser($otroEstudiante);

        $this->actingAs($estudiante)
            ->getJson("/api/v1/loans/{$otherLoan->id}")
            ->assertStatus(403);
    }

    public function test_l9_bibliotecario_devuelve_prestamo_y_copia_vuelve_a_disponible(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $loan = $this->createActiveLoanForUser($bibliotecario);

        $response = $this->actingAs($bibliotecario)
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $response->assertStatus(200);

        $this->assertDatabaseHas('book_copies', [
            'id' => $loan->book_copy_id,
            'status' => BookCopy::STATUS_AVAILABLE,
        ]);
    }

    public function test_l10_devolver_prestamo_dos_veces_retorna_422(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $loan = $this->createActiveLoanForUser($bibliotecario);

        $this->actingAs($bibliotecario)
            ->postJson("/api/v1/loans/{$loan->id}/return")
            ->assertStatus(200);

        $this->actingAs($bibliotecario)
            ->postJson("/api/v1/loans/{$loan->id}/return")
            ->assertStatus(422);
    }

    public function test_l11_devolucion_a_tiempo_no_genera_multa(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $loan = $this->createActiveLoanForUser($bibliotecario, now()->addDays(3));

        $this->actingAs($bibliotecario)
            ->postJson("/api/v1/loans/{$loan->id}/return")
            ->assertStatus(200);

        $this->assertDatabaseMissing('fines', [
            'loan_id' => $loan->id,
        ]);
    }

    public function test_l12_devolucion_tardia_genera_multa_pendiente(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $loan = $this->createActiveLoanForUser($bibliotecario, now()->subDays(2));

        $this->actingAs($bibliotecario)
            ->postJson("/api/v1/loans/{$loan->id}/return")
            ->assertStatus(200);

        $this->assertDatabaseHas('fines', [
            'loan_id' => $loan->id,
            'status' => Fine::STATUS_PENDING,
        ]);
    }

    public function test_l13_multa_tardia_calcula_total_amount_dias_por_3(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');

        $fixedNow = Carbon::create(2026, 4, 7, 10, 0, 0);
        $this->travelTo($fixedNow);

        $loan = $this->createActiveLoanForUser(
            $bibliotecario,
            $fixedNow->copy()->subDays(4)
        );

        $this->actingAs($bibliotecario)
            ->postJson("/api/v1/loans/{$loan->id}/return")
            ->assertStatus(200);

        $fine = Fine::where('loan_id', $loan->id)->firstOrFail();

        $this->assertEquals(4, $fine->days_overdue);
        $this->assertEquals(3.0, (float) $fine->daily_amount);
        $this->assertEquals(12.0, (float) $fine->total_amount);

        $this->travelBack();
    }

    public function test_l14_admin_no_puede_crear_prestamos(): void
    {
        $admin = $this->createUserWithRole('admin');
        $copy = $this->createAvailableCopy();

        $this->actingAs($admin)->postJson('/api/v1/loans', [
            'book_copy_id' => $copy->id,
        ])->assertStatus(403);
    }

    public function test_l15_admin_no_puede_devolver_prestamos(): void
    {
        $admin = $this->createUserWithRole('admin');
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $loan = $this->createActiveLoanForUser($bibliotecario);

        $this->actingAs($admin)
            ->postJson("/api/v1/loans/{$loan->id}/return")
            ->assertStatus(403);
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

    private function createActiveLoanForUser(User $user, ?Carbon $returnDate = null): Loan
    {
        $copy = $this->createAvailableCopy();
        $copy->update(['status' => BookCopy::STATUS_LOANED]);

        return Loan::create([
            'user_id' => $user->id,
            'book_id' => $copy->book_id,
            'book_copy_id' => $copy->id,
            'return_date' => $returnDate ?? now()->addDays(7),
            'returned_at' => null,
        ]);
    }

    private function extractLoansFromResponse(array $payload): array
    {
        return $payload['data'] ?? $payload;
    }
}
