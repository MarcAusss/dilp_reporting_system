@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-context', 'Dashboard')

@section('content')

    <div class="space-y-7">

        {{-- Heading --}}
        <x-ui.page-header
            title="Dashboard"
            eyebrow="System Overview"
            description="Welcome to the DILP Reporting and Monitoring System. Project and accomplishment statistics will appear here as the operational modules become available."
        >
            <x-slot:actions>
                <div
                    class="hidden items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 sm:flex"
                >
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>

                    <span class="text-xs font-semibold text-slate-600">
                        Operational
                    </span>
                </div>
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Current account --}}
        <x-ui.alert
            variant="info"
            title="Foundation setup is active"
        >
            Authentication, authorization, role management, and the base
            government application interface are now connected.
        </x-ui.alert>

        {{-- Foundation overview --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <x-ui.card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400"
                        >
                            System Status
                        </p>

                        <p class="mt-3 text-xl font-bold text-slate-950">
                            Operational
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Core application available
                        </p>
                    </div>

                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-5 w-5"
                        >
                            <path d="m7 12 3 3 7-7" />
                            <circle cx="12" cy="12" r="9" />
                        </svg>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400"
                        >
                            Current Role
                        </p>

                        @php
                            $currentRoleValue = auth()->user()
                                ->getRoleNames()
                                ->first();

                            $currentRole = $currentRoleValue
                                ? \App\Enums\UserRole::tryFrom(
                                    $currentRoleValue
                                )
                                : null;
                        @endphp

                        <p class="mt-3 text-xl font-bold text-slate-950">
                            {{ $currentRole?->label() ?? 'User' }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Access determined by permissions
                        </p>
                    </div>

                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-[#164b73]"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-5 w-5"
                        >
                            <circle cx="12" cy="8" r="3.5" />
                            <path d="M5.5 20c.5-4.5 2.7-7 6.5-7s6 2.5 6.5 7" />
                        </svg>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400"
                        >
                            Initial Fiscal Year
                        </p>

                        <p class="mt-3 text-xl font-bold text-slate-950">
                            2026
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Target workbook migration basis
                        </p>
                    </div>

                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-5 w-5"
                        >
                            <rect x="4" y="5.5" width="16" height="14" rx="2" />
                            <path d="M8 3v5M16 3v5M4 10h16" />
                        </svg>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400"
                        >
                            Account Security
                        </p>

                        <p class="mt-3 text-xl font-bold text-slate-950">
                            Authenticated
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Protected session active
                        </p>
                    </div>

                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-5 w-5"
                        >
                            <rect x="5" y="10" width="14" height="10" rx="2" />
                            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                        </svg>
                    </div>
                </div>
            </x-ui.card>

        </div>

        <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">

            {{-- Module readiness --}}
            <x-ui.card :padding="false">

                <div
                    class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6"
                >
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">
                            System Modules
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Current implementation readiness
                        </p>
                    </div>

                    <x-ui.badge variant="info">
                        Foundation
                    </x-ui.badge>
                </div>

                <div class="divide-y divide-slate-100">

                    <div
                        class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-4 w-4"
                                >
                                    <path d="m7 12 3 3 7-7" />
                                </svg>
                            </span>

                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    Authentication & Authorization
                                </p>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Fortify + role and permission foundation
                                </p>
                            </div>
                        </div>

                        <x-ui.badge variant="success">
                            Ready
                        </x-ui.badge>
                    </div>

                    <div
                        class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-4 w-4"
                                >
                                    <path d="m7 12 3 3 7-7" />
                                </svg>
                            </span>

                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    Government UI Foundation
                                </p>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Responsive application shell
                                </p>
                            </div>
                        </div>

                        <x-ui.badge variant="success">
                            Ready
                        </x-ui.badge>
                    </div>

                    @foreach ([
                        ['Master Data', 'Province, municipality, funds and reference data'],
                        ['Project Registry', 'Primary replacement for the working spreadsheet'],
                        ['Workflow Monitoring', 'Evaluation through payment and awarding'],
                        ['Reports & Analytics', 'Summary, Per Fund, Per PO, SPRS and CQPR'],
                    ] as [$module, $description])

                        <div
                            class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-400"
                                >
                                    <span class="h-2 w-2 rounded-full bg-current"></span>
                                </span>

                                <div>
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ $module }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ $description }}
                                    </p>
                                </div>
                            </div>

                            <x-ui.badge>
                                Pending
                            </x-ui.badge>
                        </div>

                    @endforeach

                </div>

            </x-ui.card>

            {{-- Signed in user --}}
            <x-ui.card>

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">
                            Current Session
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Authenticated account
                        </p>
                    </div>

                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                </div>

                <div class="mt-6">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-xl bg-[#0f2a44] text-sm font-bold text-white"
                    >
                        {{ collect(explode(' ', trim(auth()->user()->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                            ->implode('') }}
                    </div>

                    <p class="mt-4 text-base font-bold text-slate-900">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ auth()->user()->email }}
                    </p>
                </div>

                <dl class="mt-6 space-y-4 border-t border-slate-100 pt-5">

                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs text-slate-500">
                            Role
                        </dt>

                        <dd class="text-right text-xs font-semibold text-slate-700">
                            {{ $currentRole?->label() ?? 'User' }}
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs text-slate-500">
                            Account
                        </dt>

                        <dd>
                            <x-ui.badge variant="success">
                                Active
                            </x-ui.badge>
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs text-slate-500">
                            Interface
                        </dt>

                        <dd class="text-right text-xs font-semibold text-slate-700">
                            Phase 1C
                        </dd>
                    </div>

                </dl>

            </x-ui.card>

        </div>

    </div>

@endsection