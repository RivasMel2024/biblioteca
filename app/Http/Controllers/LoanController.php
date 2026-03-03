<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $loans = Loan::with('book')->paginate();

        return response()->json(LoanResource::collection($loans));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLoanRequest $request)
    {
        $book = Book::find($request->input('book_id'));

        if (! $book->is_available || $book->available_copies === 0) {
            return response()->json(['message' => 'Book is not available'], 422);
        }

        $loan = Loan::create([
            'requester_name' => $request->input('requester_name'),
            'book_id' => $request->input('book_id'),
        ]);

        $book->update([
            'available_copies' => $book->available_copies - 1,
            'is_available' => $book->available_copies - 1 > 0,
        ]);

        return response()->json($loan, 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
