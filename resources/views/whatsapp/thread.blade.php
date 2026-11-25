@extends('layouts.crm')

@section('title', 'Conversa no WhatsApp — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">Conversa no WhatsApp</h1>
    <p class="text-sm text-slate-500">
        {{ $contact->name }}@if ($contact->company)
            · {{ $contact->company->trade_name }}
        @endif
    </p>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <a href="{{ route('web.contacts.show', $contact) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Voltar para o contato
        </a>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('web.whatsapp.inbox.contact', $contact) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                Abrir na inbox
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5H19.5v6M19.5 4.5L12 12M19.5 13.5V18a1.5 1.5 0 01-1.5 1.5H6A1.5 1.5 0 014.5 18V7.5A1.5 1.5 0 016 6h4.5" />
                </svg>
            </a>

            <a href="{{ route('web.whatsapp.history') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                Histórico de atendimentos
            </a>

            @if ($currentAttendance)
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                    Em atendimento @if ($currentAttendance->operator) · {{ $currentAttendance->operator->name }} @endif
                </span>
            @else
                @can('assign', $contact)
                    <form method="POST" action="{{ route('web.whatsapp.inbox.assume', $contact) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                            Assumir atendimento
                        </button>
                    </form>
                @endcan
            @endif
        </div>

        <div class="flex h-[75vh] min-h-[32rem] flex-col rounded-2xl bg-white p-4 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 sm:p-6">
            @include('whatsapp._conversation', [
                'contact' => $contact,
                'messages' => $messages,
                'storeRoute' => route('web.contacts.whatsapp.store', $contact),
            ])
        </div>
    </div>
@endsection
