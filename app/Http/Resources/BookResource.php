<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'ISBN' => $this->ISBN,
            'total_copies' => $this->total_copies,
            'available_copies' => $this->available_copies,
            'is_available' => $this->is_available ? 'Disponible' : 'No Disponible'
        ];
    }
}
