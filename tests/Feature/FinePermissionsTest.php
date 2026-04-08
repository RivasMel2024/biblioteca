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

class FinePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_f1_bibliotecario_lista_todas_las_multas(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $studentA = $this->createUserWithRole('estudiante');
        $studentB = $this->createUserWithRole('estudiante');

        $fineA = $this->createPendingFineForUser($studentA);
        $fineB = $this->createPendingFineForUser($studentB);

        $response = $this->actingAs($bibliotecario)->getJson('/api/v1/fines');

        $response->assertStatus(200);

        $ids = collect($this->extractFinesFromResponse($response->json()))->pluck('id')->all();
        $this->assertContains($fineA->id, $ids);
        $this->assertContains($fineB->id, $ids);
    }

    public function test_f2_estudiante_ve_solo_sus_multas(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $otherStudent = $this->createUserWithRole('estudiante');

        $ownFine = $this->createPendingFineForUser($student);
        $otherFine = $this->createPendingFineForUser($otherStudent);

        $response = $this->actingAs($student)->getJson('/api/v1/fines');

        $response->assertStatus(200);

        $ids = collect($this->extractFinesFromResponse($response->json()))->pluck('id')->all();
        $this->assertContains($ownFine->id, $ids);
        $this->assertNotContains($otherFine->id, $ids);
    }

    public function test_f3_admin_no_puede_listar_multas(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/fines')
            ->assertStatus(403);
    }

    public function test_f4_estudiante_paga_su_multa_pendiente(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $fine = $this->createPendingFineForUser($student, totalAmount: 9.00);

        $response = $this->actingAs($student)->postJson("/api/v1/fines/{$fine->id}/pay", [
            'amount_paid' => 9.00,
            'payment_method' => 'efectivo',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('message', 'Multa pagada con éxito.')
            ->assertJsonPath('fine.status', Fine::STATUS_PAID);

        $this->assertDatabaseHas('fines', [
            'id' => $fine->id,
            'status' => Fine::STATUS_PAID,
        ]);
    }

    public function test_f5_pagar_multa_actualiza_paid_at(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $fine = $this->createPendingFineForUser($student, totalAmount: 12.00);

        $this->assertNull($fine->paid_at);

        $this->actingAs($student)->postJson("/api/v1/fines/{$fine->id}/pay", [
            'amount_paid' => 12.00,
        ])->assertStatus(200);

        $this->assertNotNull($fine->fresh()->paid_at);
    }

    public function test_f6_no_se_puede_pagar_una_multa_ya_pagada(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $fine = $this->createPendingFineForUser($student, totalAmount: 15.00);

        $this->actingAs($student)->postJson("/api/v1/fines/{$fine->id}/pay", [
            'amount_paid' => 15.00,
        ])->assertStatus(200);

        $this->actingAs($student)->postJson("/api/v1/fines/{$fine->id}/pay", [
            'amount_paid' => 15.00,
        ])->assertStatus(422);
    }

    public function test_f7_amount_paid_distinto_al_total_retorna_422(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $fine = $this->createPendingFineForUser($student, totalAmount: 20.00);

        $response = $this->actingAs($student)->postJson("/api/v1/fines/{$fine->id}/pay", [
            'amount_paid' => 19.99,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'El monto pagado debe ser exacto.');
    }

    public function test_f8_bibliotecario_no_puede_pagar_multas(): void
    {
        $bibliotecario = $this->createUserWithRole('bibliotecario');
        $student = $this->createUserWithRole('estudiante');
        $fine = $this->createPendingFineForUser($student, totalAmount: 7.00);

        $this->actingAs($bibliotecario)->postJson("/api/v1/fines/{$fine->id}/pay", [
            'amount_paid' => 7.00,
        ])->assertStatus(403);
    }

    public function test_f9_multa_pendiente_bloquea_nuevo_prestamo_pendiente_regla_futura(): void
    {
        $this->markTestIncomplete('Regla pendiente: bloquear nuevos prestamos si hay multas pendientes.');
    }

    public function test_f10_flujo_prestamo_vencido_devolucion_multa_pago_y_nuevo_prestamo(): void
    {
        $student = $this->createUserWithRole('estudiante');
        $bibliotecario = $this->createUserWithRole('bibliotecario');

        $firstCopy = $this->createAvailableCopy();

        $loanResponse = $this->actingAs($student)->postJson('/api/v1/loans', [
            'book_copy_id' => $firstCopy->id,
        ]);

        $loanResponse->assertStatus(201);
        $loanId = $loanResponse->json('id');

        $loan = Loan::findOrFail($loanId);
        $loan->update(['return_date' => now()->subDays(3)]);

        $returnResponse = $this->actingAs($bibliotecario)
            ->postJson("/api/v1/loans/{$loan->id}/return");

        $returnResponse->assertStatus(200);

        $fine = Fine::where('loan_id', $loan->id)->firstOrFail();
        $this->assertEquals(Fine::STATUS_PENDING, $fine->status);

        $payResponse = $this->actingAs($student)->postJson("/api/v1/fines/{$fine->id}/pay", [
            'amount_paid' => (float) $fine->total_amount,
        ]);

        $payResponse
            ->assertStatus(200)
            ->assertJsonPath('fine.status', Fine::STATUS_PAID);

        $secondCopy = $this->createAvailableCopy();

        $newLoanResponse = $this->actingAs($student)->postJson('/api/v1/loans', [
            'book_copy_id' => $secondCopy->id,
        ]);

        $newLoanResponse->assertStatus(201);
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

    private function extractFinesFromResponse(array $payload): array
    {
        return $payload['data'] ?? $payload;
    }
}
