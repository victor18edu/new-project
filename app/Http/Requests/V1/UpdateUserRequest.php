<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:150',
            'email' => 'sometimes|required|email|max:150|unique:users,email,' . $this->route('user'),
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,sales_rep,manager',
            'is_active' => 'sometimes|boolean',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar_url' => 'sometimes|nullable|string|max:255',
            'last_login_at' => 'sometimes|nullable|date',
        ];
    }
}
