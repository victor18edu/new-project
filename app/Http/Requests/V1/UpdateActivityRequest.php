<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
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
            'activity_type_id' => 'sometimes|required|exists:activity_types,id',
            'company_id' => 'nullable|exists:companies,id',
            'contact_id' => 'sometimes|nullable|exists:contacts,id',
            'opportunity_id' => 'sometimes|nullable|exists:opportunities,id',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'created_by' => 'sometimes|nullable|exists:users,id',
            'title' => 'sometimes|required|string|max:200',
            'description' => 'sometimes|nullable|string',
            'due_date' => 'sometimes|nullable|date',
            'completed_at' => 'sometimes|nullable|date',
            'status' => 'sometimes|required|in:pending,completed,cancelled',
            'outcome_notes' => 'sometimes|nullable|string',
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
            'activity_type_id.required' => 'O id do tipo de atividade é obrigatorio.',
            'activity_type_id.exists' => 'O tipo de atividade informado não existe.',
            'company_id.exists' => 'A companhia informada não existe.',
            'contact_id.exists' => 'O contato informado não existe.',
            'opportunity_id.exists' => 'A oportunidade informada não existe.',
            'assigned_to.exists' => 'O usuário informado não existe.',
            'created_by.exists' => 'O usuário informado não existe.',
            'title.required' => 'O título é obrigatorio.',
            'title.string' => 'O título deve ser um texto.',
            'title.max' => 'O título não pode exceder 200 caracteres.',
            'description.string' => 'A descrição deve ser um texto.',
            'due_date.date' => 'A data de vencimento deve ser uma data válida.',
            'completed_at.date' => 'A data de conclusão deve ser uma data válida.',
            'status.required' => 'O status é obrigatorio.',
            'status.in' => 'O status informado é inválido.',
            'outcome_notes.string' => 'As notas do resultado devem ser um texto.',
        ];
    }
}
