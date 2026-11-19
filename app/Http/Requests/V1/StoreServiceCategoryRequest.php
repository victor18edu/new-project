<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:120|unique:service_categories,slug',
            'description' => 'sometimes|nullable|string|max:1000',
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
            'name.required' => 'O nome é obrigatorio.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome não pode exceder 100 caracteres.',
            'slug.required' => 'O slug é obrigatorio.',
            'slug.string' => 'O slug deve ser um texto.',
            'slug.max' => 'O slug não pode exceder 120 caracteres.',
            'slug.unique' => 'O slug informado já está em uso.',
            'description.string' => 'A descrição deve ser um texto.',
        ];
    }
}
