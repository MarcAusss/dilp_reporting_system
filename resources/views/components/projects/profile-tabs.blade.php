@props(['project'])

@php
    $tabs = [
        [
            'label' => 'Overview',
            'route' => 'projects.show',
            'permission' => 'projects.view',
        ],
        [
            'label' => 'Financials',
            'route' => 'projects.financials',
            'permission' => 'project-financials.view',
        ],
        [
            'label' => 'Beneficiaries',
            'route' => 'projects.beneficiaries',
            'permission' => 'project-beneficiaries.view',
        ],
        [
            'label' => 'Livelihoods',
            'route' => 'projects.livelihoods',
            'permission' => 'project-livelihoods.view',
        ],
        [
            'label' => 'Budget Items',
            'route' => 'projects.budget-items',
            'permission' => 'project-budget-items.view',
        ],
        [
            'label' => 'Convergence',
            'route' => 'projects.convergence',
            'permission' => 'project-convergence.view',
        ],
        [
            'label' => 'Workflow',
            'route' => 'projects.workflow',
            'permission' => 'project-workflow.view',
        ],
        [
            'label' => 'Implementation Processing',
            'route' => 'projects.processing',
            'permission' => 'project-processing.view',
        ],
    ];
@endphp

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <nav class="flex min-w-max items-center gap-1 p-2" aria-label="Project profile sections">
        @foreach ($tabs as $tab)
            @can($tab['permission'])
                @php
                    $active = request()->routeIs($tab['route']);
                @endphp

                <a href="{{ route($tab['route'], $project) }}"
                    @class([
                        'rounded-lg px-3.5 py-2 text-xs font-semibold transition',
                        'bg-[#0f2a44] text-white shadow-sm' => $active,
                        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
                    ])>
                    {{ $tab['label'] }}
                </a>
            @endcan
        @endforeach
    </nav>
</div>
