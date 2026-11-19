@extends('layouts.crm')

@section('title', 'Painel — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">Painel</h1>
    <p class="text-sm text-slate-500">Acesse rapidamente todos os módulos do CRM.</p>
@endsection

@section('content')
    <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-700 px-6 py-8 text-white shadow-xl shadow-indigo-900/10 sm:px-8">
        <p class="text-sm font-medium text-indigo-100">Bem-vindo de volta, {{ Auth::user()->name }}!</p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Tudo que você precisa para gerenciar seu CRM</h2>
        <p class="mt-2 max-w-2xl text-sm text-indigo-100">Escolha um módulo abaixo para listar, criar, editar ou remover registros.</p>
    </div>

    <div x-data="{ environmentModalOpen: false }" @keydown.escape.window="environmentModalOpen = false" class="rounded-2xl bg-white p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-200/80 sm:p-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                    <span class="size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    Docker + Vite + Bootstrap 5
                </span>
                <h3 class="mt-4 text-lg font-semibold tracking-tight text-slate-900">Ambiente Frontend Conectado</h3>
                <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    Backend configurado em:
                    <code class="break-all rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700">{{ config('services.backend.base_url') }}</code>
                </p>
            </div>

            <button type="button" @click="environmentModalOpen = true" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
                Testar JS
            </button>
        </div>

        <div x-cloak x-show="environmentModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="environment-modal-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="environmentModalOpen = false" aria-hidden="true"></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h4 id="environment-modal-title" class="text-lg font-semibold text-slate-900">Bootstrap 5 Ativo</h4>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">O JavaScript do Bootstrap e o Vite HMR estão funcionando perfeitamente dentro do container!</p>
                    </div>
                </div>

                <div class="mt-7 flex justify-end">
                    <button type="button" @click="environmentModalOpen = false" class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    @foreach ($groups as $group => $items)
        <section class="space-y-4">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-semibold tracking-tight text-slate-900">{{ $group }}</h3>
                <span class="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ count($items) }}</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($items as $item)
                    <a href="{{ $item['route'] }}" class="group rounded-2xl bg-white p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-200/80 transition hover:-translate-y-0.5 hover:shadow-xl hover:ring-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900 group-hover:text-indigo-700">{{ $item['label'] }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $item['description'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                {{ $item['count'] }}
                            </span>
                        </div>
                        <p class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600">
                            Acessar módulo
                            <svg class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </p>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
