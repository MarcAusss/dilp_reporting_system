@props([
    'padding' => true,
])

<div
    {{ $attributes->merge([
        'class' =>
            'rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02]' .
            ($padding ? ' p-5 sm:p-6' : ''),
    ]) }}>
    {{ $slot }}
</div>
