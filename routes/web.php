<?php

use App\Http\Controllers\Web\CrmResourceController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\UserController;
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
