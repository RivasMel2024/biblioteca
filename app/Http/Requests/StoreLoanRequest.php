<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
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
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'book_copy_id' => ['required', 'integer', 'exists:book_copies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'El usuario no existe.',
            'book_copy_id.required' => 'La copia del libro es requerida.',
            'book_copy_id.exists' => 'La copia del libro no existe.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $requestedUserId = $this->input('user_id');
            $user = $this->user();

            if (!$requestedUserId || !$user) {
                return;
            }

            $canAssignOtherUsers = $user->hasRole('bibliotecario') || $user->can('gestionar usuarios');
            if (!$canAssignOtherUsers && (int) $requestedUserId !== (int) $user->id) {
                $validator->errors()->add('user_id', 'No puedes crear prestamos para otros usuarios.');
            }
        });
    }
}
