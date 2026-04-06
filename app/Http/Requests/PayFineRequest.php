<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayFineRequest extends FormRequest
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
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_paid.required' => 'El monto pagado es requerido.',
            'amount_paid.numeric' => 'El monto debe ser un número.',
            'amount_paid.min' => 'El monto debe ser mayor a 0.',
        ];
    }
}
