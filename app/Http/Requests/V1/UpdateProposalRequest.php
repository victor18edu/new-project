<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProposalRequest extends FormRequest
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
            'proposal_number' => 'sometimes|required|string|max:30|unique:proposals,proposal_number,' . $this->route('proposal'),
            'opportunity_id' => 'sometimes|required|exists:opportunities,id',
            'company_id' => 'sometimes|required|exists:companies,id',
            'contact_id' => 'sometimes|nullable|exists:contacts,id',
            'owner_id' => 'sometimes|required|exists:users,id',
            'status' => 'sometimes|required|in:draft,sent,accepted,rejected,expired',
            'total_one_off' => 'sometimes|nullable|numeric|decimal:0,2|max:9999999999.99',
            'total_recurring' => 'sometimes|nullable|numeric|decimal:0,2|max:9999999999.99',
            'payment_terms' => 'sometimes|nullable|string|max:255',
            'valid_until' => 'sometimes|required|date',
            'sent_at' => 'sometimes|nullable|date',
            'accepted_at' => 'sometimes|nullable|date',
            'pdf_path' => 'sometimes|nullable|string|max:255',
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
            'proposal_number.required' => 'O número da proposta é obrigatorio.',
            'proposal_number.string' => 'O número da proposta deve ser um texto.',
            'proposal_number.max' => 'O número da proposta não pode exceder 30 caracteres.',
            'proposal_number.unique' => 'O número da proposta informado já está em uso.',
            'opportunity_id.required' => 'O id da oportunidade é obrigatorio.',
            'opportunity_id.exists' => 'A oportunidade informada não existe.',
            'company_id.required' => 'O id da companhia é obrigatorio.',
            'company_id.exists' => 'A companhia informada não existe.',
            'contact_id.exists' => 'O contato informado não existe.',
            'owner_id.required' => 'O id do usuário responsável é obrigatorio.',
            'owner_id.exists' => 'O usuário informado não existe.',
            'status.required' => 'O status é obrigatorio.',
            'status.in' => 'O status informado é inválido.',
            'total_one_off.numeric' => 'O valor único total deve ser um número.',
            'total_one_off.decimal' => 'O valor único total deve ter no máximo 2 casas decimais.',
            'total_one_off.max' => 'O valor único total não pode exceder 9999999999.99.',
            'total_recurring.numeric' => 'O valor recorrente total deve ser um número.',
            'total_recurring.decimal' => 'O valor recorrente total deve ter no máximo 2 casas decimais.',
            'total_recurring.max' => 'O valor recorrente total não pode exceder 9999999999.99.',
            'payment_terms.string' => 'Os termos de pagamento devem ser um texto.',
            'payment_terms.max' => 'Os termos de pagamento não podem exceder 255 caracteres.',
            'valid_until.required' => 'A validade até é obrigatoria.',
            'valid_until.date' => 'A validade até deve ser uma data válida.',
            'sent_at.date' => 'A data de envio deve ser uma data válida.',
            'accepted_at.date' => 'A data de aceitação deve ser uma data válida.',
            'pdf_path.string' => 'O caminho do PDF deve ser um texto.',
            'pdf_path.max' => 'O caminho do PDF não pode exceder 255 caracteres.',
            'notes.string' => 'As notas devem ser um texto.',
        ];
    }
}
