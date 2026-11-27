@extends('layouts.crm')

@section('title', 'Histórico de atendimentos — '.config('app.name', 'Laravel'))

@section('header')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="truncate text-lg font-semibold text-slate-900">Histórico de atendimentos</h1>
            <p class="text-sm text-slate-500">Atendimentos WhatsApp e auditoria de mensagens</p>
        </div>

        <a href="{{ route('web.whatsapp.inbox') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-indigo-600 shadow-sm ring-1 ring-slate-200 hover:bg-indigo-50">
            Ir para as conversas
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5H19.5v6M19.5 4.5L12 12M19.5 13.5V18a1.5 1.5 0 01-1.5 1.5H6A1.5 1.5 0 014.5 18V7.5A1.5 1.5 0 016 6h4.5" />
            </svg>
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
            <form method="GET" action="{{ route('web.whatsapp.history') }}" class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <label for="history-search" class="block text-xs font-medium text-slate-600">Contato</label>
                    <input id="history-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Nome ou WhatsApp" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="history-operator" class="block text-xs font-medium text-slate-600">Operador</label>
                    <select id="history-operator" name="operator_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        @foreach ($operators as $id => $name)
                            <option value="{{ $id }}" @selected((string) $id === $filters['operator_id'])>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="history-status" class="block text-xs font-medium text-slate-600">Status</label>
                    <select id="history-status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="history-category" class="block text-xs font-medium text-slate-600">Categoria</label>
                    <select id="history-category" name="customer_category_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todas</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected((string) $id === $filters['customer_category_id'])>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="history-from" class="block text-xs font-medium text-slate-600">De</label>
                    <input id="history-from" type="date" name="from" value="{{ $filters['from'] }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="history-to" class="block text-xs font-medium text-slate-600">Até</label>
                    <input id="history-to" type="date" name="to" value="{{ $filters['to'] }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="flex items-center justify-between gap-2 sm:col-span-2 lg:col-span-6">
                    <button type="submit" class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                        Filtrar
                    </button>
                    <a href="{{ route('web.whatsapp.history') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">Limpar filtros</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Contato</th>
                            <th class="px-4 py-3">Operador</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Início</th>
                            <th class="px-4 py-3">Fim</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($attendances as $attendance)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ $attendance->contact?->name ?? 'Contato removido' }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $attendance->contact?->whatsapp }}
                                        @if ($attendance->contact?->company)
                                            · {{ $attendance->contact->company->trade_name }}
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $attendance->operator?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-1 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $attendance->status === \App\Models\WhatsAppAttendance::STATUS_OPEN,
                                        'bg-slate-100 text-slate-600' => $attendance->status !== \App\Models\WhatsAppAttendance::STATUS_OPEN,
                                    ])>
                                        {{ \App\Models\WhatsAppAttendance::STATUS_LABELS[$attendance->status] ?? $attendance->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $attendance->started_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $attendance->ended_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-3">
                                        @if ($attendance->contact)
                                            <a href="{{ route('web.whatsapp.inbox.contact', $attendance->contact) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Conversa</a>
                                        @endif

                                        @can('close', $attendance)
                                            <form method="POST" action="{{ route('web.whatsapp.history.close', $attendance) }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="text" name="outcome_notes" maxlength="1000" placeholder="Resultado (opcional)" class="w-40 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <button type="submit" class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">
                                                    Encerrar
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Nenhum atendimento encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($attendances->hasPages())
                <div class="border-t border-slate-200 p-3">
                    {{ $attendances->links() }}
                </div>
            @endif
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <div class="overflow-hidden rounded-2xl bg-white p-5 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
                <h2 class="text-sm font-semibold text-slate-900">Mensagens editadas</h2>
                <p class="mt-1 text-xs text-slate-500">O texto original é preservado para auditoria.</p>

                <ul class="mt-4 space-y-3">
                    @forelse ($revisions as $revision)
                        <li class="rounded-lg bg-slate-50 p-3 text-xs">
                            <p class="font-medium text-slate-700">
                                {{ $revision->message?->contact?->name ?? 'Contato removido' }}
                                @if ($revision->editor)
                                    · {{ $revision->editor->name }}
                                @else
                                    · editada no celular
                                @endif
                                · {{ $revision->created_at?->format('d/m/Y H:i') }}
                            </p>
                            <p class="mt-1 text-red-700"><span class="font-semibold">Original:</span> {{ $revision->old_body }}</p>
                            <p class="mt-1 text-emerald-700"><span class="font-semibold">Novo:</span> {{ $revision->new_body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Nenhuma mensagem editada.</li>
                    @endforelse
                </ul>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white p-5 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80">
                <h2 class="text-sm font-semibold text-slate-900">Mensagens apagadas</h2>
                <p class="mt-1 text-xs text-slate-500">Não aparecem mais na conversa, mas permanecem registradas.</p>

                <ul class="mt-4 space-y-3">
                    @forelse ($deletedMessages as $deleted)
                        <li class="rounded-lg bg-slate-50 p-3 text-xs">
                            <p class="font-medium text-slate-700">
                                {{ $deleted->contact?->name ?? 'Contato removido' }}
                                @if ($deleted->deleter)
                                    · apagada por {{ $deleted->deleter->name }}
                                @elseif (($deleted->metadata['remote_delete_source'] ?? null) === 'phone')
                                    · apagada no celular
                                @endif
                                · {{ $deleted->deleted_at?->format('d/m/Y H:i') }}
                            </p>
                            <p class="mt-1 whitespace-pre-line text-slate-600">{{ $deleted->body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Nenhuma mensagem apagada.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
@endsection
