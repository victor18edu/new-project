<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:150',
            'role_title' => 'sometimes|nullable|string|max:100',
            'email' => 'sometimes|nullable|email|max:150',
            'phone' => 'sometimes|nullable|string|max:25',
            'whatsapp' => 'sometimes|nullable|string|max:25',
            'is_decision_maker' => 'sometimes|nullable|boolean',
            'is_primary' => 'sometimes|nullable|boolean',
            'company_id' => 'sometimes|required|exists:companies,id',
            'notes' => 'sometimes|nullable|string',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatorio.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome não pode exceder 150 caracteres.',
            'role_title.string' => 'O cargo deve ser um texto.',
            'role_title.max' => 'O cargo não pode exceder 100 caracteres.',
            'email.email' => 'O e-mail deve ser válido.',
            'email.max' => 'O e-mail não pode exceder 150 caracteres.',
            'phone.string' => 'O telefone deve ser um texto.',
            'phone.max' => 'O telefone não pode exceder 25 caracteres.',
            'whatsapp.string' => 'O WhatsApp deve ser um texto.',
            'whatsapp.max' => 'O WhatsApp não pode exceder 25 caracteres.',
            'company_id.required' => 'O id da companhia é obrigatorio.',
            'company_id.exists' => 'A companhia informada não existe.',
            'notes.string' => 'As notas devem ser um texto.',
        ];
    }
}
