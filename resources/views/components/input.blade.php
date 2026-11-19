@props(['disabled' => false, 'mask' => null, 'step' => null, 'required' => false, 'max' => null, 'maxValue' => null])

<input
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    @if ($mask === 'phone') data-phone-mask @endif
    @if ($step !== null) step="{{ $step }}" @endif
    @if ($max !== null) maxlength="{{ $max }}" @endif
    @if ($maxValue !== null) data-numeric-mask="{{ $maxValue }}" @endif
    {!! $attributes->merge(['class' => 'block w-full rounded-lg border-slate-300 bg-white px-4 py-3 text-sm leading-normal text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20']) !!}
>
