<?php

namespace App\Http\Controllers;

use App\Http\Resources\FineResource;
use App\Models\Fine;
use App\Http\Requests\PayFineRequest;
use Illuminate\Http\Request;

class FineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Fine::class);

        $fines = Fine::query()
            ->with(['loan.user', 'loan.bookCopy.book'])
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->user()->hasRole('estudiante'), function ($query) {
                // Los estudiantes solo ven sus propias multas
                $query->whereHas('loan', fn ($q) => $q->where('user_id', $query->user()->id));
            })
            ->paginate();

        return response()->json(FineResource::collection($fines));
    }

    /**
     * Display the specified resource.
     */
    public function show(Fine $fine)
    {
        $this->authorize('view', $fine);

        return response()->json(new FineResource($fine->load(['loan.user', 'loan.bookCopy.book'])));
    }

    /**
     * Pay the fine.
     */
    public function pay(PayFineRequest $request, Fine $fine)
    {
        $this->authorize('pay', $fine);

        if ($fine->status !== Fine::STATUS_PENDING) {
            return response()->json([
                'message' => 'Esta multa ya fue pagada o condonada.'
            ], 422);
        }

        $amountPaid = $request->input('amount_paid');
        $amountPaidRounded = number_format((float) $amountPaid, 2, '.', '');
        $totalAmountRounded = number_format((float) $fine->total_amount, 2, '.', '');

        if ($amountPaidRounded !== $totalAmountRounded) {
            return response()->json([
                'message' => 'El monto pagado debe ser exacto.',
                'fine_amount' => (float) $fine->total_amount,
                'amount_paid' => (float) $amountPaid,
            ], 422);
        }

        $fine->update([
            'status' => Fine::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => 'Multa pagada con éxito.',
            'fine' => new FineResource($fine),
            'change' => 0,
        ], 200);
    }
}
