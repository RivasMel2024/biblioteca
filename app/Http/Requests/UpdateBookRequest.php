<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
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
            'ISBN' => ['required', 'string', 'max:20', 'unique:books,ISBN,' . $this->route('book')],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'total_copies' => ['required', 'integer', 'min:1'],
            'available_copies' => ['required', 'integer', 'min:0', 'lte:total_copies'],
            'is_available' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'available_copies.lte' => 'Las copias disponibles no pueden ser más que el total.',
        ];
    }
}
