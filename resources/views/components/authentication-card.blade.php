<div class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 sm:p-8">
    @isset($logo)
        <div class="mb-7 lg:hidden">
            {{ $logo }}
        </div>
    @endisset

    {{ $slot }}
</div>
