<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePipelineStageRequest extends FormRequest
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
            'pipeline_id' => 'sometimes|required|exists:pipelines,id',
            'name' => 'sometimes|required|string|max:100',
            'order_index' => 'sometimes|nullable|integer',
            'probability_percent' => 'sometimes|nullable|numeric|min:0|max:100',
            'slug' => 'sometimes|required|string|max:120|unique:pipeline_stages,slug,' . $this->route('pipeline_stage') . ',id,pipeline_id,' . $this->input('pipeline_id'),
            'is_won_stage' => 'sometimes|nullable|boolean',
            'is_lost_stage' => 'sometimes|nullable|boolean',
            'color_hex' => 'sometimes|nullable|string|max:7',
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
            'pipeline_id.required' => 'O id do pipeline é obrigatorio.',
            'pipeline_id.exists' => 'O pipeline informado não existe.',
            'name.required' => 'O nome é obrigatorio.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome não pode exceder 100 caracteres.',
            'order_index.integer' => 'O índice de ordem deve ser um número inteiro.',
            'probability_percent.numeric' => 'A probabilidade percentual deve ser um número.',
            'probability_percent.min' => 'A probabilidade percentual deve ser maior ou igual a 0.',
            'probability_percent.max' => 'A probabilidade percentual não pode exceder 100.',
            'slug.required' => 'O slug é obrigatorio.',
            'slug.string' => 'O slug deve ser um texto.',
            'slug.max' => 'O slug não pode exceder 120 caracteres.',
            'slug.unique' => 'O slug informado já está em uso neste pipeline.',
            'color_hex.string' => 'A cor hexadecimal deve ser um texto.',
            'color_hex.max' => 'A cor hexadecimal não pode exceder 7 caracteres.',
        ];
    }
}
