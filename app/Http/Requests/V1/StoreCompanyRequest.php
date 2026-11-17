<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
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
            'corporate_name' => 'required|string|max:200',
            'trade_name' => 'required|string|max:200',
            'tax_id' => 'sometimes|nullable|string|max:20|unique:companies,tax_id',
            'segment_id' => 'required|exists:segments,id',
            'lead_source_id' => 'sometimes|nullable|exists:lead_sources,id',
            'owner_id' => 'required|exists:users,id',
            'website_url' => 'sometimes|nullable|url|max:255',
            'instagram_handle' => 'sometimes|nullable|string|max:100',
            'google_maps_url' => 'sometimes|nullable|url|max:255',
            'website_status' => 'sometimes|nullable|in:none,outdated,broken,modern',
            'is_mobile_responsive' => 'sometimes|nullable|boolean',
            'has_whatsapp_button' => 'sometimes|nullable|boolean',
            'has_active_ads' => 'sometimes|nullable|boolean',
            'pain_points_notes' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string|max:200',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'postal_code' => 'sometimes|nullable|string|max:10',
            'status' => 'required|in:prospecting,contacted,qualified,customer,churned,unviable',
            'temperature' => 'sometimes|nullable|in:cold,warm,hot',
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
            'corporate_name.required' => 'O nome corporativo é obrigatorio.',
            'corporate_name.string' => 'O nome corporativo deve ser um texto.',
            'corporate_name.max' => 'O nome corporativo não pode exceder 200 caracteres.',
            'trade_name.required' => 'O nome fantasia é obrigatorio.',
            'trade_name.string' => 'O nome fantasia deve ser um texto.',
            'trade_name.max' => 'O nome fantasia não pode exceder 200 caracteres.',
            'tax_id.string' => 'O CNPJ deve ser um texto.',
            'tax_id.max' => 'O CNPJ não pode exceder 20 caracteres.',
            'tax_id.unique' => 'O CNPJ informado já está cadastrado.',
            'segment_id.required' => 'O id do segmento é obrigatorio.',
            'segment_id.exists' => 'O segmento informado não existe.',
            'lead_source_id.exists' => 'A fonte de lead informada não existe.',
            'owner_id.required' => 'O id do usuário responsável é obrigatorio.',
            'owner_id.exists' => 'O usuário informado não existe.',
            'website_url.url' => 'O website deve ser uma URL válida.',
            'website_url.max' => 'O website não pode exceder 255 caracteres.',
            'instagram_handle.string' => 'O Instagram deve ser um texto.',
            'instagram_handle.max' => 'O Instagram não pode exceder 100 caracteres.',
            'google_maps_url.url' => 'O Google Maps deve ser uma URL válida.',
            'google_maps_url.max' => 'O Google Maps não pode exceder 255 caracteres.',
            'website_status.in' => 'O status do website informado é inválido.',
            'address.string' => 'O endereço deve ser um texto.',
            'address.max' => 'O endereço não pode exceder 200 caracteres.',
            'city.required' => 'A cidade é obrigatoria.',
            'city.string' => 'A cidade deve ser um texto.',
            'city.max' => 'A cidade não pode exceder 100 caracteres.',
            'state.required' => 'O estado é obrigatorio.',
            'state.string' => 'O estado deve ser um texto.',
            'state.size' => 'O estado deve ter exatamente 2 caracteres.',
            'postal_code.string' => 'O CEP deve ser um texto.',
            'postal_code.max' => 'O CEP não pode exceder 10 caracteres.',
            'status.required' => 'O status é obrigatorio.',
            'status.in' => 'O status informado é inválido.',
            'temperature.in' => 'A temperatura informada é inválida.',
        ];
    }
}
