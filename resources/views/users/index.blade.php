@extends('layouts.crm')

@section('title', 'Usuários — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">Usuários</h1>
    <p class="text-sm text-slate-500">Gerencie os usuários e seus acessos.</p>
@endsection

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Usuários</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $users->total() }} {{ $users->total() === 1 ? 'usuário encontrado' : 'usuários encontrados' }}.</p>
        </div>

        @can('create', App\Models\User::class)
            <a href="{{ route('web.users.create') }}" @class([
                'inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
            ])>
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                </svg>
                Novo usuário
            </a>
        @endcan
    </div>

    <div class="rounded-2xl bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
        <form method="GET" action="{{ route('web.users.index') }}" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4 sm:p-5">
            <div class="min-w-[220px] flex-1">
                <x-label for="q" value="Buscar" />
                <x-input id="q" name="q" type="search" class="mt-2" placeholder="Nome ou e-mail" :value="$filters['q']" />
            </div>

            <div class="w-full sm:w-44">
                <x-label for="role" value="Perfil" />
                <select id="role" name="role" class="mt-2 block w-full rounded-lg border-slate-300 bg-white px-3 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todos</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-40">
                <x-label for="status" value="Status" />
                <select id="status" name="status" class="mt-2 block w-full rounded-lg border-slate-300 bg-white px-3 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todos</option>
                    <option value="active" @selected($filters['status'] === 'active')>Ativo</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Inativo</option>
                </select>
            </div>

            <x-button class="w-full sm:w-auto">Filtrar</x-button>

            @if ($filters['q'] !== '' || $filters['role'] !== '' || $filters['status'] !== '')
                <a href="{{ route('web.users.index') }}" class="rounded-lg px-3 py-3 text-sm font-medium text-slate-500 hover:text-slate-700">Limpar</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nome</th>
                        <th class="px-5 py-3">E-mail</th>
                        <th class="px-5 py-3">Perfil</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Último login</th>
                        <th class="px-5 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $user->name }}</p>
                                        @if ($user->phone)
                                            <p class="text-xs text-slate-500">{{ $user->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->email }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ $roles[$user->role] ?? $user->role }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span> Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        <span class="size-1.5 rounded-full bg-slate-400"></span> Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-500">
                                {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-3">
                                    @can('update', $user)
                                        <a href="{{ route('web.users.edit', $user) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Editar</a>
                                    @endcan

                                    @can('delete', $user)
                                        <form method="POST" action="{{ route('web.users.destroy', $user) }}" onsubmit="return confirm('Tem certeza que deseja remover este usuário?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Excluir</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                Nenhum usuário encontrado com os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
