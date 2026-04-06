<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Http\Resources\BookCopyResource;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Book::class);

        $books = Book::with('category')
            ->when($request->has('title'), function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->input('title') . '%');
            })
            ->when($request->has('isbn'), function ($query) use ($request) {
                $query->where('ISBN', 'like', '%' . $request->input('isbn') . '%');
            })
            ->when($request->has('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->has('is_available'), function ($query) use ($request) {
                $query->where('is_available', $request->boolean('is_available'));
            })
            ->paginate();

        return response()->json(BookResource::collection($books));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        $this->authorize('create', Book::class);

        $book = Book::create([
            'title' => $request->title,
            'description' => $request->description,
            'ISBN' => $request->ISBN,
            'category_id' => $request->category_id,
        ]);

        // Crear las copias del libro
        for ($i = 0; $i < $request->total_copies; $i++) {
            BookCopy::create([
                'book_id' => $book->id,
                'barcode' => $this->generateBarcode($book->id, $i + 1),
                'status' => BookCopy::STATUS_AVAILABLE,
            ]);
        }

        return response()->json(new BookResource($book->load('category')), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $this->authorize('view', $book);

        return response()->json(new BookResource(
            $book->load(['category', 'copies'])
        ));
    }

    /**
     * Get available copies of a book.
     */
    public function copies(Book $book)
    {
        $this->authorize('view', $book);

        $copies = $book->copies()
            ->where('status', BookCopy::STATUS_AVAILABLE)
            ->get();

        return response()->json(BookCopyResource::collection($copies));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $book->update($request->validated());

        return response()->json([
            'message' => 'Libro actualizado exitosamente.',
            'data' => new BookResource($book->load('category')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        // Verificar si hay préstamos activos
        $hasActiveLoans = $book->copies()
            ->whereHas('loans', function ($query) {
                $query->whereNull('returned_at');
            })
            ->exists();

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

    /**
     * Generate a unique barcode for a book copy.
     */
    private function generateBarcode(int $bookId, int $copyNumber): string
    {
        return sprintf('BOOK-%06d-COPY-%03d', $bookId, $copyNumber);
    }
}
