<!-- Overlay do menu mobile -->
<div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

<aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-900 text-white transition-transform duration-200 lg:static lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="flex h-16 shrink-0 items-center gap-3 px-6">
        <x-brand-mark class="size-9 ring-1 ring-white/20" />
        <span class="text-base font-semibold tracking-tight">{{ config('app.name', 'Laravel') }}</span>
        <button type="button" class="ms-auto rounded-lg p-1.5 text-slate-300 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false" aria-label="Fechar menu">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-5">
        <div class="space-y-1">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Geral</p>

            <a href="{{ route('dashboard') }}" @class([
                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                'bg-indigo-600 text-white' => request()->routeIs('dashboard'),
                'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('dashboard'),
            ])>
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5v10.5H3.75zM8.25 6.75V4.5m7.5 2.25V4.5M3.75 12h16.5" />
                </svg>
                Painel
            </a>

            <a href="{{ route('web.users.index') }}" @class([
                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                'bg-indigo-600 text-white' => request()->routeIs('web.users.*'),
                'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('web.users.*'),
            ])>
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                Usuários
            </a>

            @can('viewInbox', \App\Models\Contact::class)
                <div x-data="{ whatsappOpen: {{ request()->routeIs('web.whatsapp.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button type="button"
                            @click="whatsappOpen = !whatsappOpen"
                            :aria-expanded="whatsappOpen.toString()"
                            aria-controls="whatsapp-submenu"
                            @class([
                                'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                                'bg-indigo-600 text-white' => request()->routeIs('web.whatsapp.*'),
                                'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('web.whatsapp.*'),
                            ])>
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 11.25a8.25 8.25 0 01-11.7 7.5L3.75 20.25l1.5-4.8a8.25 8.25 0 1115-4.2z" />
                        </svg>
                        <span class="flex-1 text-start">WhatsApp</span>
                        <svg class="size-4 shrink-0 transition-transform" :class="whatsappOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                        </svg>
                    </button>

                    <div id="whatsapp-submenu" x-cloak x-show="whatsappOpen" class="ms-4 space-y-1 border-l border-white/10 ps-3">
                        @if (Auth::user()->role === 'admin')
                            <a href="{{ route('web.whatsapp.connection') }}"
                               @click="sidebarOpen = false"
                               @class([
                                   'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                   'bg-indigo-600 text-white' => request()->routeIs('web.whatsapp.connection*'),
                                   'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('web.whatsapp.connection*'),
                               ])>
                                <svg class="size-4 shrink-0 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5v2.25m7.5-2.25v2.25m-9.75 3h12a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-12A2.25 2.25 0 014.5 17.25v-7.5A2.25 2.25 0 016.75 7.5zm7.5 5.25h3m-3 3h3m-9-3h.008v.008H9.25V12.75zm0 3h.008v.008H9.25V15.75zm3-3h.008v.008H12.25V12.75z" />
                                </svg>
                                Conexão
                            </a>
                        @endif

                        <a href="{{ route('web.whatsapp.inbox') }}"
                           @click="sidebarOpen = false"
                           @class([
                               'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                               'bg-indigo-600 text-white' => request()->routeIs('web.whatsapp.inbox*'),
                               'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('web.whatsapp.inbox*'),
                           ])>
                            <svg class="size-4 shrink-0 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 11.25a8.25 8.25 0 01-11.7 7.5L3.75 20.25l1.5-4.8a8.25 8.25 0 1115-4.2z" />
                            </svg>
                            Conversas
                        </a>

                        @can('viewHistory', \App\Models\Contact::class)
                            <a href="{{ route('web.whatsapp.history') }}"
                               @click="sidebarOpen = false"
                               @class([
                                   'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                   'bg-indigo-600 text-white' => request()->routeIs('web.whatsapp.history*'),
                                   'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('web.whatsapp.history*'),
                               ])>
                                <svg class="size-4 shrink-0 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Histórico
                            </a>
                        @endcan
                    </div>
                </div>
            @endcan
        </div>

        @foreach (App\Support\CrmResources::grouped() as $group => $definitions)
            <div class="space-y-1">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $group }}</p>

                @foreach ($definitions as $definition)
                    <a href="{{ route($definition->indexRoute()) }}" @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-indigo-600 text-white' => request()->routeIs('web.'.$definition->key.'.*'),
                        'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('web.'.$definition->key.'.*'),
                    ])>
                        <svg class="size-4 shrink-0 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5h7.5a2.25 2.25 0 012.25 2.25v10.5a2.25 2.25 0 01-2.25 2.25h-7.5A2.25 2.25 0 016 17.25V6.75A2.25 2.25 0 018.25 4.5zm1.5 4.5h4.5m-4.5 3h4.5m-4.5 3h2.25" />
                        </svg>
                        {{ $definition->label }}
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-white/10 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
                Sair
            </button>
        </form>
    </div>
</aside>
