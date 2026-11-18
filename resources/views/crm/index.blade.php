@extends('layouts.crm')

@section('title', $definition->label.' — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">{{ $definition->label }}</h1>
    <p class="text-sm text-slate-500">Gerencie os registros de {{ strtolower($definition->label) }}.</p>
@endsection

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $definition->label }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $records->total() }} {{ $records->total() === 1 ? 'registro encontrado' : 'registros encontrados' }}.
            </p>
        </div>

        @can('create', $definition->modelClass)
            <a href="{{ route($definition->route('create')) }}" @class([
                'inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
            ])>
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                </svg>
                Novo {{ strtolower($definition->singularLabel) }}
            </a>
        @endcan
    </div>

    <div class="rounded-2xl bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
        <form method="GET" action="{{ route($definition->indexRoute()) }}" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4 sm:p-5">
            @if ($definition->searchable !== [])
                <div class="min-w-[220px] flex-1">
                    <x-label for="q" value="Buscar" />
                    <x-input id="q" name="q" type="search" class="mt-2" placeholder="Buscar..." :value="$search" />
                </div>
            @endif

            @foreach ($definition->filters as $filter)
                @php
                    $options = is_callable($filter['options']) ? call_user_func($filter['options']) : $filter['options'];
                @endphp
                <div class="w-full sm:w-44">
                    <x-label for="filter-{{ $filter['name'] }}" value="{{ $filter['label'] }}" />
                    <select id="filter-{{ $filter['name'] }}" name="{{ $filter['name'] }}" class="mt-2 block w-full rounded-lg border-slate-300 bg-white px-3 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Todos</option>
                        @foreach ($options as $value => $label)
                            <option value="{{ $value }}" @selected((string) $value === $filters[$filter['name']])>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <x-button class="w-full sm:w-auto">Filtrar</x-button>

            @if ($search !== '' || collect($filters)->contains(fn ($value) => $value !== ''))
                <a href="{{ route($definition->indexRoute()) }}" class="rounded-lg px-3 py-3 text-sm font-medium text-slate-500 hover:text-slate-700">Limpar</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        @foreach ($definition->columns as $column)
                            <th class="px-5 py-3">{{ $column['label'] }}</th>
                        @endforeach
                        <th class="px-5 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($records as $record)
                        <tr class="hover:bg-slate-50/70">
                            @foreach ($definition->columns as $column)
                                <td class="px-5 py-4 text-slate-600">
                                    <x-crm-value :column="$column" :value="data_get($record, $column['key'])" />
                                </td>
                            @endforeach
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route($definition->route('show'), $record) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Ver</a>

                                    @can('update', $record)
                                        <a href="{{ route($definition->route('edit'), $record) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Editar</a>
                                    @endcan

                                    @can('delete', $record)
                                        <form method="POST" action="{{ route($definition->route('destroy'), $record) }}" onsubmit="return confirm('Tem certeza que deseja remover este registro?');">
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
                            <td colspan="{{ count($definition->columns) + 1 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                                Nenhum registro encontrado com os filtros informados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $records->links() }}
            </div>
        @endif
    </div>
@endsection
