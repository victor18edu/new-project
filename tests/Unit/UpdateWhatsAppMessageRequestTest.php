<?php

namespace Tests\Unit;

use App\Http\Requests\V1\UpdateWhatsAppMessageRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateWhatsAppMessageRequestTest extends TestCase
{
    public function test_authorize_allows_any_caller(): void
    {
        $this->assertTrue((new UpdateWhatsAppMessageRequest)->authorize());
    }

    public function test_rules_accept_a_body_within_the_maximum_length(): void
    {
        $request = new UpdateWhatsAppMessageRequest;

        $this->assertTrue(Validator::make(['body' => 'Texto editado'], $request->rules())->passes());
        $this->assertTrue(Validator::make(['body' => str_repeat('a', 4096)], $request->rules())->passes());
    }

    public function test_rules_reject_missing_empty_and_oversized_bodies(): void
    {
        $request = new UpdateWhatsAppMessageRequest;

        $this->assertFalse(Validator::make([], $request->rules())->passes());
        $this->assertFalse(Validator::make(['body' => ''], $request->rules())->passes());
        $this->assertFalse(Validator::make(['body' => str_repeat('a', 4097)], $request->rules())->passes());
    }

    public function test_rules_use_custom_portuguese_messages(): void
    {
        $request = new UpdateWhatsAppMessageRequest;

        $validator = Validator::make([], $request->rules(), $request->messages());
        $this->assertSame('O texto da mensagem é obrigatório.', $validator->errors()->first('body'));

        $validator = Validator::make(['body' => 123], $request->rules(), $request->messages());
        $this->assertSame('O texto da mensagem deve ser um texto.', $validator->errors()->first('body'));

        $validator = Validator::make(['body' => str_repeat('a', 4097)], $request->rules(), $request->messages());
        $this->assertSame('A mensagem não pode exceder 4096 caracteres.', $validator->errors()->first('body'));
    }
}
