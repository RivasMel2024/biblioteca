<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\BookCopy;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    // Periodo de préstamo en días
    private const LOAN_PERIOD_DAYS = 25;

    /**
     * Display a listing of the resource.
     * Bibliotecarios, estudiantes y docentes pueden ver préstamos.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Loan::class);

        $loans = Loan::with(['user', 'bookCopy.book', 'fine'])
            ->when($request->has('status'), function ($query) use ($request) {
                if ($request->input('status') === 'active') {
                    $query->whereNull('returned_at');
                } elseif ($request->input('status') === 'returned') {
                    $query->whereNotNull('returned_at');
                }
            })
            ->when($request->user()->hasRole('estudiante'), function ($query) {
                // Los estudiantes solo ven sus propios préstamos
                $query->where('user_id', $query->user()->id);
            })
            ->orderByDesc('created_at')
            ->paginate();

        return response()->json(LoanResource::collection($loans));
    }

    /**
     * Store a newly created resource in storage.
     * Solo estudiantes y docentes pueden solicitar préstamos.
     */
    public function store(StoreLoanRequest $request)
    {
        $this->authorize('create', Loan::class);

        $bookCopy = BookCopy::find($request->input('book_copy_id'));

        if (!$bookCopy) {
            return response()->json(['message' => 'Copia del libro no encontrada.'], 404);
        }

        if (!$bookCopy->isAvailable()) {
            return response()->json([
                'message' => 'Esta copia no está disponible.',
                'status' => $bookCopy->status
            ], 422);
        }

        // Crear préstamo con fecha de devolución esperada
        $loan = Loan::create([
            'user_id' => $request->user()->id,
            'book_copy_id' => $bookCopy->id,
            'book_id' => $bookCopy->book_id,
            'return_date' => now()->addDays(self::LOAN_PERIOD_DAYS),
        ]);

        // Marcar la copia como prestada
        $bookCopy->update(['status' => BookCopy::STATUS_LOANED]);

        return response()->json(new LoanResource($loan->load(['user', 'bookCopy.book'])), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Loan $loan)
    {
        $this->authorize('view', $loan);

        return response()->json(new LoanResource(
            $loan->load(['user', 'bookCopy.book', 'fine'])
        ));
    }
}
