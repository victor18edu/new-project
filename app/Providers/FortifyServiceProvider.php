<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\FailedPasswordResetLinkRequestResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Mensagem genérica também quando o e-mail não existe (ou o link foi limitado).
        $this->app->bind(
            \Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse::class,
            FailedPasswordResetLinkRequestResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->input('email'))->first();

            // Mensagem única para e-mail inexistente, senha inválida ou usuário inativo.
            if (! $user || ! $user->is_active || ! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            // Evita registrar um log de auditoria de "updated" a cada login.
            User::withoutEvents(fn () => $user->forceFill(['last_login_at' => now()])->save());

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey)->response(fn () => response()->json([
                'message' => 'Muitas tentativas de acesso. Tente novamente em breve.',
            ], 429));
        });
    }
}
