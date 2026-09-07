@extends('layouts.app')

@section('title', 'Project Profile')
@section('page-context', 'Project Profile')

@section('content')
    <div class="space-y-7">
        <x-ui.page-header title="Project Profile" eyebrow="Project Registry"
            description="Consolidated DILP project profile, financial data, workflow, and implementation coverage.">
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    Back to Projects
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-projects.profile-tabs :project="$project" />
        <x-projects.identity-card :project="$project" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-ui.card>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Beneficiaries</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($project->beneficiaries->count()) }}</p>
                <p class="mt-1 text-xs text-slate-500">Individual records linked to this project</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Livelihood Types</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($project->livelihoods->count()) }}</p>
                <p class="mt-1 text-xs text-slate-500">Livelihood classifications recorded</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Budget Detail</p>
                <p class="mt-3 text-2xl font-bold text-[#164b73]">
                    ₱{{ number_format((float) $project->budgetItems->sum('amount'), 2) }}
                </p>
                <p class="mt-1 text-xs text-slate-500">Detailed project budget line items</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Convergence</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($project->convergences->count()) }}</p>
                <p class="mt-1 text-xs text-slate-500">Recorded partner/program interventions</p>
            </x-ui.card>

            <x-ui.card>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Implementation</p>
                <div class="mt-3">
                    @if ($project->implementation)
                        <x-ui.badge :variant="$project->implementation->status->badgeVariant()">
                            {{ $project->implementation->status->label() }}
                        </x-ui.badge>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($project->implementation->accomplishment_percentage) }}%</p>
                    @else
                        <x-ui.badge>Not Started</x-ui.badge>
                        <p class="mt-2 text-lg font-bold text-slate-950">0%</p>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-500">Current implementation accomplishment</p>
            </x-ui.card>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <x-ui.card>
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="text-sm font-bold text-slate-900">Project Information</h3>
                    <p class="mt-1 text-xs text-slate-500">Core registry and primary location data.</p>
                </div>

                <dl class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Fund Source</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-700">{{ $project->fundSource?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Office</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-700">{{ $project->office?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Implementation Mode</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-700">{{ $project->implementationMode?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Date Received</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-700">{{ $project->date_received?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Primary Location</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-700">
                            @if ($project->primaryLocation)
                                {{ collect([
                                    $project->primaryLocation->barangay?->name,
                                    $project->primaryLocation->municipality?->name,
                                    $project->primaryLocation->province?->name,
                                ])->filter()->implode(', ') }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="text-sm font-bold text-slate-900">Financial Snapshot</h3>
                    <p class="mt-1 text-xs text-slate-500">Saved aggregate financial details.</p>
                </div>

                <div class="mt-5 space-y-3">
                    @php
                        $financial = $project->financial;
                    @endphp

                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                        <span class="text-xs font-semibold text-slate-600">DOLE Share</span>
                        <span class="text-sm font-bold text-slate-900">₱{{ number_format($financial?->doleShare() ?? 0, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                        <span class="text-xs font-semibold text-slate-600">Total Equity</span>
                        <span class="text-sm font-bold text-slate-900">₱{{ number_format($financial?->totalEquity() ?? 0, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                        <span class="text-xs font-bold text-[#164b73]">Total Project Cost</span>
                        <span class="text-sm font-bold text-[#164b73]">₱{{ number_format($financial?->totalProjectCost() ?? 0, 2) }}</span>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection
