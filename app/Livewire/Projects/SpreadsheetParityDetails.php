<?php

namespace App\Livewire\Projects;

use App\Enums\PermissionName;
use App\Models\Project;
use App\Models\ProjectBeneficiaryMetric;
use App\Models\ProjectComplianceCommunication;
use App\Models\ProjectDisValidation;
use App\Models\ProjectEndorsement;
use App\Models\ProjectFundingDetail;
use App\Models\ProjectGpaiRecord;
use App\Models\ProjectMoaRecord;
use App\Models\ProjectStageMetric;
use App\Models\ProjectSectorMetric;
use App\Models\ProjectLivelihoodMetric;
use App\Models\ProjectSpecialProgramMetric;
use App\Models\ProjectReportingInclusion;
use App\Models\ProjectPostImplementationRecord;
use App\Models\ProjectConvergenceMetric;
use App\Models\ProjectStatusSnapshot;
use App\Models\FundSource;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SpreadsheetParityDetails extends Component
{
    public int $projectId;

    public array $funding = [];
    public array $proponent = [];
    public array $dis = [];
    public array $beneficiaryMetric = [];
    public array $moa = [];

    public array $endorsement = ['sequence_no' => 1, 'endorsement_type' => 'endorsement'];
    public array $communication = ['type' => 'LCOM', 'status' => 'open'];
    public array $gpai = [];
    public array $stageMetric = [];
    public array $sectorMetric = [];
    public array $livelihoodMetric = [];
    public array $specialMetric = [];
    public array $reportingInclusion = [];
    public array $postImplementation = [];
    public array $convergenceMetric = [];
    public array $statusSnapshot = [];
    public array $locationMetadata = [];

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsView->value);
        Project::query()->findOrFail($projectId);
        $this->projectId = $projectId;
        $this->loadOneToOneRecords();
    }

    public function saveCoreDetails(): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);

        $validated = $this->validate([
            'funding.adl_nta_number' => ['nullable','string','max:150'],
            'funding.fund_sponsor' => ['nullable','string','max:255'],
            'funding.partylist_nga' => ['nullable','string','max:255'],
            'funding.legislator' => ['nullable','string','max:255'],
            'funding.lce_congressman_point_person' => ['nullable','string','max:255'],
            'funding.district' => ['nullable','string','max:100'],
            'funding.actual_charging' => ['nullable','string','max:255'],
            'funding.target_barangays' => ['nullable','string','max:3000'],
            'funding.target_municipalities' => ['nullable','string','max:3000'],
            'funding.fund_amount' => ['nullable','numeric','min:0','max:9999999999999.99'],
            'funding.remarks' => ['nullable','string','max:3000'],

            'proponent.abbreviation' => ['nullable','string','max:100'],
            'proponent.head_name' => ['nullable','string','max:255'],
            'proponent.organization_name' => ['nullable','string','max:255'],
            'proponent.organization_classification' => ['nullable','string','max:150'],

            'dis.details_tally_with_dis' => ['nullable','boolean'],
            'dis.proposal_status' => ['nullable','string','max:150'],
            'dis.for_updating' => ['boolean'],
            'dis.for_coordination_po' => ['boolean'],
            'dis.for_coordination_co' => ['boolean'],
            'dis.action_required' => ['nullable','string','max:3000'],
            'dis.resolution' => ['nullable','string','max:3000'],
            'dis.remarks' => ['nullable','string','max:3000'],

            'beneficiaryMetric.total_beneficiaries' => ['nullable','integer','min:0','max:99999999'],
            'beneficiaryMetric.female_beneficiaries' => ['nullable','integer','min:0','max:99999999'],
            'beneficiaryMetric.male_beneficiaries' => ['nullable','integer','min:0','max:99999999'],
            'beneficiaryMetric.female_assistance_amount' => ['nullable','numeric','min:0','max:9999999999999.99'],
            'beneficiaryMetric.beneficiary_type_ies' => ['nullable','string','max:255'],
            'beneficiaryMetric.remarks' => ['nullable','string','max:3000'],

            'moa.received_at' => ['nullable','date'],
            'moa.forwarded_for_signature_at' => ['nullable','date'],
            'moa.signed_copy_returned_at' => ['nullable','date'],
            'moa.notarial_slip_at' => ['nullable','date'],
            'moa.forwarded_to_notarial_at' => ['nullable','date'],
            'moa.notarized_at' => ['nullable','date'],
            'moa.notarized_copy_received_at' => ['nullable','date'],
            'moa.original_folder_forwarded_imsd_at' => ['nullable','date'],
            'moa.lacking_requirements' => ['nullable','string','max:5000'],
            'moa.commitment' => ['nullable','string','max:5000'],
            'moa.important_note' => ['nullable','string','max:5000'],
            'locationMetadata.district' => ['nullable','string','max:100'],
            'locationMetadata.income_class' => ['nullable','string','max:100'],
        ]);

        $metricCheck = $validated['beneficiaryMetric'] ?? [];
        $totalCheck = $metricCheck['total_beneficiaries'] ?? null;
        $femaleCheck = $metricCheck['female_beneficiaries'] ?? null;
        $maleCheck = $metricCheck['male_beneficiaries'] ?? null;

        if ($totalCheck !== null && $femaleCheck !== null && $femaleCheck > $totalCheck) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'beneficiaryMetric.female_beneficiaries' => 'Female beneficiaries cannot exceed the total beneficiaries.',
            ]);
        }
        if ($totalCheck !== null && $maleCheck !== null && $maleCheck > $totalCheck) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'beneficiaryMetric.male_beneficiaries' => 'Male beneficiaries cannot exceed the total beneficiaries.',
            ]);
        }

        DB::transaction(function () use ($validated): void {
            $project = Project::query()->with('proponent')->findOrFail($this->projectId);

            $funding = $validated['funding'] ?? [];
            ProjectFundingDetail::query()->updateOrCreate(
                ['project_id' => $project->id],
                [...$funding, 'created_by' => auth()->id(), 'updated_by' => auth()->id()]
            );

            $project->proponent->update($validated['proponent'] ?? []);

            ProjectDisValidation::query()->updateOrCreate(
                ['project_id' => $project->id],
                [...($validated['dis'] ?? []), 'created_by' => auth()->id(), 'updated_by' => auth()->id()]
            );

            $metric = $validated['beneficiaryMetric'] ?? [];
            ProjectBeneficiaryMetric::query()->updateOrCreate(
                ['project_id' => $project->id],
                [...$metric, 'source' => 'legacy_spreadsheet', 'created_by' => auth()->id(), 'updated_by' => auth()->id()]
            );

            ProjectMoaRecord::query()->updateOrCreate(
                ['project_id' => $project->id],
                [...($validated['moa'] ?? []), 'created_by' => auth()->id(), 'updated_by' => auth()->id()]
            );

            if ($project->primaryLocation) {
                $project->primaryLocation->update($validated['locationMetadata'] ?? []);
            }
        });

        $this->loadOneToOneRecords();
        session()->flash('spreadsheet-detail-status', 'Spreadsheet parity details saved successfully.');
    }

    public function addEndorsement(): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
        $data = $this->validate([
            'endorsement.sequence_no' => ['required','integer','min:1','max:99'],
            'endorsement.endorsement_date' => ['nullable','date'],
            'endorsement.reference_number' => ['nullable','string','max:150'],
            'endorsement.endorsement_type' => ['required','string','max:100'],
            'endorsement.remarks' => ['nullable','string','max:3000'],
        ])['endorsement'];
        ProjectEndorsement::query()->updateOrCreate(
            ['project_id' => $this->projectId, 'sequence_no' => $data['sequence_no']],
            [...$data, 'created_by' => auth()->id()]
        );
        $this->endorsement = ['sequence_no' => ((int) ProjectEndorsement::where('project_id',$this->projectId)->max('sequence_no')) + 1, 'endorsement_type' => 'endorsement'];
        session()->flash('spreadsheet-detail-status', 'Endorsement saved.');
    }

    public function deleteEndorsement(int $id): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
        ProjectEndorsement::where('project_id',$this->projectId)->findOrFail($id)->delete();
    }

    public function addCommunication(): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
        $data = $this->validate([
            'communication.type' => ['required','string','max:50'],
            'communication.reference_number' => ['nullable','string','max:150'],
            'communication.issued_at' => ['nullable','date'],
            'communication.received_at' => ['nullable','date'],
            'communication.resolved_at' => ['nullable','date'],
            'communication.status' => ['required', Rule::in(['open','for_compliance','resolved','closed'])],
            'communication.remarks' => ['nullable','string','max:3000'],
        ])['communication'];
        ProjectComplianceCommunication::create([...$data, 'project_id'=>$this->projectId, 'created_by'=>auth()->id()]);
        $this->communication = ['type'=>'LCOM','status'=>'open'];
        session()->flash('spreadsheet-detail-status', 'Compliance communication saved.');
    }

    public function deleteCommunication(int $id): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
        ProjectComplianceCommunication::where('project_id',$this->projectId)->findOrFail($id)->delete();
    }

    public function addGpai(): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
        $data = $this->validate([
            'gpai.beneficiaries_enrolled' => ['nullable','integer','min:0'],
            'gpai.gpai_amount' => ['nullable','numeric','min:0'],
            'gpai.enrollment_status' => ['nullable','string','max:100'],
            'gpai.fund_source_id' => ['nullable','integer','exists:fund_sources,id'],
            'gpai.forwarded_for_enrollment_at' => ['nullable','date'],
            'gpai.cash_advance_payee' => ['nullable','string','max:255'],
            'gpai.responsible_person' => ['nullable','string','max:255'],
            'gpai.or_policy_received_at' => ['nullable','date'],
            'gpai.or_number' => ['nullable','string','max:150'],
            'gpai.or_date' => ['nullable','date'],
            'gpai.policy_number' => ['nullable','string','max:150'],
            'gpai.dv_number' => ['nullable','string','max:150'],
            'gpai.check_date' => ['nullable','date'],
            'gpai.check_number' => ['nullable','string','max:150'],
            'gpai.remarks' => ['nullable','string','max:3000'],
        ])['gpai'];
        ProjectGpaiRecord::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]);
        $this->gpai = [];
        session()->flash('spreadsheet-detail-status', 'GPAI record saved.');
    }

    public function deleteGpai(int $id): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
        ProjectGpaiRecord::where('project_id',$this->projectId)->findOrFail($id)->delete();
    }

    public function addStageMetric(): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
        $data = $this->validate([
            'stageMetric.stage' => ['required','string','max:80'],
            'stageMetric.beneficiary_count' => ['nullable','integer','min:0'],
            'stageMetric.female_beneficiary_count' => ['nullable','integer','min:0'],
            'stageMetric.amount' => ['nullable','numeric','min:0'],
            'stageMetric.as_of_date' => ['nullable','date'],
            'stageMetric.remarks' => ['nullable','string','max:3000'],
        ])['stageMetric'];
        ProjectStageMetric::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]);
        $this->stageMetric = [];
        session()->flash('spreadsheet-detail-status', 'Stage metric saved.');
    }

    public function deleteStageMetric(int $id): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
        ProjectStageMetric::where('project_id',$this->projectId)->findOrFail($id)->delete();
    }


    public function addSectorMetric(): void
    {
        $this->authorizeExtendedUpdate();
        $data = $this->validate([
            'sectorMetric.sector_name'=>['required','string','max:255'], 'sectorMetric.metric_type'=>['nullable','string','max:100'],
            'sectorMetric.beneficiary_count'=>['nullable','integer','min:0'], 'sectorMetric.female_count'=>['nullable','integer','min:0'],
            'sectorMetric.assistance_amount'=>['nullable','numeric','min:0'], 'sectorMetric.beneficiary_names'=>['nullable','string','max:10000'],
            'sectorMetric.beneficiary_addresses'=>['nullable','string','max:10000'], 'sectorMetric.attribute_detail'=>['nullable','string','max:5000'],
            'sectorMetric.reporting_period'=>['nullable','string','max:100'], 'sectorMetric.remarks'=>['nullable','string','max:3000'],
        ])['sectorMetric'];
        ProjectSectorMetric::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]); $this->sectorMetric=[];
    }
    public function deleteSectorMetric(int $id): void { $this->authorizeExtendedUpdate(); ProjectSectorMetric::where('project_id',$this->projectId)->findOrFail($id)->delete(); }

    public function addLivelihoodMetric(): void
    {
        $this->authorizeExtendedUpdate();
        $data=$this->validate([
            'livelihoodMetric.livelihood_name'=>['required','string','max:255'],'livelihoodMetric.beneficiary_count'=>['nullable','integer','min:0'],
            'livelihoodMetric.female_count'=>['nullable','integer','min:0'],'livelihoodMetric.assistance_amount'=>['nullable','numeric','min:0'],
            'livelihoodMetric.reporting_period'=>['nullable','string','max:100'],'livelihoodMetric.remarks'=>['nullable','string','max:3000'],
        ])['livelihoodMetric'];
        ProjectLivelihoodMetric::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]); $this->livelihoodMetric=[];
    }
    public function deleteLivelihoodMetric(int $id): void { $this->authorizeExtendedUpdate(); ProjectLivelihoodMetric::where('project_id',$this->projectId)->findOrFail($id)->delete(); }

    public function addSpecialMetric(): void
    {
        $this->authorizeExtendedUpdate();
        $data=$this->validate([
            'specialMetric.program_name'=>['required','string','max:255'],'specialMetric.metric_name'=>['nullable','string','max:255'],
            'specialMetric.beneficiary_count'=>['nullable','integer','min:0'],'specialMetric.female_count'=>['nullable','integer','min:0'],
            'specialMetric.association_count'=>['nullable','integer','min:0'],'specialMetric.assistance_amount'=>['nullable','numeric','min:0'],
            'specialMetric.beneficiary_or_assistance_detail'=>['nullable','string','max:10000'],'specialMetric.status'=>['nullable','string','max:100'],
            'specialMetric.as_of_date'=>['nullable','date'],'specialMetric.remarks'=>['nullable','string','max:3000'],
        ])['specialMetric'];
        ProjectSpecialProgramMetric::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]); $this->specialMetric=[];
    }
    public function deleteSpecialMetric(int $id): void { $this->authorizeExtendedUpdate(); ProjectSpecialProgramMetric::where('project_id',$this->projectId)->findOrFail($id)->delete(); }

    public function addReportingInclusion(): void
    {
        $this->authorizeExtendedUpdate();
        $data=$this->validate([
            'reportingInclusion.report_type'=>['required',Rule::in(['SPRS','CQPR','PCL','OTHER'])], 'reportingInclusion.reporting_period'=>['nullable','string','max:100'],
            'reportingInclusion.quarter'=>['nullable','integer','min:1','max:4'],'reportingInclusion.status'=>['nullable','string','max:100'],
            'reportingInclusion.approved_month'=>['nullable','date'],'reportingInclusion.reported_at'=>['nullable','date'],
            'reportingInclusion.beneficiary_count'=>['nullable','integer','min:0'],'reportingInclusion.amount'=>['nullable','numeric','min:0'],
            'reportingInclusion.remarks'=>['nullable','string','max:3000'],
        ])['reportingInclusion'];
        ProjectReportingInclusion::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]); $this->reportingInclusion=[];
    }
    public function deleteReportingInclusion(int $id): void { $this->authorizeExtendedUpdate(); ProjectReportingInclusion::where('project_id',$this->projectId)->findOrFail($id)->delete(); }

    public function addPostImplementation(): void
    {
        $this->authorizeExtendedUpdate();
        $data=$this->validate([
            'postImplementation.received_at'=>['nullable','date'],'postImplementation.inclusions'=>['nullable','string','max:10000'],
            'postImplementation.are_reference'=>['nullable','string','max:255'],'postImplementation.check_awarding_to_proponent_at'=>['nullable','date'],
            'postImplementation.awarded_to_beneficiaries_at'=>['nullable','date'],'postImplementation.forwarded_to_supply_unit_at'=>['nullable','date'],
            'postImplementation.status'=>['nullable','string','max:100'],'postImplementation.remarks'=>['nullable','string','max:3000'],
        ])['postImplementation'];
        ProjectPostImplementationRecord::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]); $this->postImplementation=[];
    }
    public function deletePostImplementation(int $id): void { $this->authorizeExtendedUpdate(); ProjectPostImplementationRecord::where('project_id',$this->projectId)->findOrFail($id)->delete(); }

    public function addConvergenceMetric(): void
    {
        $this->authorizeExtendedUpdate();
        $data=$this->validate([
            'convergenceMetric.initiative_name'=>['required','string','max:255'],'convergenceMetric.beneficiary_count'=>['nullable','integer','min:0'],
            'convergenceMetric.female_count'=>['nullable','integer','min:0'],'convergenceMetric.beneficiary_type'=>['nullable','string','max:255'],
            'convergenceMetric.assistance_amount'=>['nullable','numeric','min:0'],'convergenceMetric.remarks'=>['nullable','string','max:3000'],
        ])['convergenceMetric'];
        ProjectConvergenceMetric::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]); $this->convergenceMetric=[];
    }
    public function deleteConvergenceMetric(int $id): void { $this->authorizeExtendedUpdate(); ProjectConvergenceMetric::where('project_id',$this->projectId)->findOrFail($id)->delete(); }

    public function addStatusSnapshot(): void
    {
        $this->authorizeExtendedUpdate();
        $data=$this->validate([
            'statusSnapshot.dimension'=>['required','string','max:100'],'statusSnapshot.status'=>['nullable','string','max:255'],
            'statusSnapshot.as_of_date'=>['nullable','date'],'statusSnapshot.beneficiary_count'=>['nullable','integer','min:0'],
            'statusSnapshot.amount'=>['nullable','numeric','min:0'],'statusSnapshot.remarks'=>['nullable','string','max:3000'],
        ])['statusSnapshot'];
        ProjectStatusSnapshot::create([...$data,'project_id'=>$this->projectId,'created_by'=>auth()->id()]); $this->statusSnapshot=[];
    }
    public function deleteStatusSnapshot(int $id): void { $this->authorizeExtendedUpdate(); ProjectStatusSnapshot::where('project_id',$this->projectId)->findOrFail($id)->delete(); }

    private function authorizeExtendedUpdate(): void
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsUpdate->value);
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectSpreadsheetDetailsView->value);
        $project = Project::with(['proponent','projectType','projectPurpose'])->findOrFail($this->projectId);
        return view('livewire.projects.spreadsheet-parity-details', [
            'project' => $project,
            'endorsements' => $project->endorsements()->latest('sequence_no')->get(),
            'communications' => $project->complianceCommunications()->latest('issued_at')->latest('id')->get(),
            'gpaiRecords' => $project->gpaiRecords()->with('fundSource')->latest('id')->get(),
            'stageMetrics' => $project->stageMetrics()->latest('as_of_date')->latest('id')->get(),
            'sectorMetrics' => $project->sectorMetrics()->latest('id')->get(),
            'livelihoodMetrics' => $project->livelihoodMetrics()->latest('id')->get(),
            'specialMetrics' => $project->specialProgramMetrics()->latest('id')->get(),
            'reportingInclusions' => $project->reportingInclusions()->latest('id')->get(),
            'postImplementationRecords' => $project->postImplementationRecords()->latest('id')->get(),
            'convergenceMetrics' => $project->convergenceMetrics()->latest('id')->get(),
            'statusSnapshots' => $project->statusSnapshots()->latest('id')->get(),
            'fundSources' => FundSource::query()->where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    private function loadOneToOneRecords(): void
    {
        $project = Project::with(['proponent','fundingDetail','disValidation','beneficiaryMetric','moaRecord','primaryLocation'])->findOrFail($this->projectId);
        $this->funding = $project->fundingDetail?->only(['adl_nta_number','fund_sponsor','partylist_nga','legislator','lce_congressman_point_person','district','actual_charging','target_barangays','target_municipalities','fund_amount','remarks']) ?? [];
        $this->proponent = $project->proponent->only(['abbreviation','head_name','organization_name','organization_classification']);
        $this->dis = $project->disValidation?->only(['details_tally_with_dis','proposal_status','for_updating','for_coordination_po','for_coordination_co','action_required','resolution','remarks']) ?? ['for_updating'=>false,'for_coordination_po'=>false,'for_coordination_co'=>false];
        $this->beneficiaryMetric = $project->beneficiaryMetric?->only(['total_beneficiaries','female_beneficiaries','male_beneficiaries','female_assistance_amount','beneficiary_type_ies','remarks']) ?? [];
        $this->locationMetadata = $project->primaryLocation?->only(['district','income_class']) ?? [];
        $this->moa = $project->moaRecord?->only(['received_at','forwarded_for_signature_at','signed_copy_returned_at','notarial_slip_at','forwarded_to_notarial_at','notarized_at','notarized_copy_received_at','original_folder_forwarded_imsd_at','lacking_requirements','commitment','important_note']) ?? [];
    }
}
