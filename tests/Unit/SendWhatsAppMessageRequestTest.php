<?php

namespace Tests\Unit;

use App\Http\Requests\V1\SendWhatsAppMessageRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SendWhatsAppMessageRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_allows_authenticated_user_with_crm_permission(): void
    {
        $request = $this->requestFor($this->contact(), User::factory()->create());

        $this->assertTrue($request->authorize());
    }

    public function test_authorize_denies_guest(): void
    {
        $request = $this->requestFor($this->contact());

        $this->assertFalse($request->authorize());
    }

    public function test_rules_use_custom_portuguese_messages(): void
    {
        $request = $this->requestFor($this->contact());

        $validator = Validator::make(['body' => ''], $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertSame('A mensagem é obrigatória.', $validator->errors()->first('body'));

        $validator = Validator::make(['body' => str_repeat('a', 4097)], $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertSame('A mensagem não pode exceder 4096 caracteres.', $validator->errors()->first('body'));
    }

    public function test_after_rejects_contact_without_a_valid_whatsapp(): void
    {
        $request = $this->requestFor($this->contact(null));

        $validator = Validator::make(['body' => 'Olá'], $request->rules(), $request->messages());

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        $this->assertTrue($validator->errors()->has('whatsapp'));
    }

    public function test_after_accepts_contact_with_a_valid_whatsapp(): void
    {
        $request = $this->requestFor($this->contact('11999999999'));

        $validator = Validator::make(['body' => 'Olá'], $request->rules(), $request->messages());

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        $this->assertFalse($validator->errors()->has('whatsapp'));
    }

    private function requestFor(Contact $contact, ?User $user = null): SendWhatsAppMessageRequest
    {
        $route = new Route('POST', '/test', []);
        $route->bind(HttpRequest::create('/test', 'POST'));
        $route->setParameter('contact', $contact);

        $request = SendWhatsAppMessageRequest::create('/test', 'POST', ['body' => 'Olá']);
        $request->setRouteResolver(static fn (): Route => $route);
        $request->setUserResolver(static fn (): ?User => $user);

        return $request;
    }

    private function contact(?string $whatsapp = '11999999999'): Contact
    {
        return Contact::create([
            'name' => 'Cliente Teste',
            'company_id' => Company::factory()->create()->id,
            'whatsapp' => $whatsapp,
        ]);
    }
}
