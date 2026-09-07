<div class="space-y-6">
    @if (session()->has('workflow-status'))
        <x-ui.alert variant="success" title="Project Workflow">
            {{ session('workflow-status') }}
        </x-ui.alert>
    @endif

    @error('workflow')
        <x-ui.alert variant="danger" title="Workflow Action">
            {{ $message }}
        </x-ui.alert>
    @enderror

    <x-projects.identity-card :project="$project" />

    <x-ui.card>
        <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#1e5a8a]">
                    Current Processing State
                </p>
                <h3 class="mt-1 text-lg font-bold text-slate-950">
                    {{ $state->stage->label() }}
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    Last action {{ $state->last_action_at?->format('M d, Y h:i A') ?? '—' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$state->status->badgeVariant()">
                    {{ $state->status->label() }}
                </x-ui.badge>

                @if ($state->assigned_role)
                    <x-ui.badge variant="info">
                        Assigned: {{ \App\Enums\UserRole::tryFrom($state->assigned_role)?->label() ?? $state->assigned_role }}
                    </x-ui.badge>
                @else
                    <x-ui.badge>
                        No active assignment
                    </x-ui.badge>
                @endif
            </div>
        </div>

        @php
            $currentStageIndex = collect($stages)->search(
                fn ($stage) => $stage === $state->stage
            );
        @endphp

        <div class="mt-6 overflow-x-auto">
            <div class="grid min-w-[760px] grid-cols-5 gap-3">
                @foreach ($stages as $index => $stage)
                    @php
                        $isCurrent = $stage === $state->stage;
                        $isPast = is_int($currentStageIndex) && $index < $currentStageIndex;
                    @endphp

                    <div @class([
                        'rounded-xl border px-4 py-4',
                        'border-[#0f2a44] bg-[#0f2a44] text-white' => $isCurrent,
                        'border-emerald-200 bg-emerald-50 text-emerald-800' => $isPast,
                        'border-slate-200 bg-slate-50 text-slate-500' => ! $isCurrent && ! $isPast,
                    ])>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold',
                                'bg-white/15 text-white' => $isCurrent,
                                'bg-emerald-100 text-emerald-700' => $isPast,
                                'bg-white text-slate-500 ring-1 ring-slate-200' => ! $isCurrent && ! $isPast,
                            ])>
                                {{ $index + 1 }}
                            </span>
                            <span class="text-xs font-bold">
                                {{ $stage->label() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.card>

    <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <x-ui.card>
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900">Workflow Action</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Actions are restricted to the role currently assigned to this project. Super Admin retains override access.
                </p>
            </div>

            <div class="mt-5 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Reference Number
                    </label>
                    <input type="text" wire:model="reference_number" maxlength="150"
                        placeholder="Optional endorsement, memo, or tracking reference"
                        class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                    @error('reference_number')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Remarks
                    </label>
                    <textarea rows="5" wire:model="remarks" maxlength="3000"
                        placeholder="Record action notes, findings, reasons for return/rejection, or other processing remarks..."
                        class="mt-2 block w-full resize-y rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100"></textarea>
                    @error('remarks')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @can('project-workflow.update')
                    @if (count($availableActionValues))
                        <div class="grid gap-2 sm:grid-cols-2">
                            @if (in_array(\App\Enums\ProjectWorkflowAction::Started->value, $availableActionValues, true))
                                <x-ui.button wire:click="startProcessing">
                                    Start Processing
                                </x-ui.button>
                            @endif

                            @if (in_array(\App\Enums\ProjectWorkflowAction::Advanced->value, $availableActionValues, true))
                                <x-ui.button wire:click="advance">
                                    Submit to {{ $state->stage->next()?->label() }}
                                </x-ui.button>
                            @endif

                            @if (in_array(\App\Enums\ProjectWorkflowAction::Approved->value, $availableActionValues, true))
                                <x-ui.button wire:click="approve">
                                    Approve Project
                                </x-ui.button>
                            @endif

                            @if (in_array(\App\Enums\ProjectWorkflowAction::Note->value, $availableActionValues, true))
                                <x-ui.button variant="secondary" wire:click="addNote">
                                    Record Note
                                </x-ui.button>
                            @endif

                            @if (in_array(\App\Enums\ProjectWorkflowAction::Returned->value, $availableActionValues, true))
                                <x-ui.button variant="secondary" wire:click="returnForCorrection"
                                    wire:confirm="Return this project to Evaluation for correction?">
                                    Return for Correction
                                </x-ui.button>
                            @endif

                            @if (in_array(\App\Enums\ProjectWorkflowAction::Rejected->value, $availableActionValues, true))
                                <x-ui.button variant="danger" wire:click="reject"
                                    wire:confirm="Reject this project workflow? This ends the current workflow.">
                                    Reject Project
                                </x-ui.button>
                            @endif
                        </div>
                    @elseif ($state->status->isTerminal())
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                            This workflow is {{ strtolower($state->status->label()) }} and no longer accepts processing actions.
                        </div>
                    @else
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            This item is assigned to another workflow role. You can review its history but cannot act on it.
                        </div>
                    @endif
                @else
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                        Your account has read-only access to project workflow information.
                    </div>
                @endcan
            </div>
        </x-ui.card>

        <x-ui.card :padding="false">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="text-sm font-bold text-slate-900">Workflow History</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Immutable processing trail for this project.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Date / Actor</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Action</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">State</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Reference / Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($events as $event)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600 sm:px-6">
                                    <p class="font-semibold text-slate-800">
                                        {{ $event->acted_at?->format('M d, Y') ?? '—' }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-500">
                                        {{ $event->acted_at?->format('h:i A') ?? '' }}
                                    </p>
                                    <p class="mt-1 text-[11px] font-medium text-slate-500">
                                        {{ $event->actor?->name ?? 'System' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-xs font-bold text-slate-800">
                                        {{ $event->action->label() }}
                                    </p>
                                    @if ($event->assigned_role)
                                        <p class="mt-1 text-[11px] text-slate-500">
                                            Assigned to {{ \App\Enums\UserRole::tryFrom($event->assigned_role)?->label() ?? $event->assigned_role }}
                                        </p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ $event->to_stage->label() }}
                                    </p>
                                    <div class="mt-1">
                                        <x-ui.badge :variant="$event->to_status->badgeVariant()">
                                            {{ $event->to_status->label() }}
                                        </x-ui.badge>
                                    </div>
                                </td>
                                <td class="min-w-[260px] px-5 py-4 text-xs leading-5 text-slate-600 sm:px-6">
                                    @if ($event->reference_number)
                                        <p class="font-semibold text-slate-700">
                                            Ref: {{ $event->reference_number }}
                                        </p>
                                    @endif
                                    <p @class(['mt-1' => $event->reference_number])>
                                        {{ $event->remarks ?: '—' }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500 sm:px-6">
                                    No workflow events recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($events->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    {{ $events->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</div>
