@props(['checked' => false])

<input type="checkbox" @checked($checked) {!! $attributes->merge(['class' => 'rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-2 focus:ring-indigo-500/20']) !!}>
