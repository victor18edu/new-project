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
