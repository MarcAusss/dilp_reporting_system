<div class="space-y-6">
    @if (session()->has('processing-status'))
        <x-ui.alert variant="success" title="Project Processing">
            {{ session('processing-status') }}
        </x-ui.alert>
    @endif

    @error('processing')
        <x-ui.alert variant="danger" title="Processing Locked">
            {{ $message }}
        </x-ui.alert>
    @enderror

    <x-projects.identity-card :project="$project" />

    @if (! $isUnlocked)
        <x-ui.alert variant="warning" title="Awaiting Project Approval">
            Financial and implementation records are read-only until the project workflow reaches Completed / Approved status.
        </x-ui.alert>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">DOLE Share</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">₱{{ number_format($summary['dole_share'], 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">Saved Phase 3 financial allocation</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Obligated</p>
            <p class="mt-3 text-2xl font-bold text-[#164b73]">₱{{ number_format($summary['obligated'], 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">Remaining ₱{{ number_format($summary['remaining_unobligated'], 2) }}</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Paid / Disbursed</p>
            <p class="mt-3 text-2xl font-bold text-emerald-700">₱{{ number_format($summary['disbursed'], 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">Undisbursed obligation ₱{{ number_format($summary['undisbursed_obligation'], 2) }}</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Fund Utilization</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($summary['utilization_percentage'], 2) }}%</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-[#164b73]" style="width: {{ min(100, max(0, $summary['utilization_percentage'])) }}%"></div>
            </div>
        </x-ui.card>
    </div>

    <x-ui.card :padding="false">
        <div class="overflow-x-auto border-b border-slate-100 p-2">
            <nav class="flex min-w-max items-center gap-1" aria-label="Processing sections">
                @foreach ([
                    'procurement' => 'Procurement',
                    'obligation' => 'Obligation',
                    'disbursement' => 'Payment / Disbursement',
                    'insurance' => 'Insurance',
                    'implementation' => 'Implementation',
                    'replacement' => 'Replacement Requests',
                ] as $section => $label)
                    <button
                        type="button"
                        wire:click="selectSection('{{ $section }}')"
                        @class([
                            'rounded-lg px-3.5 py-2 text-xs font-semibold transition',
                            'bg-[#0f2a44] text-white shadow-sm' => $activeSection === $section,
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => $activeSection !== $section,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="grid gap-4 border-b border-slate-100 bg-slate-50/70 px-5 py-4 sm:grid-cols-3 sm:px-6">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Procurement Total</p>
                <p class="mt-1 text-sm font-bold text-slate-800">₱{{ number_format($summary['procurement_total'], 2) }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Insurance Premium</p>
                <p class="mt-1 text-sm font-bold text-slate-800">₱{{ number_format($summary['insurance_premium'], 2) }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Approved / Completed Replacements</p>
                <p class="mt-1 text-sm font-bold text-slate-800">₱{{ number_format($summary['replacement_total'], 2) }}</p>
            </div>
        </div>
    </x-ui.card>

    @if ($activeSection === 'procurement')
        <div class="space-y-4">
            <x-ui.card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Procurement Records</h3>
                        <p class="mt-1 text-xs text-slate-500">Track procurement references, suppliers, amounts, and delivery status.</p>
                    </div>
                    @can('project-processing.update')
                        <x-ui.button type="button" wire:click="startProcurement" :disabled="! $isUnlocked">Add Procurement</x-ui.button>
                    @endcan
                </div>
            </x-ui.card>

            @if ($showProcurementForm)
                <x-ui.card>
                    <form wire:submit="saveProcurement" class="space-y-5">
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Reference No.</label>
                                <input type="text" wire:model="procurement_reference_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            </div>
                            <div class="xl:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Description *</label>
                                <input type="text" wire:model="procurement_description" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                @error('procurement_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Status *</label>
                                <select wire:model="procurement_status" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                    @foreach ($procurementStatuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Supplier</label>
                                <input type="text" wire:model="procurement_supplier" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Procurement Method</label>
                                <input type="text" wire:model="procurement_method" placeholder="Regular / EPA / other method" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            </div>
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" wire:model="procurement_is_epa">
                                <span>EPA Procurement</span>
                            </label>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Procurement Date</label>
                                <input type="date" wire:model="procurement_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Amount *</label>
                                <input type="number" min="0" step="0.01" wire:model="procurement_amount" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                @error('procurement_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Remarks</label>
                            <textarea rows="3" wire:model="procurement_remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                            <x-ui.button type="button" variant="secondary" wire:click="cancelProcurement">Cancel</x-ui.button>
                            <x-ui.button type="submit">Save Procurement</x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50"><tr>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Reference / Description</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Supplier / Date</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Amount</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($project->procurements->sortByDesc('id') as $record)
                                <tr>
                                    <td class="px-5 py-4 text-xs text-slate-600"><p class="font-semibold text-slate-800">{{ $record->description }}</p><p class="mt-1">{{ $record->reference_number ?: '—' }}</p></td>
                                    <td class="px-5 py-4 text-xs text-slate-600"><p>{{ $record->supplier ?: '—' }}</p><p class="mt-1 text-slate-400">{{ $record->procurement_date?->format('M d, Y') ?? '—' }}</p><p class="mt-1 text-[11px] text-slate-400">{{ $record->procurement_method ?: 'Method not recorded' }}{{ $record->is_epa ? ' · EPA' : '' }}</p></td>
                                    <td class="px-5 py-4"><x-ui.badge :variant="$record->status->badgeVariant()">{{ $record->status->label() }}</x-ui.badge></td>
                                    <td class="px-5 py-4 text-right text-sm font-semibold text-slate-800">₱{{ number_format((float) $record->amount, 2) }}</td>
                                    <td class="px-5 py-4 text-right">@can('project-processing.update')<button type="button" wire:click="editProcurement({{ $record->id }})" @disabled(! $isUnlocked) class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 disabled:opacity-40">Edit</button>@endcan</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">No procurement records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    @endif

    @if ($activeSection === 'obligation')
        <div class="space-y-4">
            <x-ui.card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><h3 class="text-sm font-bold text-slate-900">Obligation Records</h3><p class="mt-1 text-xs text-slate-500">Track obligated amounts against the approved DOLE share.</p></div>
                    @can('project-processing.update')<x-ui.button type="button" wire:click="startObligation" :disabled="! $isUnlocked">Add Obligation</x-ui.button>@endcan
                </div>
            </x-ui.card>
            @if ($showObligationForm)
                <x-ui.card>
                    <form wire:submit="saveObligation" class="space-y-5">
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <div><label class="block text-sm font-medium text-slate-700">Obligation No.</label><input type="text" wire:model="obligation_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                            <div><label class="block text-sm font-medium text-slate-700">Date</label><input type="date" wire:model="obligation_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                            <div><label class="block text-sm font-medium text-slate-700">Amount *</label><input type="number" min="0.01" step="0.01" wire:model="obligation_amount" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@error('obligation_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="block text-sm font-medium text-slate-700">Status *</label><select wire:model="obligation_status" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@foreach ($obligationStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></div>
                        </div>
                        <div><label class="block text-sm font-medium text-slate-700">Remarks</label><textarea rows="3" wire:model="obligation_remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea></div>
                        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><x-ui.button type="button" variant="secondary" wire:click="cancelObligation">Cancel</x-ui.button><x-ui.button type="submit">Save Obligation</x-ui.button></div>
                    </form>
                </x-ui.card>
            @endif
            <x-ui.card :padding="false"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Obligation No.</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Date</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Status</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">Amount</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($project->obligations->sortByDesc('id') as $record)<tr><td class="px-5 py-4 text-xs font-semibold text-slate-700">{{ $record->obligation_number ?: '—' }}</td><td class="px-5 py-4 text-xs text-slate-600">{{ $record->obligation_date?->format('M d, Y') ?? '—' }}</td><td class="px-5 py-4"><x-ui.badge :variant="$record->status->badgeVariant()">{{ $record->status->label() }}</x-ui.badge></td><td class="px-5 py-4 text-right text-sm font-semibold text-slate-800">₱{{ number_format((float) $record->amount, 2) }}</td><td class="px-5 py-4 text-right">@can('project-processing.update')<button type="button" wire:click="editObligation({{ $record->id }})" @disabled(! $isUnlocked) class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 disabled:opacity-40">Edit</button>@endcan</td></tr>@empty<tr><td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">No obligation records yet.</td></tr>@endforelse
            </tbody></table></div></x-ui.card>
        </div>
    @endif

    @if ($activeSection === 'disbursement')
        <div class="space-y-4">
            <x-ui.card><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-bold text-slate-900">Payment / Disbursement Records</h3><p class="mt-1 text-xs text-slate-500">Paid records count toward fund utilization and cannot exceed obligated funds.</p></div>@can('project-processing.update')<x-ui.button type="button" wire:click="startDisbursement" :disabled="! $isUnlocked">Add Disbursement</x-ui.button>@endcan</div></x-ui.card>
            @if ($showDisbursementForm)
                <x-ui.card><form wire:submit="saveDisbursement" class="space-y-5"><div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div><label class="block text-sm font-medium text-slate-700">Disbursement No.</label><input type="text" wire:model="disbursement_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Payment Reference</label><input type="text" wire:model="payment_reference" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Payee</label><input type="text" wire:model="payee" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Date Prepared</label><input type="date" wire:model="payment_prepared_at" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Forwarded to Signatories</label><input type="date" wire:model="payment_forwarded_to_signatories_at" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Date/Time Released</label><input type="datetime-local" wire:model="payment_released_at" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Check / LDDAP-ADA No.</label><input type="text" wire:model="check_lddap_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Check / LDDAP-ADA Date</label><input type="date" wire:model="check_lddap_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">DV Number</label><input type="text" wire:model="payment_dv_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Date</label><input type="date" wire:model="disbursement_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Amount *</label><input type="number" min="0.01" step="0.01" wire:model="disbursement_amount" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@error('disbursement_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-slate-700">Status *</label><select wire:model="disbursement_status" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@foreach ($disbursementStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></div>
                </div><div><label class="block text-sm font-medium text-slate-700">Remarks</label><textarea rows="3" wire:model="disbursement_remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea></div><div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><x-ui.button type="button" variant="secondary" wire:click="cancelDisbursement">Cancel</x-ui.button><x-ui.button type="submit">Save Disbursement</x-ui.button></div></form></x-ui.card>
            @endif
            <x-ui.card :padding="false"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Reference</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Payee / Date</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Status</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">Amount</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($project->disbursements->sortByDesc('id') as $record)<tr><td class="px-5 py-4 text-xs text-slate-600"><p class="font-semibold text-slate-800">{{ $record->disbursement_number ?: '—' }}</p><p class="mt-1">{{ $record->payment_reference ?: '—' }}</p><p class="mt-1 text-[11px] text-slate-400">Check/LDDAP: {{ $record->check_lddap_number ?: '—' }} · DV: {{ $record->dv_number ?: '—' }}</p></td><td class="px-5 py-4 text-xs text-slate-600"><p>{{ $record->payee ?: '—' }}</p><p class="mt-1 text-slate-400">{{ $record->disbursement_date?->format('M d, Y') ?? '—' }}</p></td><td class="px-5 py-4"><x-ui.badge :variant="$record->status->badgeVariant()">{{ $record->status->label() }}</x-ui.badge></td><td class="px-5 py-4 text-right text-sm font-semibold text-slate-800">₱{{ number_format((float) $record->amount, 2) }}</td><td class="px-5 py-4 text-right">@can('project-processing.update')<button type="button" wire:click="editDisbursement({{ $record->id }})" @disabled(! $isUnlocked) class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 disabled:opacity-40">Edit</button>@endcan</td></tr>@empty<tr><td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">No disbursement records yet.</td></tr>@endforelse
            </tbody></table></div></x-ui.card>
        </div>
    @endif

    @if ($activeSection === 'insurance')
        <div class="space-y-4">
            <x-ui.card><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-bold text-slate-900">Insurance Records</h3><p class="mt-1 text-xs text-slate-500">Record policy coverage, premium, coverage amount, and validity.</p></div>@can('project-processing.update')<x-ui.button type="button" wire:click="startInsurance" :disabled="! $isUnlocked">Add Insurance</x-ui.button>@endcan</div></x-ui.card>
            @if ($showInsuranceForm)
                <x-ui.card><form wire:submit="saveInsurance" class="space-y-5"><div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div><label class="block text-sm font-medium text-slate-700">Provider</label><input type="text" wire:model="insurance_provider" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Policy No.</label><input type="text" wire:model="policy_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Coverage Start</label><input type="date" wire:model="coverage_start" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Coverage End</label><input type="date" wire:model="coverage_end" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@error('coverage_end')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-slate-700">Premium Amount *</label><input type="number" min="0" step="0.01" wire:model="premium_amount" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Covered Amount *</label><input type="number" min="0" step="0.01" wire:model="covered_amount" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Status *</label><select wire:model="insurance_status" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@foreach ($insuranceStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></div>
                </div><div><label class="block text-sm font-medium text-slate-700">Remarks</label><textarea rows="3" wire:model="insurance_remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea></div><div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><x-ui.button type="button" variant="secondary" wire:click="cancelInsurance">Cancel</x-ui.button><x-ui.button type="submit">Save Insurance</x-ui.button></div></form></x-ui.card>
            @endif
            <x-ui.card :padding="false"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Provider / Policy</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Coverage</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Status</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">Premium / Covered</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($project->insurances->sortByDesc('id') as $record)<tr><td class="px-5 py-4 text-xs text-slate-600"><p class="font-semibold text-slate-800">{{ $record->provider ?: '—' }}</p><p class="mt-1">{{ $record->policy_number ?: '—' }}</p></td><td class="px-5 py-4 text-xs text-slate-600">{{ $record->coverage_start?->format('M d, Y') ?? '—' }} – {{ $record->coverage_end?->format('M d, Y') ?? '—' }}</td><td class="px-5 py-4"><x-ui.badge :variant="$record->status->badgeVariant()">{{ $record->status->label() }}</x-ui.badge></td><td class="px-5 py-4 text-right text-xs text-slate-600"><p class="font-semibold text-slate-800">₱{{ number_format((float) $record->premium_amount, 2) }}</p><p class="mt-1">Covered: ₱{{ number_format((float) $record->covered_amount, 2) }}</p></td><td class="px-5 py-4 text-right">@can('project-processing.update')<button type="button" wire:click="editInsurance({{ $record->id }})" @disabled(! $isUnlocked) class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 disabled:opacity-40">Edit</button>@endcan</td></tr>@empty<tr><td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">No insurance records yet.</td></tr>@endforelse
            </tbody></table></div></x-ui.card>
        </div>
    @endif

    @if ($activeSection === 'implementation')
        <x-ui.card>
            <div class="border-b border-slate-100 pb-4"><h3 class="text-sm font-bold text-slate-900">Implementation Progress</h3><p class="mt-1 text-xs text-slate-500">Maintain one authoritative implementation status and accomplishment percentage for the project.</p></div>
            <form wire:submit="saveImplementation" class="mt-5 space-y-5">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div><label class="block text-sm font-medium text-slate-700">Start Date</label><input type="date" wire:model="implementation_start_date" @disabled(! $isUnlocked) class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm disabled:bg-slate-50"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Target Completion</label><input type="date" wire:model="target_completion_date" @disabled(! $isUnlocked) class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm disabled:bg-slate-50">@error('target_completion_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-slate-700">Actual Completion</label><input type="date" wire:model="actual_completion_date" @disabled(! $isUnlocked) class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm disabled:bg-slate-50">@error('actual_completion_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-slate-700">Status *</label><select wire:model="implementation_status" @disabled(! $isUnlocked) class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm disabled:bg-slate-50">@foreach ($implementationStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-slate-700">Accomplishment % *</label><input type="number" min="0" max="100" step="1" wire:model="accomplishment_percentage" @disabled(! $isUnlocked) class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm disabled:bg-slate-50">@error('accomplishment_percentage')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                <div><label class="block text-sm font-medium text-slate-700">Implementation Remarks</label><textarea rows="4" wire:model="implementation_remarks" @disabled(! $isUnlocked) class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm disabled:bg-slate-50"></textarea></div>
                @can('project-processing.update')<div class="flex justify-end border-t border-slate-100 pt-5"><x-ui.button type="submit" :disabled="! $isUnlocked">Save Implementation Progress</x-ui.button></div>@endcan
            </form>
        </x-ui.card>
    @endif

    @if ($activeSection === 'replacement')
        <div class="space-y-4">
            <x-ui.card><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-bold text-slate-900">Replacement Requests</h3><p class="mt-1 text-xs text-slate-500">Track requests to replace damaged, defective, or unsuitable livelihood items.</p></div>@can('project-processing.update')<x-ui.button type="button" wire:click="startReplacement" :disabled="! $isUnlocked">Add Replacement Request</x-ui.button>@endcan</div></x-ui.card>
            @if ($showReplacementForm)
                <x-ui.card><form wire:submit="saveReplacement" class="space-y-5"><div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div><label class="block text-sm font-medium text-slate-700">Reference No.</label><input type="text" wire:model="replacement_reference_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div class="xl:col-span-2"><label class="block text-sm font-medium text-slate-700">Item Description *</label><input type="text" wire:model="replacement_item_description" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@error('replacement_item_description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-slate-700">Status *</label><select wire:model="replacement_status" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@foreach ($replacementStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-slate-700">Requested Amount *</label><input type="number" min="0" step="0.01" wire:model="requested_amount" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Request Date</label><input type="date" wire:model="request_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700">Resolution Date</label><input type="date" wire:model="resolution_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">@error('resolution_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                </div><div><label class="block text-sm font-medium text-slate-700">Reason *</label><textarea rows="3" wire:model="replacement_reason" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>@error('replacement_reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"><div><label class="block text-sm font-medium text-slate-700">No. of Beneficiaries Replaced</label><input type="number" min="0" wire:model="replacement_beneficiaries_replaced" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div><div><label class="block text-sm font-medium text-slate-700">Approval Letter Date</label><input type="date" wire:model="replacement_approval_letter_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div><div><label class="block text-sm font-medium text-slate-700">Date/Time Released</label><input type="datetime-local" wire:model="replacement_released_at" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div><div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700">Original Beneficiary</label><textarea rows="2" wire:model="replacement_original_beneficiary" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea></div><div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700">Replacement Beneficiary</label><textarea rows="2" wire:model="replacement_new_beneficiary" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea></div><div><label class="block text-sm font-medium text-slate-700">ARE / Insurance Received</label><input type="date" wire:model="replacement_are_insurance_received_at" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></div></div><div><label class="block text-sm font-medium text-slate-700">Remarks</label><textarea rows="3" wire:model="replacement_remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea></div><div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><x-ui.button type="button" variant="secondary" wire:click="cancelReplacement">Cancel</x-ui.button><x-ui.button type="submit">Save Replacement Request</x-ui.button></div></form></x-ui.card>
            @endif
            <x-ui.card :padding="false"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Reference / Item</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Request / Resolution</th><th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">Status</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">Amount</th><th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($project->replacementRequests->sortByDesc('id') as $record)<tr><td class="px-5 py-4 text-xs text-slate-600"><p class="font-semibold text-slate-800">{{ $record->item_description }}</p><p class="mt-1">{{ $record->reference_number ?: '—' }}</p></td><td class="px-5 py-4 text-xs text-slate-600"><p>{{ $record->request_date?->format('M d, Y') ?? '—' }}</p><p class="mt-1 text-slate-400">Resolved: {{ $record->resolution_date?->format('M d, Y') ?? '—' }}</p></td><td class="px-5 py-4"><x-ui.badge :variant="$record->status->badgeVariant()">{{ $record->status->label() }}</x-ui.badge></td><td class="px-5 py-4 text-right text-sm font-semibold text-slate-800">₱{{ number_format((float) $record->requested_amount, 2) }}</td><td class="px-5 py-4 text-right">@can('project-processing.update')<button type="button" wire:click="editReplacement({{ $record->id }})" @disabled(! $isUnlocked) class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 disabled:opacity-40">Edit</button>@endcan</td></tr>@empty<tr><td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">No replacement requests yet.</td></tr>@endforelse
            </tbody></table></div></x-ui.card>
        </div>
    @endif
</div>
