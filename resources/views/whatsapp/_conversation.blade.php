@php
    $statusLabels = [
        'queued' => 'Na fila',
        'sent' => 'Enviada',
        'delivered' => 'Entregue',
        'read' => 'Lida',
        'failed' => 'Falhou',
        'received' => 'Recebida',
    ];
@endphp

@if (filled($contact->whatsapp))
    <div class="flex min-h-0 flex-1 flex-col gap-4">
        <div data-chat-scroll class="min-h-0 flex-1 overflow-y-auto pr-1">
            <div class="space-y-4">
                @forelse ($messages as $message)
                    <div class="flex {{ $message->direction === 'outbound' ? 'justify-end' : 'justify-start' }}"
                         x-data="{
                             editing: false,
                             body: @js($message->body),
                         }">
                        <div @class([
                            'max-w-[85%] rounded-2xl px-4 py-3 text-sm shadow-sm sm:max-w-[70%]',
                            'bg-emerald-50 text-emerald-950' => $message->direction === 'outbound',
                            'bg-slate-100 text-slate-800' => $message->direction === 'inbound',
                        ])>
                            <template x-if="! editing">
                                <p class="whitespace-pre-line break-words">{{ $message->body }}</p>
                            </template>

                            <template x-if="editing">
                                <form method="POST" action="{{ route('web.whatsapp.messages.update', [$contact, $message]) }}" class="space-y-2">
                                    @csrf
                                    @method('PUT')

                                    <textarea x-model="body" name="body" rows="3" maxlength="4096" required
                                              class="block w-full min-w-[16rem] rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>

                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="editing = false"
                                                class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-white/60">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                class="rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </template>

                            <p class="mt-1 text-xs opacity-70">
                                {{ ($message->sent_at ?? $message->created_at)?->format('d/m/Y H:i') }}
                                · {{ $statusLabels[$message->status] ?? $message->status }}
                                @if ($message->direction === 'outbound' && $message->sender)
                                    · {{ $message->sender->name }}
                                @endif
                                @if ($message->edited_at)
                                    · editado
                                    @if ($message->editor)
                                        por {{ $message->editor->name }}
                                    @endif
                                @endif
                            </p>

                            @if (\Illuminate\Support\Facades\Gate::check('update', $message) || \Illuminate\Support\Facades\Gate::check('delete', $message))
                                <div class="mt-2 flex flex-wrap items-center gap-2 border-t border-current/10 pt-2 text-xs">
                                    @can('update', $message)
                                        <button type="button" @click="editing = true"
                                                class="rounded px-2 py-1 font-medium text-indigo-700 hover:bg-white/60">
                                            Editar
                                        </button>
                                    @endcan

                                    @can('retry', $message)
                                        <form method="POST" action="{{ route('web.whatsapp.messages.retry', [$contact, $message]) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="rounded px-2 py-1 font-medium text-emerald-700 hover:bg-white/60">
                                                Reenviar
                                            </button>
                                        </form>
                                    @endcan

                                    @can('delete', $message)
                                        <x-delete-button
                                            :action="route('web.whatsapp.messages.destroy', [$contact, $message])"
                                            :form-id="'delete-message-'.$contact->id.'-'.$message->id"
                                            message="Apagar esta mensagem da conversa e do WhatsApp? Ela continuará registrada no histórico de auditoria."
                                            label="Apagar"
                                            class="rounded px-2 py-1 font-medium text-red-700 hover:bg-white/60"
                                        />
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        Nenhuma mensagem trocada com este contato ainda.
                    </p>
                @endforelse
            </div>

            @if ($messages->hasPages())
                <div class="mt-6 border-t border-slate-200 pt-5">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>

        @can('sendMessage', $contact)
            <form method="POST" action="{{ $storeRoute }}" class="shrink-0 border-t border-slate-200 pt-5">
                @csrf

                <div>
                    <label for="body" class="block text-sm font-medium text-slate-700">Mensagem</label>
                    <textarea id="body" name="body" rows="4" maxlength="4096" required placeholder="Escreva a mensagem para {{ $contact->name }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('whatsapp')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Enviar mensagem
                    </button>
                </div>
            </form>
        @else
            <p class="shrink-0 border-t border-slate-200 pt-4 text-sm text-slate-500">
                Você não possui permissão para enviar mensagens nesta conversa.
            </p>
        @endcan

        <script>
            (() => {
                const scroller = document.querySelector('[data-chat-scroll]');

                if (scroller) {
                    scroller.scrollTop = scroller.scrollHeight;
                }
            })();
        </script>
    </div>
@else
    <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200" role="status">
        Este contato não possui um número de WhatsApp cadastrado. Edite o contato para adicionar um número antes de enviar mensagens.
    </div>
@endif
