<div class="space-y-7">
    <x-ui.page-header
        title="Monitoring & Compliance"
        eyebrow="DILP Monitoring"
        description="Regional monitoring workspace for accomplishment, open findings, due reports, and upcoming follow-up activity."
    />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Projects</p><p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($summary['projects']) }}</p><p class="mt-1 text-xs text-slate-500">Registered projects in monitoring scope</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Open Findings</p><p class="mt-3 text-2xl font-bold text-amber-700">{{ number_format($summary['with_open_findings']) }}</p><p class="mt-1 text-xs text-slate-500">Projects with unresolved findings</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Overdue Reports</p><p class="mt-3 text-2xl font-bold text-red-700">{{ number_format($summary['with_overdue_reports']) }}</p><p class="mt-1 text-xs text-slate-500">Projects with overdue compliance</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Monitoring Due</p><p class="mt-3 text-2xl font-bold text-[#164b73]">{{ number_format($summary['due_for_monitoring']) }}</p><p class="mt-1 text-xs text-slate-500">Follow-up due within 30 days</p></x-ui.card>
    </div>

    <x-ui.card :padding="false">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div><h2 class="text-sm font-bold text-slate-900">Project Monitoring Register</h2><p class="mt-1 text-xs text-slate-500">Use attention filters to identify projects requiring monitoring action.</p></div>
                <div class="grid gap-3 sm:grid-cols-2 lg:w-[620px]">
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search project or proponent..." class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                    <select wire:model.live="attention" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        <option value="all">All projects</option>
                        <option value="findings">With open findings</option>
                        <option value="overdue">With overdue reports</option>
                        <option value="monitoring_due">Monitoring due within 30 days</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr>
            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Project</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Office</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Accomplishment</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Monitoring</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Attention</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Action</th>
        </tr></thead><tbody class="divide-y divide-slate-100 bg-white">
            @forelse($projects as $project)<tr class="align-top hover:bg-slate-50/70">
                <td class="min-w-[300px] px-5 py-4 sm:px-6"><p class="font-mono text-[11px] font-semibold text-[#164b73]">{{ $project->registry_number }}</p><p class="mt-1 text-sm font-bold text-slate-900">{{ $project->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $project->proponent?->name ?? '—' }}</p></td>
                <td class="px-5 py-4 text-xs font-semibold text-slate-700">{{ $project->office?->name ?? '—' }}</td>
                <td class="px-5 py-4"><p class="text-sm font-bold text-[#164b73]">{{ $project->implementation?->accomplishment_percentage ?? 0 }}%</p><p class="mt-1 text-[11px] text-slate-400">{{ $project->implementation?->status?->label() ?? 'Not Started' }}</p></td>
                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600"><p>{{ number_format($project->monitoring_visits_count) }} visit(s)</p><p class="mt-1 text-[11px] text-slate-400">Next: {{ $project->next_monitoring_date ? \Illuminate\Support\Carbon::parse($project->next_monitoring_date)->format('M d, Y') : '—' }}</p></td>
                <td class="whitespace-nowrap px-5 py-4"><div class="flex flex-col items-start gap-1">@if($project->open_findings_count)<x-ui.badge variant="warning">{{ $project->open_findings_count }} open finding(s)</x-ui.badge>@endif @if($project->overdue_reports_count)<x-ui.badge variant="danger">{{ $project->overdue_reports_count }} overdue report(s)</x-ui.badge>@endif @if(!$project->open_findings_count && !$project->overdue_reports_count)<x-ui.badge variant="success">No active issues</x-ui.badge>@endif</div></td>
                <td class="whitespace-nowrap px-5 py-4 text-right sm:px-6"><a href="{{ route('projects.monitoring', $project) }}" class="text-xs font-bold text-[#164b73] hover:text-[#0f2a44]">Open Monitoring</a></td>
            </tr>@empty<tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500 sm:px-6">No projects match the current monitoring filters.</td></tr>@endforelse
        </tbody></table></div>
        @if($projects->hasPages())<div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $projects->links() }}</div>@endif
    </x-ui.card>
</div>
