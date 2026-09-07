<div class="space-y-6">
    @if (session()->has('spreadsheet-detail-status'))
        <x-ui.alert variant="success" title="Spreadsheet Details">{{ session('spreadsheet-detail-status') }}</x-ui.alert>
    @endif

    <x-projects.identity-card :project="$project" />

    <form wire:submit="saveCoreDetails" class="space-y-6">
        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Funding & Source Information</h3>
            <p class="mt-1 text-xs text-slate-500">ADL/NTA, sponsor, political/NGA source, charging and geographic targeting from the legacy workbook.</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ([
                    'adl_nta_number'=>'ADL / NTA Number','fund_sponsor'=>'Fund Sponsor','partylist_nga'=>'Party-list / NGA',
                    'legislator'=>'Congressman / Senator','lce_congressman_point_person'=>'LCE / Congressman / Point Person',
                    'district'=>'District','actual_charging'=>'Actual Charging','fund_amount'=>'Fund Amount'
                ] as $field=>$label)
                    <label class="block"><span class="text-xs font-semibold text-slate-600">{{ $label }}</span>
                        <input wire:model="funding.{{ $field }}" type="{{ $field === 'fund_amount' ? 'number' : 'text' }}" step="{{ $field === 'fund_amount' ? '0.01' : '1' }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('funding.'.$field)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                @endforeach
                <label class="block md:col-span-2"><span class="text-xs font-semibold text-slate-600">Target Municipalities</span><textarea wire:model="funding.target_municipalities" rows="2" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea></label>
                <label class="block md:col-span-2"><span class="text-xs font-semibold text-slate-600">Target Barangays</span><textarea wire:model="funding.target_barangays" rows="2" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea></label>
                <label class="block md:col-span-2 xl:col-span-3"><span class="text-xs font-semibold text-slate-600">Funding Remarks</span><textarea wire:model="funding.remarks" rows="2" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea></label>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Expanded Proponent Profile</h3>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @foreach (['abbreviation'=>'Abbreviation','head_name'=>'LCE / Head of Office','organization_name'=>'Association / Organization Name','organization_classification'=>'Organization Classification'] as $field=>$label)
                    <label class="block"><span class="text-xs font-semibold text-slate-600">{{ $label }}</span><input wire:model="proponent.{{ $field }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">DIS Validation & Coordination</h3>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <label class="block"><span class="text-xs font-semibold text-slate-600">Details Tally with DIS</span>
                    <select wire:model="dis.details_tally_with_dis" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Not recorded</option><option value="1">Yes</option><option value="0">No</option></select>
                </label>
                <label class="block lg:col-span-2"><span class="text-xs font-semibold text-slate-600">Proposal Status in DIS</span><input wire:model="dis.proposal_status" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                @foreach (['for_updating'=>'For Updating','for_coordination_po'=>'For Coordination to PO','for_coordination_co'=>'For Coordination with CO'] as $field=>$label)
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm"><input type="checkbox" wire:model="dis.{{ $field }}"><span>{{ $label }}</span></label>
                @endforeach
                @foreach (['action_required'=>'Action Required','resolution'=>'Resolution','remarks'=>'DIS Remarks'] as $field=>$label)
                    <label class="block lg:col-span-3"><span class="text-xs font-semibold text-slate-600">{{ $label }}</span><textarea wire:model="dis.{{ $field }}" rows="2" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea></label>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Legacy Beneficiary Metrics</h3>
            <p class="mt-1 text-xs text-slate-500">Preserves aggregate spreadsheet totals without inventing individual beneficiary identities.</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach (['total_beneficiaries'=>'Total Beneficiaries','female_beneficiaries'=>'Female Beneficiaries','male_beneficiaries'=>'Male Beneficiaries','female_assistance_amount'=>'Amount Granted to Female Beneficiaries','beneficiary_type_ies'=>'Beneficiary Type / IES'] as $field=>$label)
                    <label class="block"><span class="text-xs font-semibold text-slate-600">{{ $label }}</span><input wire:model="beneficiaryMetric.{{ $field }}" type="{{ in_array($field,['total_beneficiaries','female_beneficiaries','male_beneficiaries','female_assistance_amount']) ? 'number' : 'text' }}" step="{{ $field === 'female_assistance_amount' ? '0.01' : '1' }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('beneficiaryMetric.'.$field)<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                @endforeach
                <label class="block md:col-span-2 xl:col-span-5"><span class="text-xs font-semibold text-slate-600">Remarks</span><textarea wire:model="beneficiaryMetric.remarks" rows="2" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea></label>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">MOA & Original Folder Timeline</h3>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach (['received_at'=>'MOA Received','forwarded_for_signature_at'=>'Forwarded for Signature','signed_copy_returned_at'=>'Signed Copy Returned','notarial_slip_at'=>'Notarial Slip','forwarded_to_notarial_at'=>'Forwarded to Notarial Office','notarized_at'=>'Notarized','notarized_copy_received_at'=>'Notarized Copy Received','original_folder_forwarded_imsd_at'=>'Original Folder Forwarded to IMSD'] as $field=>$label)
                    <label class="block"><span class="text-xs font-semibold text-slate-600">{{ $label }}</span><input type="date" wire:model="moa.{{ $field }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                @endforeach
                @foreach (['lacking_requirements'=>'Lacking Documentary Requirements','commitment'=>'Commitment','important_note'=>'Important Note'] as $field=>$label)
                    <label class="block md:col-span-2 xl:col-span-4"><span class="text-xs font-semibold text-slate-600">{{ $label }}</span><textarea wire:model="moa.{{ $field }}" rows="2" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea></label>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Location Reporting Metadata</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label><span class="text-xs font-semibold text-slate-600">District</span><input wire:model="locationMetadata.district" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                <label><span class="text-xs font-semibold text-slate-600">Income Class</span><input wire:model="locationMetadata.income_class" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
            </div>
        </x-ui.card>

        @can('project-spreadsheet-details.update')
            <div class="flex justify-end"><x-ui.button type="submit">Save Core Spreadsheet Details</x-ui.button></div>
        @endcan
    </form>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Endorsements</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <input type="number" min="1" wire:model="endorsement.sequence_no" placeholder="Sequence" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="date" wire:model="endorsement.endorsement_date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input wire:model="endorsement.reference_number" placeholder="Reference number" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input wire:model="endorsement.endorsement_type" placeholder="Type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <textarea wire:model="endorsement.remarks" rows="2" placeholder="Remarks" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button wire:click="addEndorsement" type="button">Save Endorsement</x-ui.button>@endcan
            </div>
            <div class="mt-5 space-y-2">@forelse($endorsements as $item)<div class="flex items-start justify-between rounded-lg border border-slate-200 p-3 text-xs"><div><strong>#{{ $item->sequence_no }}</strong> · {{ $item->endorsement_date?->format('M d, Y') ?? 'No date' }} · {{ $item->reference_number ?: 'No reference' }}<p class="mt-1 text-slate-500">{{ $item->remarks }}</p></div>@can('project-spreadsheet-details.update')<button wire:click="deleteEndorsement({{ $item->id }})" wire:confirm="Delete this endorsement?" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No endorsements recorded.</p>@endforelse</div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">LCOM / Compliance Communications</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <input wire:model="communication.type" placeholder="Type (LCOM)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input wire:model="communication.reference_number" placeholder="Reference number" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="date" wire:model="communication.issued_at" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="date" wire:model="communication.received_at" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="date" wire:model="communication.resolved_at" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><select wire:model="communication.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="open">Open</option><option value="for_compliance">For Compliance</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select>
                <textarea wire:model="communication.remarks" rows="2" placeholder="Remarks" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button wire:click="addCommunication" type="button">Add Communication</x-ui.button>@endcan
            </div>
            <div class="mt-5 space-y-2">@forelse($communications as $item)<div class="flex justify-between rounded-lg border border-slate-200 p-3 text-xs"><div><strong>{{ $item->type }}</strong> · {{ $item->reference_number ?: 'No reference' }} · {{ ucfirst(str_replace('_',' ',$item->status)) }}<p class="mt-1 text-slate-500">{{ $item->remarks }}</p></div>@can('project-spreadsheet-details.update')<button wire:click="deleteCommunication({{ $item->id }})" wire:confirm="Delete this communication?" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No communications recorded.</p>@endforelse</div>
        </x-ui.card>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">GPAI / Micro-Insurance</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach (['beneficiaries_enrolled'=>'Beneficiaries Enrolled','gpai_amount'=>'GPAI Amount','enrollment_status'=>'Enrollment Status','cash_advance_payee'=>'Cash Advance Payee','responsible_person'=>'Responsible Person','or_number'=>'OR Number','policy_number'=>'Policy Number','dv_number'=>'DV Number','check_number'=>'Check Number'] as $field=>$label)
                    <input wire:model="gpai.{{ $field }}" type="{{ in_array($field,['beneficiaries_enrolled','gpai_amount']) ? 'number':'text' }}" step="{{ $field==='gpai_amount'?'0.01':'1' }}" placeholder="{{ $label }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @endforeach
                <select wire:model="gpai.fund_source_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Fund Source</option>@foreach($fundSources as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select>
                @foreach (['forwarded_for_enrollment_at'=>'Forwarded for Enrollment','or_policy_received_at'=>'OR/Policy Received','or_date'=>'OR Date','check_date'=>'Check Date'] as $field=>$label)<label class="text-xs text-slate-500">{{ $label }}<input type="date" wire:model="gpai.{{ $field }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>@endforeach
                <textarea wire:model="gpai.remarks" rows="2" placeholder="Remarks" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button wire:click="addGpai" type="button">Add GPAI Record</x-ui.button>@endcan
            </div>
            <div class="mt-5 space-y-2">@forelse($gpaiRecords as $item)<div class="flex justify-between rounded-lg border border-slate-200 p-3 text-xs"><div><strong>{{ $item->enrollment_status ?: 'GPAI Record' }}</strong> · {{ number_format((int)$item->beneficiaries_enrolled) }} beneficiaries · ₱{{ number_format((float)$item->gpai_amount,2) }}<p class="mt-1 text-slate-500">Policy {{ $item->policy_number ?: '—' }} / OR {{ $item->or_number ?: '—' }}</p></div>@can('project-spreadsheet-details.update')<button wire:click="deleteGpai({{ $item->id }})" wire:confirm="Delete this GPAI record?" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No GPAI records.</p>@endforelse</div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Stage Beneficiary & Amount Metrics</h3>
            <p class="mt-1 text-xs text-slate-500">Captures spreadsheet snapshots such as Approved, Obligated, Disbursed, Paid and Awarded beneficiary/amount counts.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <select wire:model="stageMetric.stage" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Select Stage</option>@foreach(['approved','procurement','obligated','disbursed','paid','awarded'] as $stage)<option value="{{ $stage }}">{{ ucfirst($stage) }}</option>@endforeach</select>
                <input type="date" wire:model="stageMetric.as_of_date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" wire:model="stageMetric.beneficiary_count" placeholder="Beneficiary Count" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="number" wire:model="stageMetric.female_beneficiary_count" placeholder="Female Count" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" step="0.01" wire:model="stageMetric.amount" placeholder="Amount" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><textarea wire:model="stageMetric.remarks" rows="2" placeholder="Remarks" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button wire:click="addStageMetric" type="button">Add Stage Metric</x-ui.button>@endcan
            </div>
            <div class="mt-5 overflow-x-auto"><table class="min-w-full text-xs"><thead><tr class="border-b text-left text-slate-500"><th class="py-2">Stage</th><th>Beneficiaries</th><th>Female</th><th>Amount</th><th></th></tr></thead><tbody>@forelse($stageMetrics as $item)<tr class="border-b border-slate-100"><td class="py-2 font-semibold">{{ ucfirst($item->stage) }}</td><td>{{ number_format((int)$item->beneficiary_count) }}</td><td>{{ number_format((int)$item->female_beneficiary_count) }}</td><td>₱{{ number_format((float)$item->amount,2) }}</td><td class="text-right">@can('project-spreadsheet-details.update')<button wire:click="deleteStageMetric({{ $item->id }})" wire:confirm="Delete this stage metric?" class="font-bold text-red-600">Delete</button>@endcan</td></tr>@empty<tr><td colspan="5" class="py-5 text-center text-slate-500">No stage metrics.</td></tr>@endforelse</tbody></table></div>
        </x-ui.card>
    </div>


    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Sector Assistance Metrics</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <input wire:model="sectorMetric.sector_name" placeholder="Sector (PCL, Senior Citizen, PWD...)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input wire:model="sectorMetric.reporting_period" placeholder="Reporting period / month" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" wire:model="sectorMetric.beneficiary_count" placeholder="Total beneficiaries" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="number" wire:model="sectorMetric.female_count" placeholder="Female" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" step="0.01" wire:model="sectorMetric.assistance_amount" placeholder="Assistance amount" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input wire:model="sectorMetric.attribute_detail" placeholder="Age / disability / sector detail" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <textarea wire:model="sectorMetric.beneficiary_names" rows="2" placeholder="Beneficiary name(s) when workbook records them" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                <textarea wire:model="sectorMetric.beneficiary_addresses" rows="2" placeholder="Beneficiary address(es)" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button type="button" wire:click="addSectorMetric">Add Sector Metric</x-ui.button>@endcan
            </div>
            <div class="mt-4 max-h-64 space-y-2 overflow-y-auto">@forelse($sectorMetrics as $m)<div class="flex justify-between rounded-lg border p-3 text-xs"><span><strong>{{ $m->sector_name }}</strong> · {{ $m->beneficiary_count ?? 0 }} beneficiaries · ₱{{ number_format((float)$m->assistance_amount,2) }}</span>@can('project-spreadsheet-details.update')<button wire:click="deleteSectorMetric({{ $m->id }})" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No sector metrics.</p>@endforelse</div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Livelihood Assistance Metrics</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <input wire:model="livelihoodMetric.livelihood_name" placeholder="Livelihood" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input wire:model="livelihoodMetric.reporting_period" placeholder="Reporting period" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" wire:model="livelihoodMetric.beneficiary_count" placeholder="Beneficiaries" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="number" wire:model="livelihoodMetric.female_count" placeholder="Female" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" step="0.01" wire:model="livelihoodMetric.assistance_amount" placeholder="Assistance amount" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><textarea wire:model="livelihoodMetric.remarks" rows="2" placeholder="Remarks" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button type="button" wire:click="addLivelihoodMetric">Add Livelihood Metric</x-ui.button>@endcan
            </div>
            <div class="mt-4 max-h-64 space-y-2 overflow-y-auto">@forelse($livelihoodMetrics as $m)<div class="flex justify-between rounded-lg border p-3 text-xs"><span><strong>{{ $m->livelihood_name }}</strong> · {{ $m->beneficiary_count ?? 0 }} · ₱{{ number_format((float)$m->assistance_amount,2) }}</span>@can('project-spreadsheet-details.update')<button wire:click="deleteLivelihoodMetric({{ $m->id }})" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No livelihood metrics.</p>@endforelse</div>
        </x-ui.card>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Special Programs / Referrals</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <input wire:model="specialMetric.program_name" placeholder="Program (TUPAD referral, NIA, KADIWA, YAKAP...)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input wire:model="specialMetric.metric_name" placeholder="Metric / nature of assistance" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" wire:model="specialMetric.beneficiary_count" placeholder="Beneficiaries" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="number" wire:model="specialMetric.female_count" placeholder="Female" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" wire:model="specialMetric.association_count" placeholder="Associations" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="number" step="0.01" wire:model="specialMetric.assistance_amount" placeholder="Amount" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <textarea wire:model="specialMetric.beneficiary_or_assistance_detail" rows="2" placeholder="Names / undertaking / assistance detail" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button type="button" wire:click="addSpecialMetric">Add Program Metric</x-ui.button>@endcan
            </div>
            <div class="mt-4 max-h-64 space-y-2 overflow-y-auto">@forelse($specialMetrics as $m)<div class="flex justify-between rounded-lg border p-3 text-xs"><span><strong>{{ $m->program_name }}</strong> · {{ $m->beneficiary_count ?? 0 }} beneficiaries</span>@can('project-spreadsheet-details.update')<button wire:click="deleteSpecialMetric({{ $m->id }})" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No special-program metrics.</p>@endforelse</div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">SPRS / CQPR / PCL Inclusion</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <select wire:model="reportingInclusion.report_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Report Type</option><option>SPRS</option><option>CQPR</option><option>PCL</option><option>OTHER</option></select>
                <input wire:model="reportingInclusion.reporting_period" placeholder="Reporting period / month" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" min="1" max="4" wire:model="reportingInclusion.quarter" placeholder="Quarter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input wire:model="reportingInclusion.status" placeholder="Status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <label class="text-xs text-slate-500">Approved Month<input type="date" wire:model="reportingInclusion.approved_month" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label><label class="text-xs text-slate-500">Reported Date<input type="date" wire:model="reportingInclusion.reported_at" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                <textarea wire:model="reportingInclusion.remarks" rows="2" placeholder="Remarks" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button type="button" wire:click="addReportingInclusion">Add Report Inclusion</x-ui.button>@endcan
            </div>
            <div class="mt-4 space-y-2">@forelse($reportingInclusions as $m)<div class="flex justify-between rounded-lg border p-3 text-xs"><span><strong>{{ $m->report_type }}</strong> · {{ $m->reporting_period ?: '—' }} · {{ $m->status ?: '—' }}</span>@can('project-spreadsheet-details.update')<button wire:click="deleteReportingInclusion({{ $m->id }})" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No report-inclusion records.</p>@endforelse</div>
        </x-ui.card>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Post-Implementation / Liquidation</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-xs text-slate-500">Date Received<input type="date" wire:model="postImplementation.received_at" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label><input wire:model="postImplementation.are_reference" placeholder="ARE reference" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <label class="text-xs text-slate-500">Check Awarding to Proponent<input type="date" wire:model="postImplementation.check_awarding_to_proponent_at" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label><label class="text-xs text-slate-500">Awarded to Beneficiaries<input type="date" wire:model="postImplementation.awarded_to_beneficiaries_at" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                <label class="text-xs text-slate-500">Forwarded to Supply Unit<input type="date" wire:model="postImplementation.forwarded_to_supply_unit_at" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label><input wire:model="postImplementation.status" placeholder="Status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <textarea wire:model="postImplementation.inclusions" rows="2" placeholder="Post-doc/liquidation inclusions" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button type="button" wire:click="addPostImplementation">Add Post-Implementation Record</x-ui.button>@endcan
            </div>
            <div class="mt-4 space-y-2">@forelse($postImplementationRecords as $m)<div class="flex justify-between rounded-lg border p-3 text-xs"><span><strong>{{ $m->status ?: 'Post-implementation' }}</strong> · {{ $m->received_at?->format('M d, Y') ?? 'No date' }}</span>@can('project-spreadsheet-details.update')<button wire:click="deletePostImplementation({{ $m->id }})" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No post-implementation records.</p>@endforelse</div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-sm font-bold text-slate-900">Convergence Metrics</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <input wire:model="convergenceMetric.initiative_name" placeholder="Initiative / program" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input wire:model="convergenceMetric.beneficiary_type" placeholder="Beneficiary type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" wire:model="convergenceMetric.beneficiary_count" placeholder="Beneficiaries" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="number" wire:model="convergenceMetric.female_count" placeholder="Female" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="number" step="0.01" wire:model="convergenceMetric.assistance_amount" placeholder="Assistance amount" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><textarea wire:model="convergenceMetric.remarks" rows="2" placeholder="Remarks" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                @can('project-spreadsheet-details.update')<x-ui.button type="button" wire:click="addConvergenceMetric">Add Convergence Metric</x-ui.button>@endcan
            </div>
            <div class="mt-4 space-y-2">@forelse($convergenceMetrics as $m)<div class="flex justify-between rounded-lg border p-3 text-xs"><span><strong>{{ $m->initiative_name }}</strong> · {{ $m->beneficiary_count ?? 0 }} · ₱{{ number_format((float)$m->assistance_amount,2) }}</span>@can('project-spreadsheet-details.update')<button wire:click="deleteConvergenceMetric({{ $m->id }})" class="font-bold text-red-600">Delete</button>@endcan</div>@empty<p class="text-sm text-slate-500">No convergence metrics.</p>@endforelse</div>
        </x-ui.card>
    </div>

    <x-ui.card>
        <h3 class="text-sm font-bold text-slate-900">Legacy Status Snapshots</h3>
        <p class="mt-1 text-xs text-slate-500">Preserves spreadsheet-specific TSSD, IMSD, procurement/payment or other status dimensions without replacing the authoritative workflow history.</p>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <input wire:model="statusSnapshot.dimension" placeholder="Dimension (TSSD / IMSD / DIS...)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input wire:model="statusSnapshot.status" placeholder="Status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="date" wire:model="statusSnapshot.as_of_date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <input type="number" wire:model="statusSnapshot.beneficiary_count" placeholder="Beneficiaries" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="number" step="0.01" wire:model="statusSnapshot.amount" placeholder="Amount" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><textarea wire:model="statusSnapshot.remarks" rows="2" placeholder="Remarks" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            @can('project-spreadsheet-details.update')<x-ui.button type="button" wire:click="addStatusSnapshot">Add Status Snapshot</x-ui.button>@endcan
        </div>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-xs"><thead><tr class="border-b text-left text-slate-500"><th class="py-2">Dimension</th><th>Status</th><th>Beneficiaries</th><th>Amount</th><th></th></tr></thead><tbody>@forelse($statusSnapshots as $m)<tr class="border-b"><td class="py-2 font-semibold">{{ $m->dimension }}</td><td>{{ $m->status }}</td><td>{{ $m->beneficiary_count ?? 0 }}</td><td>₱{{ number_format((float)$m->amount,2) }}</td><td class="text-right">@can('project-spreadsheet-details.update')<button wire:click="deleteStatusSnapshot({{ $m->id }})" class="font-bold text-red-600">Delete</button>@endcan</td></tr>@empty<tr><td colspan="5" class="py-5 text-center text-slate-500">No legacy status snapshots.</td></tr>@endforelse</tbody></table></div>
    </x-ui.card>

    <x-ui.alert variant="info" title="Detailed Payment Timeline">
        Phase 10 also expands existing disbursement records with Prepared Date, Forwarded to Signatories, Release Date/Time, Check/LDDAP-ADA Number and Date, and DV Number. These fields are stored on the existing disbursement records and will be wired into the full workbook importer in Phase 11.
    </x-ui.alert>
</div>
