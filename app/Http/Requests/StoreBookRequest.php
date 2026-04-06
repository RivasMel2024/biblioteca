<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'ISBN' => ['required', 'string', 'unique:books,ISBN', 'max:20'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del libro es requerido.',
            'ISBN.required' => 'El ISBN es requerido.',
            'ISBN.unique' => 'Este ISBN ya está registrado.',
            'total_copies.required' => 'El número de copias es requerido.',
            'total_copies.min' => 'Debe haber al menos 1 copia.',
            'category_id.exists' => 'La categoría no existe.',
        ];
    }
}
