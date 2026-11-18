<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\V1\UpdateUserRequest as BaseUpdateUserRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseUpdateUserRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['password'] = ['sometimes', 'nullable', 'string', Password::min(8)->mixedCase()->numbers()->symbols()];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
            'password.numbers' => 'A senha deve conter pelo menos um número.',
            'password.symbols' => 'A senha deve conter pelo menos um símbolo.',
            'email.unique' => 'Este e-mail já está cadastrado.',
        ];
    }
}
