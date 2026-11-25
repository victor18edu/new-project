<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWhatsAppMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => 'required|string|max:4096',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'body.required' => 'O texto da mensagem é obrigatório.',
            'body.string' => 'O texto da mensagem deve ser um texto.',
            'body.max' => 'A mensagem não pode exceder 4096 caracteres.',
        ];
    }
}
