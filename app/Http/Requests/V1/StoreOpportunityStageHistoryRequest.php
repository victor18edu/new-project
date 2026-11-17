<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityStageHistoryRequest extends FormRequest
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
            'opportunity_id' => 'required|exists:opportunities,id',
            'from_stage_id' => 'sometimes|nullable|exists:pipeline_stages,id',
            'to_stage_id' => 'required|exists:pipeline_stages,id',
            'user_id' => 'required|exists:users,id',
            'duration_in_seconds' => 'sometimes|nullable|integer',
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
            'opportunity_id.required' => 'O id da oportunidade é obrigatorio.',
            'opportunity_id.exists' => 'A oportunidade informada não existe.',
            'from_stage_id.exists' => 'O estágio origem informado não existe.',
            'to_stage_id.required' => 'O id do estágio destino é obrigatorio.',
            'to_stage_id.exists' => 'O estágio destino informado não existe.',
            'user_id.required' => 'O id do usuário é obrigatorio.',
            'user_id.exists' => 'O usuário informado não existe.',
            'duration_in_seconds.integer' => 'A duração em segundos deve ser um número inteiro.',
        ];
    }
}