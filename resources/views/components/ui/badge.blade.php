@props([
    'variant' => 'neutral',
])

@php
    $classes = match ($variant) {
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/15',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/15',
        'info' => 'bg-blue-50 text-blue-700 ring-blue-600/15',
        default => 'bg-slate-100 text-slate-600 ring-slate-500/10',
    };
@endphp

<span
    {{ $attributes->merge([
        'class' => "
                inline-flex items-center rounded-md px-2 py-1 text-[10px]
                font-bold uppercase tracking-wide ring-1 ring-inset
                {$classes}
            ",
    ]) }}>
    {{ $slot }}
</span>
