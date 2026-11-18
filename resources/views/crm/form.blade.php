@extends('layouts.crm')

@section('title', ($record ? 'Editar ' : 'Novo ').$definition->singularLabel.' — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">{{ $record ? 'Editar' : 'Novo' }} {{ strtolower($definition->singularLabel) }}</h1>
    <p class="text-sm text-slate-500">{{ $record ? 'Atualize os dados do registro.' : 'Preencha os dados para criar um novo registro.' }}</p>
@endsection

@section('content')
    <div class="max-w-4xl">
        <a href="{{ route($definition->indexRoute()) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Voltar para {{ strtolower($definition->label) }}
        </a>

        <form method="POST" action="{{ $record ? route($definition->route('update'), $record) : route($definition->route('store')) }}" class="mt-4 rounded-2xl bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 sm:p-8">
            @csrf
            @if ($record)
                @method('PUT')
            @endif

            <h2 class="mb-6 text-xl font-semibold tracking-tight text-slate-900">Dados do {{ strtolower($definition->singularLabel) }}</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ($definition->fields as $field)
                    <x-crm-field :field="$field" :value="$record?->{$field['name']}" />
                @endforeach
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route($definition->indexRoute()) }}" class="rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:text-slate-900">Cancelar</a>
                <x-button>{{ $record ? 'Salvar alterações' : 'Criar '.strtolower($definition->singularLabel) }}</x-button>
            </div>
        </form>
    </div>
@endsection
