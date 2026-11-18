@section('title', 'Recuperar senha — '.config('app.name', 'Laravel'))

<x-guest-layout>
    <x-authentication-card>
        <div class="mb-8">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Recuperar senha</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Informe seu e-mail e enviaremos um link para você definir uma nova senha.
            </p>
        </div>

        @session('status')
            <div class="mb-6 flex items-start gap-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200" role="status">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <span>{{ $value }}</span>
            </div>
        @endsession

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-button class="w-full">
                Enviar link de redefinição
            </x-button>
        </form>

        <div class="mt-8 text-center">
            <a class="rounded text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" href="{{ route('login') }}">
                Voltar para o login
            </a>
        </div>
    </x-authentication-card>
</x-guest-layout>
