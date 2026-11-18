@props(['field', 'value' => null])

@php
    $name = $field['name'];
    $type = $field['type'] ?? 'text';
    $current = old($name, $value);
    $options = isset($field['options']) ? (is_callable($field['options']) ? call_user_func($field['options']) : $field['options']) : [];
    $inputType = $type === 'decimal' ? 'number' : $type;

    if ($current instanceof \Carbon\CarbonInterface) {
        $current = $type === 'date'
            ? $current->format('Y-m-d')
            : ($type === 'datetime-local' ? $current->format('Y-m-d\TH:i') : (string) $current);
    }
@endphp

<div class="{{ ($field['col'] ?? 'half') === 'full' ? 'sm:col-span-2' : 'sm:col-span-1' }}">
    <x-label for="{{ $name }}" value="{{ $field['label'] }}" />

    @switch($type)
        @case('textarea')
            <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 4 }}" class="mt-2 block w-full rounded-lg border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="{{ $field['placeholder'] ?? '' }}" {{ isset($field['required']) && $field['required'] ? 'required' : '' }}>{{ $current }}</textarea>
            @break

        @case('select')
            <select id="{{ $name }}" name="{{ $name }}" class="mt-2 block w-full rounded-lg border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" {{ isset($field['required']) && $field['required'] ? 'required' : '' }}>
                @if (! isset($field['required']) || ! $field['required'])
                    <option value="">{{ $field['empty'] ?? 'Nenhum' }}</option>
                @endif
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $current)>{{ $optionLabel }}</option>
                @endforeach
            </select>
            @break

        @case('checkbox')
            <label for="{{ $name }}" class="mt-2 flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700">
                <input type="hidden" name="{{ $name }}" value="0">
                <x-checkbox id="{{ $name }}" name="{{ $name }}" value="1" :checked="(bool) $current" />
                <span>{{ $field['help'] ?? 'Sim' }}</span>
            </label>
            @break

        @default
            <x-input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" class="mt-2" :value="$current" placeholder="{{ $field['placeholder'] ?? '' }}" {{ isset($field['required']) && $field['required'] ? 'required' : '' }} @if (in_array($type, ['number', 'decimal'], true)) step="{{ $field['step'] ?? 'any' }}" @endif />
    @endswitch

    @if (isset($field['help']) && $type !== 'checkbox')
        <p class="mt-1 text-xs text-slate-500">{{ $field['help'] }}</p>
    @endif

    <x-input-error for="{{ $name }}" class="mt-2" />
</div>
