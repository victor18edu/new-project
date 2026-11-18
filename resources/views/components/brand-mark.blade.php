@props(['class' => 'size-10'])

<span {{ $attributes->merge(['class' => 'flex '.$class.' items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/25']) }}>
    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9m5 10V5m5 14v-7m5 7V8" />
    </svg>
</span>
