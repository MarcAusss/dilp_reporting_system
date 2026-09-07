<div class="space-y-7">
    <x-ui.page-header title="Work Queues" eyebrow="Project Processing"
        description="Projects currently assigned for DILP evaluation, endorsement, validation, or approval.">
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('projects.index') }}">
                Project Registry
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Active Queue</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($summary['active']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Non-terminal workflow items</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Pending</p>
            <p class="mt-3 text-2xl font-bold text-amber-700">{{ number_format($summary['pending']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Waiting to be started</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">In Progress</p>
            <p class="mt-3 text-2xl font-bold text-[#164b73]">{{ number_format($summary['in_progress']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Currently being processed</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Returned</p>
            <p class="mt-3 text-2xl font-bold text-red-700">{{ number_format($summary['returned']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Requires correction or follow-up</p>
        </x-ui.card>
    </div>

    <x-ui.card :padding="false">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">
                        {{ $isSuperAdmin ? 'All Workflow Queues' : 'Assigned to My Role' }}
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Queue visibility follows the role assignment stored in each project's current workflow state.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:w-[720px]">
                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Search project or proponent..."
                        class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">

                    <select wire:model.live="stageFilter"
                        class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        <option value="">All stages</option>
                        @foreach ($stageOptions as $stage)
                            <option value="{{ $stage->value }}">{{ $stage->label() }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="statusFilter"
                        class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        <option value="active">Active only</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Project</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Office / Type</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Stage</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Status</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Assigned Role</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($projects as $project)
                        @php($state = $project->workflowState)
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="min-w-[300px] px-5 py-4 sm:px-6">
                                <p class="font-mono text-[11px] font-semibold text-[#164b73]">
                                    {{ $project->registry_number }}
                                </p>
                                <p class="mt-1 text-sm font-bold text-slate-900">
                                    {{ $project->title }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $project->proponent->name }}
                                </p>
                            </td>
                            <td class="min-w-[190px] px-5 py-4 text-xs text-slate-600 sm:px-6">
                                <p class="font-semibold text-slate-700">{{ $project->office?->name ?? '—' }}</p>
                                <p class="mt-1">{{ $project->projectType?->name ?? '—' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-slate-700 sm:px-6">
                                {{ $state?->stage?->label() ?? '—' }}
                                <p class="mt-1 text-[11px] font-normal text-slate-400">
                                    {{ $state?->last_action_at?->format('M d, Y h:i A') ?? '—' }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                @if ($state)
                                    <x-ui.badge :variant="$state->status->badgeVariant()">
                                        {{ $state->status->label() }}
                                    </x-ui.badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-slate-700 sm:px-6">
                                {{ $state?->assigned_role
                                    ? (\App\Enums\UserRole::tryFrom($state->assigned_role)?->label() ?? $state->assigned_role)
                                    : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right sm:px-6">
                                <a href="{{ route('projects.workflow', $project) }}"
                                    class="text-xs font-bold text-[#164b73] hover:text-[#0f2a44]">
                                    Open Workflow
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500 sm:px-6">
                                No workflow items match the current queue filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                {{ $projects->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
