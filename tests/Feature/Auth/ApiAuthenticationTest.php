<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_api_com_credenciais_validas_emite_token(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => Hash::make('senha123456'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'senha123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotNull($response->json('data.token'));
        $this->assertNotNull($response->json('data.expires_at'));
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'api-token',
            'tokenable_id' => $user->id,
        ]);
        $this->assertSame(
            ['read', 'create', 'update', 'delete'],
            $user->fresh()->tokens()->first()->abilities
        );
    }

    public function test_login_api_com_credenciais_invalidas_retorna_401_generico(): void
    {
        User::factory()->create([
            'email' => 'joao@example.com',
            'password' => Hash::make('senha123456'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'senha-errada',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'E-mail ou senha inválidos.');
        $this->assertArrayNotHasKey('token', $response->json());
    }

    public function test_login_api_de_usuario_inativo_retorna_mesma_mensagem(): void
    {
        User::factory()->create([
            'email' => 'inativo@example.com',
            'password' => Hash::make('senha123456'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inativo@example.com',
            'password' => 'senha123456',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'E-mail ou senha inválidos.');
    }

    public function test_me_com_token_valido_retorna_dados_do_usuario(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('teste', ['read']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', $user->fresh()->role)
            ->assertJsonMissingPath('data.password');
    }

    public function test_me_sem_token_retorna_json_401(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Não autenticado.');
    }

    public function test_me_com_token_expirado_retorna_401(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('expirado', ['read'], now()->subMinute());

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_logout_api_revoga_somente_o_token_atual(): void
    {
        $user = User::factory()->create();
        $tokenAtual = $user->createToken('api-token', ['read']);
        $outroToken = $user->createToken('outro', ['read']);

        $this->withToken($tokenAtual->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(PersonalAccessToken::find($tokenAtual->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($outroToken->accessToken->id));
    }

    public function test_forgot_password_api_retorna_mesma_mensagem_para_email_existente_e_inexistente(): void
    {
        User::factory()->create(['email' => 'existe@example.com']);

        $existente = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'existe@example.com',
        ]);

        $inexistente = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'naoexiste@example.com',
        ]);

        $mensagem = 'Se o e-mail estiver cadastrado, enviaremos o link de redefinição de senha.';

        $existente->assertOk()->assertJsonPath('message', $mensagem);
        $inexistente->assertOk()->assertJsonPath('message', $mensagem);
        $this->assertSame($existente->json('message'), $inexistente->json('message'));
    }
}
