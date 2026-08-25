@props([
    'roleLabel' => 'User',
])

@php
    $user = auth()->user();

    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="sticky top-0 z-30 h-20 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-full items-center gap-4 px-4 sm:px-6 lg:px-8">

        {{-- Mobile sidebar --}}
        <button type="button"
            class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 lg:hidden"
            @click="sidebarOpen = true" aria-label="Open navigation">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
        </button>

        {{-- Context --}}
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <span>DILP</span>

                <svg viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                    <path d="m7.5 4 6 6-6 6" />
                </svg>

                <span class="truncate font-medium text-slate-600">
                    @yield('page-context', 'Dashboard')
                </span>
            </div>

            <p class="mt-1 truncate text-sm font-semibold text-slate-900 lg:hidden">
                DILP Reporting System
            </p>
        </div>

        {{-- Search placeholder --}}
        <div class="hidden w-full max-w-sm md:block">
            <div class="relative">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m16.5 16.5 4 4" />
                </svg>

                <input type="search" disabled placeholder="Search projects, proponents..."
                    class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-sm text-slate-500 outline-none placeholder:text-slate-400"
                    title="Global search will be enabled with the Project Registry.">
            </div>
        </div>

        {{-- FY --}}
        <div class="hidden items-center rounded-lg border border-slate-200 bg-white px-3 py-2 sm:flex">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                    Fiscal Year
                </p>

                <p class="text-xs font-semibold text-slate-700">
                    2026
                </p>
            </div>
        </div>

        {{-- Account menu --}}
        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
            <button type="button" @click="open = ! open"
                class="flex items-center gap-3 rounded-lg p-1.5 text-left transition hover:bg-slate-50"
                :aria-expanded="open" aria-haspopup="menu">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#0f2a44] text-xs font-bold text-white">
                    {{ $initials ?: 'U' }}
                </div>

                <div class="hidden min-w-0 xl:block">
                    <p class="max-w-40 truncate text-xs font-semibold text-slate-800">
                        {{ $user->name }}
                    </p>

                    <p class="mt-0.5 max-w-40 truncate text-[10px] text-slate-500">
                        {{ $roleLabel }}
                    </p>
                </div>

                <svg viewBox="0 0 20 20" fill="currentColor" class="hidden h-4 w-4 text-slate-400 xl:block"
                    aria-hidden="true">
                    <path d="m5 7 5 5 5-5" />
                </svg>
            </button>

            <div x-cloak x-show="open" x-transition.origin.top.right @click.outside="open = false"
                class="absolute right-0 mt-2 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10"
                role="menu">
                <div class="border-b border-slate-100 px-4 py-4">
                    <p class="truncate text-sm font-semibold text-slate-900">
                        {{ $user->name }}
                    </p>

                    <p class="mt-1 truncate text-xs text-slate-500">
                        {{ $user->email }}
                    </p>

                    <div class="mt-3">
                        <span
                            class="inline-flex rounded-md bg-blue-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-[#164b73]">
                            {{ $roleLabel }}
                        </span>
                    </div>
                </div>

                <div class="p-2">
                    <div class="rounded-lg px-3 py-2.5 text-xs leading-5 text-slate-500">
                        Account management will be available in a later phase.
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf

                        <button type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50"
                            role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                class="h-5 w-5">
                                <path d="M10 5H5v14h5M14 8l4 4-4 4M18 12H9" />
                            </svg>

                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</header>
