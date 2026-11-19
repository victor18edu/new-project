<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WebAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_web_com_credenciais_validas_emite_token_web_session(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => Hash::make('senha123456'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'joao@example.com',
            'password' => 'senha123456',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $tokenId = session('web_session_token_id');

        $this->assertNotNull($tokenId);
        $this->assertNotNull(session('web_session_token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'name' => 'web-session',
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_login_web_com_credenciais_invalidas(): void
    {
        User::factory()->create([
            'email' => 'joao@example.com',
            'password' => Hash::make('senha123456'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'joao@example.com',
            'password' => 'senha-errada',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_web_de_usuario_inativo_e_rejeitado(): void
    {
        User::factory()->create([
            'email' => 'inativo@example.com',
            'password' => Hash::make('senha123456'),
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'inativo@example.com',
            'password' => 'senha123456',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_usuario_autenticado_e_redirecionado_para_dashboard_na_rota_raiz(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/dashboard');
    }

    public function test_logout_web_encerra_sessao_e_revoga_token_web_session(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => Hash::make('senha123456'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => 'joao@example.com',
            'password' => 'senha123456',
        ])->assertRedirect('/dashboard');

        $tokenId = session('web_session_token_id');
        $this->assertNotNull($tokenId);

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        $this->assertNull(session('web_session_token'));
        $this->assertNull(session('web_session_token_id'));
    }

    public function test_sessao_persiste_ao_recarregar_pagina_protegida(): void
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => Hash::make('senha123456'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => 'joao@example.com',
            'password' => 'senha123456',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
        $this->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_recuperacao_de_senha_web_envia_link_e_redefine_senha(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'joao@example.com']);

        $this->post('/forgot-password', ['email' => 'joao@example.com'])
            ->assertSessionHas('status');

        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);
        $this->get('/reset-password/'.$token)->assertOk();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'joao@example.com',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('nova-senha-123', $user->fresh()->password));
    }

    public function test_forgot_password_web_usa_mensagem_generica_para_email_inexistente(): void
    {
        User::factory()->create(['email' => 'existe@example.com']);

        $this->post('/forgot-password', ['email' => 'existe@example.com'])
            ->assertSessionHas('status', __('passwords.sent'));

        $this->post('/forgot-password', ['email' => 'naoexiste@example.com'])
            ->assertSessionHas('status', __('passwords.sent'));
    }
}
