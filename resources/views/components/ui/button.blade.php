@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'secondary' => '
            border border-slate-300 bg-white text-slate-700
            hover:bg-slate-50
            focus:ring-slate-200
        ',

        'danger' => '
            bg-red-600 text-white
            hover:bg-red-700
            focus:ring-red-200
        ',

        default => '
            bg-[#0f2a44] text-white
            hover:bg-[#173d60]
            focus:ring-blue-200
        ',
    };

    $base = '
        inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5
        text-sm font-semibold transition
        focus:outline-none focus:ring-4
        disabled:cursor-not-allowed disabled:opacity-50
    ';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge([
        'class' => $base . ' ' . $classes,
    ]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
        {{ $attributes->merge([
            'class' => $base . ' ' . $classes,
        ]) }}>
        {{ $slot }}
    </button>
@endif
