<?php

use App\Http\Controllers\Web\CrmResourceController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\WhatsAppConnectionController;
use App\Http\Controllers\Web\WhatsAppController;
use App\Http\Controllers\Web\WhatsAppHistoryController;
use App\Http\Controllers\Web\WhatsAppInboxController;
use App\Http\Controllers\Web\WhatsAppMessageController;
use App\Support\CrmResources;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware([
    'auth',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('users', UserController::class)->names('web.users');

    Route::get('whatsapp/conexao', [WhatsAppConnectionController::class, 'index'])
        ->name('web.whatsapp.connection');
    Route::post('whatsapp/conexao', [WhatsAppConnectionController::class, 'store'])
        ->name('web.whatsapp.connection.store');
    Route::get('whatsapp/conexao/qr', [WhatsAppConnectionController::class, 'qr'])
        ->name('web.whatsapp.connection.qr');

    Route::get('whatsapp', [WhatsAppInboxController::class, 'index'])
        ->name('web.whatsapp.inbox');
    Route::get('whatsapp/historico', [WhatsAppHistoryController::class, 'index'])
        ->name('web.whatsapp.history');
    Route::post('whatsapp/atendimentos/{attendance}/encerrar', [WhatsAppHistoryController::class, 'close'])
        ->name('web.whatsapp.history.close');
    Route::get('whatsapp/{contact}', [WhatsAppInboxController::class, 'show'])
        ->name('web.whatsapp.inbox.contact');
    Route::post('whatsapp/{contact}', [WhatsAppInboxController::class, 'store'])
        ->name('web.whatsapp.inbox.store');
    Route::post('whatsapp/{contact}/assumir', [WhatsAppInboxController::class, 'assume'])
        ->name('web.whatsapp.inbox.assume');
    Route::put('whatsapp/{contact}/mensagens/{message}', [WhatsAppMessageController::class, 'update'])
        ->name('web.whatsapp.messages.update');
    Route::post('whatsapp/{contact}/mensagens/{message}/reenviar', [WhatsAppMessageController::class, 'retry'])
        ->name('web.whatsapp.messages.retry');
    Route::delete('whatsapp/{contact}/mensagens/{message}', [WhatsAppMessageController::class, 'destroy'])
        ->name('web.whatsapp.messages.destroy');

    Route::get('contacts/{contact}/whatsapp', [WhatsAppController::class, 'show'])
        ->name('web.contacts.whatsapp.show');
    Route::post('contacts/{contact}/whatsapp', [WhatsAppController::class, 'store'])
        ->name('web.contacts.whatsapp.store');

    foreach (CrmResources::all() as $key => $definition) {
        Route::get($key, [CrmResourceController::class, 'index'])->name("web.{$key}.index");
        Route::get("{$key}/create", [CrmResourceController::class, 'create'])->name("web.{$key}.create");
        Route::post($key, [CrmResourceController::class, 'store'])->name("web.{$key}.store");
        Route::get("{$key}/{record}", [CrmResourceController::class, 'show'])->name("web.{$key}.show");
        Route::get("{$key}/{record}/edit", [CrmResourceController::class, 'edit'])->name("web.{$key}.edit");
        Route::put("{$key}/{record}", [CrmResourceController::class, 'update'])->name("web.{$key}.update");
        Route::delete("{$key}/{record}", [CrmResourceController::class, 'destroy'])->name("web.{$key}.destroy");
    }
});
