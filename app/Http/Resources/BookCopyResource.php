<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCopyResource extends JsonResource
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
            'book_id' => $this->book_id,
            'book' => new BookResource($this->whenLoaded('book')),
            'barcode' => $this->barcode,
            'status' => $this->status,
            'is_available' => $this->status === 'DISPONIBLE',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
