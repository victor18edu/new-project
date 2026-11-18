@extends('layouts.crm')

@section('title', 'Novo usuário — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">Novo usuário</h1>
    <p class="text-sm text-slate-500">Cadastre um novo usuário e defina seu perfil de acesso.</p>
@endsection

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('web.users.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Voltar para usuários
        </a>

        <div class="mt-4 rounded-2xl bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 sm:p-8">
            <h2 class="mb-6 text-xl font-semibold tracking-tight text-slate-900">Dados do usuário</h2>
            @include('users._form', ['user' => null, 'roles' => $roles])
        </div>
    </div>
@endsection
