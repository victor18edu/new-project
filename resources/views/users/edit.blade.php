@extends('layouts.crm')

@section('title', 'Editar usuário — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">Editar usuário</h1>
    <p class="text-sm text-slate-500">Atualize os dados de {{ $user->name }}.</p>
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
            <div class="mb-6 flex items-center gap-3 border-b border-slate-200 pb-5">
                <span class="flex size-11 items-center justify-center rounded-full bg-indigo-100 text-base font-semibold text-indigo-700">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                </div>
            </div>

            @include('users._form', ['user' => $user, 'roles' => $roles])
        </div>
    </div>
@endsection
