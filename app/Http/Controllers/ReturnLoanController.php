<?php

namespace App\Http\Controllers;

use App\Http\Resources\LoanResource;
use App\Http\Resources\FineResource;
use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Http\Request;

class ReturnLoanController extends Controller
{
    // Monto diario de la multa por atraso
    private const DAILY_FINE_AMOUNT = 5000; // En la moneda local del sistema

    /**
     * Handle the incoming request.
     * Estudiantes, docentes y bibliotecarios pueden devolver libros.
     */
    public function __invoke(Request $request, Loan $loan)
    {
        $this->authorize('return', $loan);

        if (!is_null($loan->returned_at)) {
            return response()->json([
                'message' => 'Este préstamo ya fue devuelto.'
            ], 422);
        }

        // Marcar como devuelto
        $loan->update(['returned_at' => now()]);

        // Actualizar estado de la copia
        $bookCopy = $loan->bookCopy;
        $bookCopy->update(['status' => 'DISPONIBLE']);

        // Actualizar conteo de copias disponibles del libro
        $book = $bookCopy->book;
        $availableCopies = $book->copies()
            ->where('status', 'DISPONIBLE')
            ->count();

        $book->update([
            'available_copies' => $availableCopies,
            'is_available' => $availableCopies > 0,
        ]);

        // Calcular multa si está atrasada
        $fine = null;
        if (now() > $loan->return_date) {
            $daysOverdue = now()->diffInDays($loan->return_date);
            $totalAmount = $daysOverdue * self::DAILY_FINE_AMOUNT;

            $fine = Fine::create([
                'loan_id' => $loan->id,
                'days_overdue' => $daysOverdue,
                'daily_amount' => self::DAILY_FINE_AMOUNT,
                'total_amount' => $totalAmount,
                'status' => Fine::STATUS_PENDING,
            ]);
        }

        $response = [
            'message' => 'Préstamo devuelto con éxito.',
            'loan' => new LoanResource($loan->load(['user', 'bookCopy.book', 'fine'])),
        ];

        // Si hay multa, incluirse en la respuesta
        if ($fine) {
            $response['fine'] = new FineResource($fine);
            $response['message'] = 'Préstamo devuelto. Existe una multa pendiente por atraso.';
        }

        return response()->json($response, 200);
    }
}
