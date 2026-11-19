@extends('layouts.crm')

@section('title', 'Detalhes do usuário — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">Detalhes do usuário</h1>
    <p class="text-sm text-slate-500">Consulte as informações cadastrais de {{ $user->name }}.</p>
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
            <div class="flex items-center gap-4 border-b border-slate-200 pb-6">
                <span class="flex size-14 items-center justify-center rounded-full bg-indigo-100 text-lg font-semibold text-indigo-700">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-6 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->name }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">E-mail</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->email }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telefone</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->phone ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perfil</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $roles[$user->role] ?? $user->role }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</dt>
                    <dd class="mt-1 text-sm">
                        @if ($user->is_active)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span> Ativo
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                <span class="size-1.5 rounded-full bg-slate-400"></span> Inativo
                            </span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Último login</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cadastrado em</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
            </dl>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('web.users.index') }}" class="rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:text-slate-900">Voltar</a>

                @can('update', $user)
                    <a href="{{ route('web.users.edit', $user) }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Editar
                    </a>
                @endcan

                @if ($user->id !== ($protectedUserId ?? null))
                    @can('delete', $user)
                        <x-delete-button
                            :action="route('web.users.destroy', $user)"
                            :form-id="'delete-user-'.$user->id"
                            message="Tem certeza que deseja remover este usuário?"
                            class="rounded-lg px-4 py-3 text-sm font-semibold text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        />
                    @endcan
                @endif
            </div>
        </div>
    </div>
@endsection
