@extends('layouts.crm')

@section('title', 'Conexão WhatsApp — '.config('app.name', 'Laravel'))

@section('header')
    <h1 class="truncate text-lg font-semibold text-slate-900">Conexão WhatsApp</h1>
    <p class="text-sm text-slate-500">Pareamento por QR Code do número dedicado do CRM</p>
@endsection

@section('content')
    <div x-data="{ state: '{{ $state }}', qr: '{{ $qr ?? '' }}', async refresh() { const response = await fetch('{{ route('web.whatsapp.connection.qr') }}'); if (response.ok) { const data = await response.json(); this.state = data.state; this.qr = data.qr; } } }"
         x-init="setInterval(() => refresh(), 5000)"
         class="mx-auto max-w-3xl space-y-5">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('web.whatsapp.inbox') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Voltar para a inbox
            </a>

            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1"
                  :class="state === 'open' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'">
                <span class="size-1.5 rounded-full" :class="state === 'open' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                <span x-text="state === 'open' ? 'Conectado' : (state === 'not_found' ? 'Instância não criada' : 'Aguardando pareamento')"></span>
            </span>
        </div>

        <section class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 sm:p-6">
            <div class="flex flex-col items-center gap-5 text-center">
                <template x-if="qr">
                    <div class="space-y-3">
                        <img :src="qr" alt="QR Code para parear o WhatsApp" class="mx-auto size-64 rounded-xl bg-white p-2 ring-1 ring-slate-200">
                        <p class="text-sm text-slate-600">Abra o WhatsApp no celular → <strong>Mais opções</strong> → <strong>Dispositivos conectados</strong> → escanear o código.</p>
                    </div>
                </template>

                <template x-if="! qr && state === 'open'">
                    <div class="space-y-2">
                        <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <p class="text-sm font-medium text-slate-800">WhatsApp conectado</p>
                        <p class="text-sm text-slate-500">A sessão está ativa e pronta para enviar e receber mensagens.</p>
                    </div>
                </template>

                <template x-if="! qr && state !== 'open'">
                    <div class="space-y-2">
                        <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 11.25a8.25 8.25 0 01-11.7 7.5L3.75 20.25l1.5-4.8a8.25 8.25 0 1115-4.2z" />
                            </svg>
                        </span>
                        <p class="text-sm font-medium text-slate-800">QR Code indisponível no momento</p>
                        <p class="text-sm text-slate-500">Use o botão abaixo para criar/configurar a instância e gerar um novo código.</p>
                    </div>
                </template>

                <form method="POST" action="{{ route('web.whatsapp.connection.store') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Criar / configurar instância
                    </button>
                </form>
            </div>
        </section>

        <section class="rounded-2xl bg-slate-100/70 p-5 ring-1 ring-slate-200/80">
            <h2 class="text-sm font-semibold text-slate-800">Instância</h2>
            <p class="mt-1 text-sm text-slate-600">
                Nome configurado: <code class="rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-slate-200">{{ $instance }}</code>
            </p>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                Use um <strong>número dedicado</strong>, mantenha uso humano normal, evite disparos em massa e não reconecte
                a sessão sem necessidade. O pareamento por QR usa o protocolo do WhatsApp Web (não oficial), portanto
                existe risco residual de bloqueio mesmo com esses cuidados.
            </p>
        </section>
    </div>
@endsection
