<?php

namespace App\Http\Requests\V1;

use App\Models\Contact;
use App\Support\WhatsAppPhone;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact instanceof Contact && $this->user()?->can('update', $contact) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4096'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $contact = $this->route('contact');

                if (! $contact instanceof Contact || WhatsAppPhone::normalize($contact->whatsapp) !== null) {
                    return;
                }

                $validator->errors()->add('whatsapp', 'O contato não possui um número de WhatsApp válido.');
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'A mensagem é obrigatória.',
            'body.string' => 'A mensagem deve ser um texto.',
            'body.max' => 'A mensagem não pode exceder 4096 caracteres.',
        ];
    }
}
