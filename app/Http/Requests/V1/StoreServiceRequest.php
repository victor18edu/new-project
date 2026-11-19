<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:150',
            'slug' => 'required|string|max:160|unique:services,slug',
            'description' => 'sometimes|nullable|string|max:1000',
            'base_price' => 'sometimes|nullable|numeric|decimal:0,2|max:9999999999.99',
            'pricing_type' => 'sometimes|nullable|in:one_off,monthly,hourly',
            'is_active' => 'sometimes|nullable|boolean',
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
            'category_id.required' => 'O id da categoria de serviço é obrigatorio.',
            'category_id.exists' => 'A categoria de serviço informada não existe.',
            'name.required' => 'O nome é obrigatorio.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome não pode exceder 150 caracteres.',
            'slug.required' => 'O slug é obrigatorio.',
            'slug.string' => 'O slug deve ser um texto.',
            'slug.max' => 'O slug não pode exceder 160 caracteres.',
            'slug.unique' => 'O slug informado já está em uso.',
            'description.string' => 'A descrição deve ser um texto.',
            'base_price.numeric' => 'O preço base deve ser um número.',
            'base_price.decimal' => 'O preço base deve ter no máximo 2 casas decimais.',
            'base_price.max' => 'O preço base não pode exceder 9999999999.99.',
            'pricing_type.in' => 'O tipo de preço informado é inválido.',
        ];
    }
}
