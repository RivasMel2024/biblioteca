<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'book_copy_id' => $this->book_copy_id,
            'book_copy' => new BookCopyResource($this->whenLoaded('bookCopy')),
            'book' => new BookResource($this->whenLoaded('book')),
            'created_at' => $this->created_at,
            'return_date' => $this->return_date,
            'returned_at' => $this->returned_at,
            'is_active' => $this->is_active,
            'is_overdue' => $this->is_overdue,
            'fine' => new FineResource($this->whenLoaded('fine')),
            'updated_at' => $this->updated_at,
        ];
    }
}
