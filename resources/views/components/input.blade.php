@props([
    'type' => 'text',
])

<input
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-100',
    ]) }}
>
