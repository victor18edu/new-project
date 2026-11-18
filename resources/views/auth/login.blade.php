@section('title', 'Entrar — '.config('app.name', 'Laravel'))

<x-guest-layout>
    <x-authentication-card>
        <div class="mb-8">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Bem-vindo de volta</h2>
            <p class="mt-2 text-sm text-slate-500">Entre com sua conta para continuar.</p>
        </div>

        @session('status')
            <div class="mb-6 flex items-start gap-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200" role="status">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <span>{{ $value }}</span>
            </div>
        @endsession

        {{-- Mensagem única para credenciais inválidas: não revela qual campo falhou --}}
        @error('email')
            <div class="mb-6 flex items-start gap-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200" role="alert">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <span>{{ $message }}</span>
            </div>
        @enderror

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-label for="email" value="E-mail" />
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <x-input id="email" class="!pl-11" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                </div>
            </div>

            <div>
                <x-label for="password" value="Senha" />
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </span>
                    <x-input id="password" class="!pl-11" type="password" name="password" required autocomplete="current-password" />
                </div>
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember_me" class="flex items-center gap-2 text-sm text-slate-600">
                    <x-checkbox id="remember_me" name="remember" />
                    <span>Lembrar-me</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="rounded text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" href="{{ route('password.request') }}">
                        Esqueceu sua senha?
                    </a>
                @endif
            </div>

            <div class="pt-1">
                <x-button class="w-full">
                    Entrar
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </x-button>
            </div>
        </form>

        <p class="mt-8 text-center text-xs text-slate-400">
            © {{ now()->year }} {{ config('app.name', 'Laravel') }} · Acesso seguro
        </p>
    </x-authentication-card>
</x-guest-layout>
