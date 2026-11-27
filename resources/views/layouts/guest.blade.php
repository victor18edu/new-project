<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('components.loading-overlay-head')

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-2" data-loading-root>
            <!-- Painel de marca -->
            <aside class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 px-12 py-14 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full bg-white/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-24 size-96 rounded-full bg-indigo-400/20 blur-3xl"></div>

                <a href="{{ url('/') }}" class="relative inline-flex items-center gap-3">
                    <x-brand-mark class="size-10 ring-1 ring-white/25" />
                    <span class="text-lg font-semibold tracking-tight">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="relative max-w-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-200">CRM de vendas</p>
                    <h1 class="mt-5 text-4xl font-semibold leading-tight tracking-tight">
                        Seu pipeline, seus clientes, tudo em um só lugar.
                    </h1>
                    <p class="mt-5 text-base leading-relaxed text-indigo-100/80">
                        Acompanhe oportunidades, atividades e resultados com clareza — de qualquer lugar.
                    </p>
                </div>

                <p class="relative text-xs text-indigo-200/70">Acesso restrito a usuários autorizados.</p>
            </aside>

            <!-- Formulário -->
            <main class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <x-brand-mark class="size-9" />
                        <span class="text-base font-semibold tracking-tight text-slate-900">{{ config('app.name', 'Laravel') }}</span>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>

        @livewireScripts

        <x-loading-overlay />
    </body>
</html>
