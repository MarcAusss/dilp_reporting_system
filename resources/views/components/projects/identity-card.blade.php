@props(['project'])

<x-ui.card>
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto]">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-xs font-semibold text-[#164b73]">
                    {{ $project->registry_number }}
                </span>

                <x-ui.badge>
                    FY {{ $project->fiscal_year }}
                </x-ui.badge>

                @if ($project->project_code)
                    <x-ui.badge variant="info">
                        {{ $project->project_code }}
                    </x-ui.badge>
                @endif
            </div>

            <h2 class="mt-3 text-xl font-bold tracking-tight text-slate-950">
                {{ $project->title }}
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                {{ $project->proponent->name }}
            </p>
        </div>

        <div class="grid gap-1 text-left lg:text-right">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Classification
            </span>

            <span class="text-sm font-semibold text-slate-700">
                {{ $project->projectType->name }} / {{ $project->projectPurpose->name }}
            </span>
        </div>
    </div>
</x-ui.card>
