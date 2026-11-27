@props(['label' => 'Carregando…'])

<div {{ $attributes->merge(['class' => 'loading-overlay']) }}
     data-loading-overlay
     role="status"
     aria-live="polite"
     aria-atomic="true"
     aria-label="{{ $label }}"
     tabindex="-1"
     hidden>
    <div class="loading-overlay__panel">
        <span class="loading-overlay__spinner" aria-hidden="true"></span>
        <span class="loading-overlay__label">{{ $label }}</span>
    </div>
</div>
