<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Book::class);
        $books = Book::when($request->has('title'), function ($query) use ($request) {
            $query->where('title', 'like', '%'.$request->input('title').'%');
        })->when($request->has('isbn'), function ($query) use ($request) {
            $query->where('ISBN', 'like', '%'.$request->input('isbn').'%');
        })->when($request->has('is_available'), function ($query) use ($request) {
            $query->where('is_available', $request->boolean('is_available'));
        })
            ->paginate();

        return response()->json(BookResource::collection($books));
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $hasActiveLoans = $book->loans()->whereNull('return_at')->exists();

        if ($hasActiveLoans) {
            return response()->json([
                'message' => 'No se puede eliminar el libro porque tiene préstamos activos pendientes de devolución.'
            ], 422);
        }

        try {
            $book->delete();

            return response()->json([
                'message' => 'Libro eliminado con éxito.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al intentar eliminar el libro.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
