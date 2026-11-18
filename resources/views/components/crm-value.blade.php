@props(['column', 'value' => null])

@php
    $format = $column['format'] ?? 'text';
@endphp

@if ($value === null || $value === '')
    <span class="text-slate-400">{{ $column['default'] ?? '—' }}</span>
@elseif ($format === 'datetime')
    {{ $value instanceof \Carbon\CarbonInterface ? $value->format('d/m/Y H:i') : $value }}
@elseif ($format === 'date')
    {{ $value instanceof \Carbon\CarbonInterface ? $value->format('d/m/Y') : $value }}
@elseif ($format === 'currency')
    R$ {{ number_format((float) $value, 2, ',', '.') }}
@elseif ($format === 'boolean')
    {{ $value ? 'Sim' : 'Não' }}
@elseif ($format === 'badge')
    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
        {{ $column['options'][$value] ?? $value }}
    </span>
@else
    {{ \Illuminate\Support\Str::limit((string) $value, 80) }}
@endif
