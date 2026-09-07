<div class="space-y-7">
    <x-ui.page-header
        title="Data Quality"
        eyebrow="Production Hardening"
        description="Identify incomplete or inconsistent records that can reduce the reliability of DILP monitoring and official reports."
    />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($summary as $key => $check)
            <button type="button" wire:click="selectCheck('{{ $key }}')" @class([
                'rounded-xl border bg-white p-5 text-left shadow-sm transition hover:border-[#1e5a8a]/40',
                'border-[#1e5a8a] ring-2 ring-blue-100' => $selectedCheck === $key,
                'border-slate-200' => $selectedCheck !== $key,
            ])>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ $check['label'] }}</p>
                <p @class([
                    'mt-3 text-2xl font-bold',
                    'text-red-700' => $check['severity'] === 'danger' && $check['count'] > 0,
                    'text-amber-700' => $check['severity'] === 'warning' && $check['count'] > 0,
                    'text-emerald-700' => $check['count'] === 0,
                    'text-slate-950' => $check['count'] > 0 && !in_array($check['severity'], ['danger','warning'], true),
                ])>{{ number_format($check['count']) }}</p>
                <p class="mt-2 text-[11px] leading-5 text-slate-500">{{ $check['description'] }}</p>
            </button>
        @endforeach
    </div>

    <x-ui.card :padding="false">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 class="text-sm font-bold text-slate-900">{{ $current['label'] }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $current['description'] }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Project</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Office</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Fund Source</th>
                    <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Action</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($projects as $project)
                        <tr class="hover:bg-slate-50/70">
                            <td class="min-w-[300px] px-5 py-4 sm:px-6"><p class="font-mono text-[11px] font-semibold text-[#164b73]">{{ $project->registry_number }}</p><p class="mt-1 text-sm font-bold text-slate-900">{{ $project->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $project->proponent?->name ?? '—' }}</p></td>
                            <td class="px-5 py-4 text-xs font-semibold text-slate-700">{{ $project->office?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-xs text-slate-600">{{ $project->fundSource?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-right sm:px-6"><a href="{{ route('projects.show', $project) }}" class="text-xs font-bold text-[#164b73] hover:text-[#0f2a44]">Open Project</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-emerald-700 sm:px-6">No projects currently fail this quality check.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
