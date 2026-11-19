<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityRequest extends FormRequest
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
            'code' => 'required|string|max:20|unique:opportunities,code',
            'title' => 'required|string|max:200',
            'company_id' => 'required|exists:companies,id',
            'primary_contact_id' => 'sometimes|nullable|exists:contacts,id',
            'pipeline_id' => 'required|exists:pipelines,id',
            'stage_id' => 'required|exists:pipeline_stages,id',
            'owner_id' => 'required|exists:users,id',
            'lost_reason_id' => 'sometimes|nullable|exists:lost_reasons,id',
            'estimated_one_off_value' => 'sometimes|nullable|numeric|decimal:0,2|max:9999999999.99',
            'estimated_recurring_value' => 'sometimes|nullable|numeric|decimal:0,2|max:9999999999.99',
            'currency' => 'sometimes|nullable|string|size:3',
            'expected_close_date' => 'sometimes|nullable|date',
            'closed_at' => 'sometimes|nullable|date',
            'status' => 'required|in:open,won,lost,abandoned',
            'lost_reason_notes' => 'sometimes|nullable|string|max:1000',
            'notes' => 'sometimes|nullable|string|max:1000',
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
            'code.required' => 'O código é obrigatorio.',
            'code.string' => 'O código deve ser um texto.',
            'code.max' => 'O código não pode exceder 20 caracteres.',
            'code.unique' => 'O código informado já está em uso.',
            'title.required' => 'O título é obrigatorio.',
            'title.string' => 'O título deve ser um texto.',
            'title.max' => 'O título não pode exceder 200 caracteres.',
            'company_id.required' => 'O id da companhia é obrigatorio.',
            'company_id.exists' => 'A companhia informada não existe.',
            'primary_contact_id.exists' => 'O contato informado não existe.',
            'pipeline_id.required' => 'O id do pipeline é obrigatorio.',
            'pipeline_id.exists' => 'O pipeline informado não existe.',
            'stage_id.required' => 'O id do estágio é obrigatorio.',
            'stage_id.exists' => 'O estágio informado não existe.',
            'owner_id.required' => 'O id do usuário responsável é obrigatorio.',
            'owner_id.exists' => 'O usuário informado não existe.',
            'lost_reason_id.exists' => 'O motivo de perda informado não existe.',
            'estimated_one_off_value.numeric' => 'O valor estimado único deve ser um número.',
            'estimated_one_off_value.decimal' => 'O valor estimado único deve ter no máximo 2 casas decimais.',
            'estimated_one_off_value.max' => 'O valor estimado único não pode exceder 9999999999.99.',
            'estimated_recurring_value.numeric' => 'O valor estimado recorrente deve ser um número.',
            'estimated_recurring_value.decimal' => 'O valor estimado recorrente deve ter no máximo 2 casas decimais.',
            'estimated_recurring_value.max' => 'O valor estimado recorrente não pode exceder 9999999999.99.',
            'currency.string' => 'A moeda deve ser um texto.',
            'currency.size' => 'A moeda deve ter exatamente 3 caracteres.',
            'expected_close_date.date' => 'A data de fechamento esperado deve ser uma data válida.',
            'closed_at.date' => 'A data de fechamento deve ser uma data válida.',
            'status.required' => 'O status é obrigatorio.',
            'status.in' => 'O status informado é inválido.',
            'lost_reason_notes.string' => 'As notas do motivo de perda devem ser um texto.',
            'notes.string' => 'As notas devem ser um texto.',
        ];
    }
}
