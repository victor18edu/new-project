<?php

namespace App\Providers;

use App\Contracts\WhatsAppGateway;
use App\Http\Responses\LogoutResponse as CustomLogoutResponse;
use App\Listeners\IssueWebSessionToken;
use App\Listeners\RevokeWebSessionToken;
use App\Models\User;
use App\Services\WhatsApp\EvolutionApiGateway;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogoutResponseContract::class, CustomLogoutResponse::class);
        $this->app->bind(WhatsAppGateway::class, EvolutionApiGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-whatsapp-connection', fn (User $user): bool => $user->role === 'admin');

        Event::listen(Login::class, IssueWebSessionToken::class);
        Event::listen(Logout::class, RevokeWebSessionToken::class);
    }
}
