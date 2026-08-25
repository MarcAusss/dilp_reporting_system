@props(['label', 'route' => null, 'icon' => 'circle', 'active' => false, 'disabled' => false, 'badge' => null])

@php
    $baseClasses = '
        group flex w-full items-center gap-3 rounded-lg px-3 py-2.5
        text-sm font-medium transition
    ';

    $stateClasses = $active ? 'bg-blue-50 text-[#164b73]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950';

    if ($disabled) {
        $stateClasses = 'cursor-not-allowed text-slate-400';
    }
@endphp

@if (!$disabled && $route)

    <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
        {{ $attributes->merge([
            'class' => $baseClasses . ' ' . $stateClasses,
        ]) }}>
        <span
            class="
                flex h-5 w-5 shrink-0 items-center justify-center
                {{ $active ? 'text-[#164b73]' : 'text-slate-400 group-hover:text-slate-600' }}
            ">
            @switch($icon)
                @case('dashboard')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                @break

                @case('projects')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <path d="M4 7.5h16" />
                        <path
                            d="M7 3.5h10a2 2 0 0 1 2 2v14a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 19.5v-14a2 2 0 0 1 2-2Z" />
                        <path d="M8 11h8M8 15h6" />
                    </svg>
                @break

                @case('queue')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <circle cx="12" cy="12" r="8.5" />
                        <path d="M12 7.5V12l3 2" />
                    </svg>
                @break

                @case('funds')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <path d="M3 8.5 12 4l9 4.5" />
                        <path d="M5 9.5v7M9.5 9.5v7M14.5 9.5v7M19 9.5v7" />
                        <path d="M3 19.5h18M4 16.5h16" />
                    </svg>
                @break

                @case('beneficiaries')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <circle cx="9" cy="8" r="3" />
                        <path d="M3.5 19c.4-4 2.2-6 5.5-6s5.1 2 5.5 6" />
                        <circle cx="17" cy="9" r="2.2" />
                        <path d="M16 14c2.8.1 4.2 1.7 4.5 4.5" />
                    </svg>
                @break

                @case('reports')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <path d="M5 3.5h10l4 4v13H5z" />
                        <path d="M15 3.5v4h4M8 16v-3M12 16v-6M16 16v-4" />
                    </svg>
                @break

                @case('users')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <circle cx="9" cy="8" r="3" />
                        <path d="M3.5 19c.4-4 2.2-6 5.5-6s5.1 2 5.5 6" />
                        <path d="M16 7h5M18.5 4.5v5" />
                    </svg>
                @break

                @case('settings')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.5-2.5 1a7 7 0 0 0-1.8-1L14.3 3h-4.1L9.8 6a7 7 0 0 0-1.8 1L5.5 6 3.5 9.5 5.5 11a7 7 0 0 0 0 2l-2 1.5 2 3.5 2.5-1a7 7 0 0 0 1.8 1l.4 3h4.1l.4-3a7 7 0 0 0 1.8-1l2.5 1 2-3.5-2-1.5c.1-.3.1-.7.1-1Z" />
                    </svg>
                @break

                @case('audit')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"
                        aria-hidden="true">
                        <path d="M12 3 5 6v5c0 4.5 2.4 7.8 7 10 4.6-2.2 7-5.5 7-10V6z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                @break

                @default
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
            @endswitch
        </span>

        <span class="min-w-0 flex-1 truncate">
            {{ $label }}
        </span>

        @if ($badge)
            <span
                class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                {{ $badge }}
            </span>
        @endif
    </a>
@else
    <div {{ $attributes->merge([
        'class' => $baseClasses . ' ' . $stateClasses,
    ]) }}
        title="This module will be available in a later development phase.">
        <span class="flex h-5 w-5 shrink-0 items-center justify-center text-slate-300">
            @switch($icon)
                @case('projects')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <path d="M4 7.5h16" />
                        <path
                            d="M7 3.5h10a2 2 0 0 1 2 2v14a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 19.5v-14a2 2 0 0 1 2-2Z" />
                        <path d="M8 11h8M8 15h6" />
                    </svg>
                @break

                @case('queue')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <circle cx="12" cy="12" r="8.5" />
                        <path d="M12 7.5V12l3 2" />
                    </svg>
                @break

                @case('funds')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <path d="M3 8.5 12 4l9 4.5" />
                        <path d="M5 9.5v7M9.5 9.5v7M14.5 9.5v7M19 9.5v7" />
                        <path d="M3 19.5h18M4 16.5h16" />
                    </svg>
                @break

                @case('beneficiaries')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <circle cx="9" cy="8" r="3" />
                        <path d="M3.5 19c.4-4 2.2-6 5.5-6s5.1 2 5.5 6" />
                        <circle cx="17" cy="9" r="2.2" />
                    </svg>
                @break

                @case('reports')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <path d="M5 3.5h10l4 4v13H5z" />
                        <path d="M15 3.5v4h4M8 16v-3M12 16v-6M16 16v-4" />
                    </svg>
                @break

                @case('users')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <circle cx="9" cy="8" r="3" />
                        <path d="M3.5 19c.4-4 2.2-6 5.5-6s5.1 2 5.5 6" />
                    </svg>
                @break

                @case('settings')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.5-2.5 1a7 7 0 0 0-1.8-1L14.3 3h-4.1L9.8 6a7 7 0 0 0-1.8 1L5.5 6 3.5 9.5 5.5 11a7 7 0 0 0 0 2l-2 1.5 2 3.5 2.5-1a7 7 0 0 0 1.8 1l.4 3h4.1l.4-3a7 7 0 0 0 1.8-1l2.5 1 2-3.5-2-1.5c.1-.3.1-.7.1-1Z" />
                    </svg>
                @break

                @case('audit')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <path d="M12 3 5 6v5c0 4.5 2.4 7.8 7 10 4.6-2.2 7-5.5 7-10V6z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                @break

                @default
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
            @endswitch
        </span>

        <span class="min-w-0 flex-1 truncate">
            {{ $label }}
        </span>

        <span
            class="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-slate-400">
            Soon
        </span>
    </div>

@endif
