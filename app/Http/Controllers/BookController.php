<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function store(Request $request)
    {
        $this->authorize('create', Book::class);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ISBN' => ['required', 'string', 'unique:books,ISBN', 'max:20'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $book = Book::create([
            'title' => $request->title,
            'description' => $request->description,
            'ISBN' => $request->ISBN,
            'total_copies' => $request->total_copies,
            'available_copies' => $request->total_copies,
            'is_available' => true,
        ]);

        return response()->json(new BookResource($book), 201);
    }
}
