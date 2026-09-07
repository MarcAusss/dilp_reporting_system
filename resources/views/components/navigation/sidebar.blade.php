@props([
    'roleLabel' => 'User',
])

{{-- Mobile backdrop --}}
<div x-cloak x-show="sidebarOpen" x-transition.opacity
    class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-[1px] lg:hidden" @click="sidebarOpen = false"
    aria-hidden="true"></div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    {{-- Brand --}}
    <div class="flex h-20 shrink-0 items-center justify-between border-b border-slate-100 px-5">
        <div class="flex min-w-0 items-center gap-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#0f2a44] text-[11px] font-bold tracking-wider text-white shadow-sm">
                DILP
            </div>

            <div class="min-w-0">
                <p class="truncate text-sm font-bold tracking-tight text-slate-900">
                    DILP Reporting
                </p>

                <p class="mt-0.5 truncate text-[11px] font-medium text-slate-500">
                    Monitoring System
                </p>
            </div>
        </div>

        <button type="button"
            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
            @click="sidebarOpen = false" aria-label="Close sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                <path d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <div class="flex-1 overflow-y-auto px-4 py-5">

        <nav aria-label="Main navigation" class="space-y-7">

            {{-- Overview --}}
            <div>
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                    Overview
                </p>

                <div class="space-y-1">
                    <x-navigation.sidebar-item label="Dashboard" route="dashboard" icon="dashboard" :active="request()->routeIs('dashboard')" />
                </div>
            </div>

            {{-- Project Management --}}
            <div>
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                    Project Management
                </p>

                <div class="space-y-1">
                    @can('projects.view')
                        <x-navigation.sidebar-item label="Projects" route="projects.index" icon="projects"
                            :active="request()->routeIs('projects.*')" />
                    @endcan

                    @can('work-queues.view')
                        <x-navigation.sidebar-item label="Work Queues" route="work-queues.index" icon="queue"
                            :active="request()->routeIs('work-queues.*')" />
                    @endcan
                </div>
            </div>

            {{-- Monitoring --}}
            <div>
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                    Monitoring
                </p>

                <div class="space-y-1">
                    @can('project-monitoring.view')
                        <x-navigation.sidebar-item label="Monitoring & Compliance" route="monitoring.index" icon="reports"
                            :active="request()->routeIs('monitoring.*')" />
                    @endcan

                    @can('fund-targets.view')
                        <x-navigation.sidebar-item label="Funds & Targets" route="funds.index" icon="funds" :active="request()->routeIs('funds.*')" />
                    @endcan

                    @can('beneficiaries.view')
                        <x-navigation.sidebar-item label="Beneficiaries" route="beneficiaries.index" icon="beneficiaries" :active="request()->routeIs('beneficiaries.*')" />
                    @endcan

                    @can('reports.view')
                        <x-navigation.sidebar-item label="Reports" route="reports.index" icon="reports"
                            :active="request()->routeIs('reports.*')" />
                    @endcan
                </div>
            </div>

            {{-- Super Admin only --}}
            @can('users.view')
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                        Administration
                    </p>

                    <div class="space-y-1">
                        @can('users.view')
                            <x-navigation.sidebar-item label="Users" route="users.index" icon="users" :active="request()->routeIs('users.*')" />
                        @endcan

                        @can('data-imports.view')
                            <x-navigation.sidebar-item label="Legacy Data Import" route="data-imports.index" icon="projects"
                                :active="request()->routeIs('data-imports.*')" />
                        @endcan

                        @can('data-quality.view')
                            <x-navigation.sidebar-item label="Data Quality" route="data-quality.index" icon="reports"
                                :active="request()->routeIs('data-quality.*')" />
                        @endcan

                        @can('master-data.view')
                            <x-navigation.sidebar-item label="Master Data" route="master-data.index" icon="settings"
                                :active="request()->routeIs('master-data.*')" />
                        @endcan

                        @can('master-data.view')
                            <x-navigation.sidebar-item label="Location Hierarchy" route="locations.index" icon="circle"
                                :active="request()->routeIs('locations.*')" />
                        @endcan

                        @can('audit-logs.view')
                            <x-navigation.sidebar-item label="Audit Logs" route="audit-logs.index" icon="audit"
                                :active="request()->routeIs('audit-logs.*')" />
                        @endcan


                        @can('spreadsheet-parity.view')
                            <x-navigation.sidebar-item label="Spreadsheet Parity" route="spreadsheet-parity.index" icon="reports"
                                :active="request()->routeIs('spreadsheet-parity.*')" />
                        @endcan
                    </div>
                </div>
            @endcan

        </nav>
    </div>

    {{-- Sidebar footer --}}
    <div class="shrink-0 border-t border-slate-100 p-4">
        <div class="rounded-lg bg-slate-50 px-3.5 py-3">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>

                <span class="text-xs font-semibold text-slate-700">
                    System Online
                </span>
            </div>

            <p class="mt-1.5 text-[11px] leading-5 text-slate-500">
                Signed in as
                <span class="font-semibold text-slate-600">
                    {{ $roleLabel }}
                </span>
            </p>
        </div>
    </div>
</aside>
