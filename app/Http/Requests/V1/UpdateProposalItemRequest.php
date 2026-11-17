<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProposalItemRequest extends FormRequest
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
            'proposal_id' => 'sometimes|required|exists:proposals,id',
            'service_id' => 'sometimes|nullable|exists:services,id',
            'item_name' => 'sometimes|required|string|max:150',
            'description' => 'sometimes|nullable|string',
            'quantity' => 'sometimes|required|numeric|decimal:0,2|max:999999.99',
            'unit_price' => 'sometimes|required|numeric|decimal:0,2|max:9999999999.99',
            'discount_amount' => 'sometimes|nullable|numeric|decimal:0,2|max:9999999999.99',
            'total_price' => 'sometimes|required|numeric|decimal:0,2|max:9999999999.99',
            'is_recurring' => 'sometimes|nullable|boolean',
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
            'proposal_id.required' => 'O id da proposta é obrigatorio.',
            'proposal_id.exists' => 'A proposta informada não existe.',
            'service_id.exists' => 'O serviço informado não existe.',
            'item_name.required' => 'O nome do item é obrigatorio.',
            'item_name.string' => 'O nome do item deve ser um texto.',
            'item_name.max' => 'O nome do item não pode exceder 150 caracteres.',
            'description.string' => 'A descrição deve ser um texto.',
            'quantity.required' => 'A quantidade é obrigatoria.',
            'quantity.numeric' => 'A quantidade deve ser um número.',
            'quantity.decimal' => 'A quantidade deve ter no máximo 2 casas decimais.',
            'quantity.max' => 'A quantidade não pode exceder 999999.99.',
            'unit_price.required' => 'O preço unitário é obrigatorio.',
            'unit_price.numeric' => 'O preço unitário deve ser um número.',
            'unit_price.decimal' => 'O preço unitário deve ter no máximo 2 casas decimais.',
            'unit_price.max' => 'O preço unitário não pode exceder 9999999999.99.',
            'discount_amount.numeric' => 'O valor do desconto deve ser um número.',
            'discount_amount.decimal' => 'O valor do desconto deve ter no máximo 2 casas decimais.',
            'discount_amount.max' => 'O valor do desconto não pode exceder 9999999999.99.',
            'total_price.required' => 'O preço total é obrigatorio.',
            'total_price.numeric' => 'O preço total deve ser um número.',
            'total_price.decimal' => 'O preço total deve ter no máximo 2 casas decimais.',
            'total_price.max' => 'O preço total não pode exceder 9999999999.99.',
        ];
    }
}
