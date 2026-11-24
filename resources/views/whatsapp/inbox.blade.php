@extends('layouts.crm')

@section('title', 'WhatsApp — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">WhatsApp</h1>
    <p class="text-sm text-slate-500">Conversas dos seus contatos</p>
@endsection

@section('content')
    <div class="grid gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <section class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
            <form method="GET" action="{{ route('web.whatsapp.inbox') }}" class="border-b border-slate-200 p-4">
                <label for="inbox-search" class="sr-only">Buscar conversa</label>
                <input id="inbox-search" type="search" name="q" value="{{ $search }}" placeholder="Buscar contato ou empresa" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </form>

            <ul class="divide-y divide-slate-100">
                @forelse ($contacts as $item)
                    <li>
                        <a href="{{ route('web.whatsapp.inbox.contact', $item) }}" @class([
                            'block px-4 py-3 transition',
                            'bg-emerald-50' => $selected?->id === $item->id,
                            'hover:bg-slate-50' => $selected?->id !== $item->id,
                        ])>
                            <div class="flex items-start gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                                    {{ strtoupper(mb_substr($item->name, 0, 1)) }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $item->name }}</p>
                                        @if ($item->latest_sent_at || $item->latest_created_at)
                                            <time class="shrink-0 text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($item->latest_sent_at ?? $item->latest_created_at)->format('d/m H:i') }}</time>
                                        @endif
                                    </div>

                                    <p class="truncate text-xs text-slate-500">
                                        {{ $item->company?->trade_name ?? 'Sem empresa' }}
                                        @if ($item->assignedUser)
                                            · {{ $item->assignedUser->name }}
                                        @endif
                                    </p>

                                    <p class="mt-1 truncate text-sm text-slate-600">
                                        @if ($item->latest_direction === 'outbound')
                                            <span class="text-slate-400">Você:</span>
                                        @endif
                                        {{ $item->latest_body ?: 'Sem mensagens ainda' }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-10 text-center text-sm text-slate-500">
                        Nenhuma conversa encontrada.
                    </li>
                @endforelse
            </ul>

            @if ($contacts->hasPages())
                <div class="border-t border-slate-200 p-3">
                    {{ $contacts->links() }}
                </div>
            @endif
        </section>

        <section class="flex h-[75vh] min-h-[32rem] flex-col overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
            @if ($selected)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-slate-900">{{ $selected->name }}</h2>
                        <p class="truncate text-xs text-slate-500">
                            {{ $selected->whatsapp }}
                            @if ($selected->company)
                                · {{ $selected->company->trade_name }}
                            @endif
                            @if ($selected->assignedUser)
                                · Responsável: {{ $selected->assignedUser->name }}
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('web.contacts.show', $selected) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium text-indigo-600 hover:bg-indigo-50">
                        Ver contato
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M18 6l-7.5 7.5M18 13.5V18a1.5 1.5 0 01-1.5 1.5H6A1.5 1.5 0 014.5 18V7.5A1.5 1.5 0 016 6h4.5" />
                        </svg>
                    </a>
                </div>

                <div class="flex min-h-0 flex-1 flex-col p-4 sm:p-6">
                    @include('whatsapp._conversation', [
                        'contact' => $selected,
                        'messages' => $messages,
                        'storeRoute' => $storeRoute,
                    ])
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center gap-2 p-8 text-center">
                    <span class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 11.25a8.25 8.25 0 01-11.7 7.5L3.75 20.25l1.5-4.8a8.25 8.25 0 1115-4.2z" />
                        </svg>
                    </span>
                    <p class="text-sm font-medium text-slate-700">Selecione uma conversa</p>
                    <p class="text-sm text-slate-500">Escolha um contato na lista para ver o histórico e responder.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
