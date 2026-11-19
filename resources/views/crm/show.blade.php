@extends('layouts.crm')

@section('title', 'Detalhes do '.mb_strtolower($definition->singularLabel).' — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">Detalhes do {{ mb_strtolower($definition->singularLabel) }}</h1>
    <p class="text-sm text-slate-500">Consulte as informações do registro #{{ $record->getKey() }}.</p>
@endsection

@section('content')
    <div class="max-w-4xl">
        <a href="{{ route($definition->indexRoute()) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Voltar para {{ mb_strtolower($definition->label) }}
        </a>

        <div class="mt-4 rounded-2xl bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 sm:p-8">
            @php
                $titleColumn = $definition->columns[0] ?? null;
                $title = $titleColumn ? data_get($record, $titleColumn['key']) : null;
            @endphp

            <div class="border-b border-slate-200 pb-6">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">
                    {{ $title ?: $definition->singularLabel }}
                </h2>
                <p class="text-sm text-slate-500">{{ $definition->singularLabel }} #{{ $record->getKey() }}</p>
            </div>

            <dl class="mt-6 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                @foreach ($definition->detailRows($record) as $row)
                    <div @class(['sm:col-span-2' => $row['key'] === 'description' || str_contains($row['key'], 'notes')])>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $row['label'] }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-slate-900">
                            <x-crm-value :column="$row" :value="$row['value']" :truncate="false" />
                        </dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route($definition->indexRoute()) }}" class="rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:text-slate-900">Voltar</a>

                @can('update', $record)
                    <a href="{{ route($definition->route('edit'), $record) }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Editar
                    </a>
                @endcan

                @can('delete', $record)
                    <x-delete-button
                        :action="route($definition->route('destroy'), $record)"
                        :form-id="'delete-'.$definition->key.'-'.$record->getKey()"
                        message="Tem certeza que deseja remover este registro?"
                        class="rounded-lg px-4 py-3 text-sm font-semibold text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    />
                @endcan
            </div>
        </div>
    </div>
@endsection
