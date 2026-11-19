@php
    $editing = isset($user) && $user !== null;
    $protected = $editing && $user->id === ($protectedUserId ?? null);
@endphp

<form method="POST" action="{{ $editing ? route('web.users.update', $user) : route('web.users.store') }}" class="space-y-5">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-1">
            <x-label for="name" value="Nome" :required="true" />
            <x-input id="name" name="name" type="text" class="mt-2" :value="old('name', $user->name ?? '')" :max="$limits['name'] ?? null" required autofocus autocomplete="name" />
            <x-input-error for="name" class="mt-2" />
        </div>

        <div class="sm:col-span-1">
            <x-label for="email" value="E-mail" :required="true" />
            <x-input id="email" name="email" type="email" class="mt-2" :value="old('email', $user->email ?? '')" :max="$limits['email'] ?? null" required autocomplete="email" />
            <x-input-error for="email" class="mt-2" />
        </div>

        <div class="sm:col-span-1">
            <x-label for="password" value="{{ $editing ? 'Nova senha' : 'Senha' }}" :required="! $editing" />
            <x-input id="password" name="password" type="password" class="mt-2" autocomplete="new-password" :required="! $editing" />
            <p class="mt-1 text-xs text-slate-500">{{ $editing ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 8 caracteres.' }}</p>
            <x-input-error for="password" class="mt-2" />
        </div>

        @unless ($protected)
            <div class="sm:col-span-1">
                <x-label for="phone" value="Telefone" />
                <x-input id="phone" name="phone" type="text" class="mt-2" :value="old('phone', $user->phone ?? '')" :max="$limits['phone'] ?? null" placeholder="(11) 99999-9999" autocomplete="tel" data-phone-mask />
                <x-input-error for="phone" class="mt-2" />
            </div>
        @endunless

        <div class="sm:col-span-1">
            <x-label for="role" value="Perfil" :required="! $protected" />
            @if ($protected)
                <p class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    {{ $roles[$user->role] ?? $user->role }}
                </p>
                <p class="mt-1 text-xs text-slate-500">O perfil do usuário principal não pode ser alterado.</p>
            @else
                <select id="role" name="role" class="mt-2 block w-full rounded-lg border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $user->role ?? 'sales_rep') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error for="role" class="mt-2" />
            @endif
        </div>

        <div class="sm:col-span-1">
            <x-label for="is_active" value="Status" />
            @if ($protected)
                <p class="mt-2 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <span class="size-2 rounded-full bg-emerald-500"></span> {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">O status do usuário principal não pode ser alterado.</p>
            @else
                <label for="is_active" class="mt-2 flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <x-checkbox id="is_active" name="is_active" value="1" :checked="old('is_active', $user->is_active ?? true)" />
                    <span>Usuário ativo</span>
                </label>
                <x-input-error for="is_active" class="mt-2" />
            @endif
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
        <a href="{{ route('web.users.index') }}" class="rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:text-slate-900">Cancelar</a>
        <x-button>{{ $editing ? 'Salvar alterações' : 'Criar usuário' }}</x-button>
    </div>
</form>
