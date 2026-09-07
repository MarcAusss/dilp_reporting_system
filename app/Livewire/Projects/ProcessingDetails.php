<?php

namespace App\Livewire\Projects;

use App\Enums\DisbursementStatus;
use App\Enums\ImplementationStatus;
use App\Enums\InsuranceRecordStatus;
use App\Enums\ObligationStatus;
use App\Enums\PermissionName;
use App\Enums\ProcurementStatus;
use App\Enums\ReplacementRequestStatus;
use App\Models\Project;
use App\Models\ProjectDisbursement;
use App\Models\ProjectImplementation;
use App\Models\ProjectInsurance;
use App\Models\ProjectObligation;
use App\Models\ProjectProcurement;
use App\Models\ProjectReplacementRequest;
use App\Services\ProjectProcessingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ProcessingDetails extends Component
{
    public int $projectId;

    public string $activeSection = 'procurement';

    public bool $showProcurementForm = false;
    public ?int $editingProcurementId = null;
    public string $procurement_reference_number = '';
    public string $procurement_description = '';
    public string $procurement_supplier = '';
    public string $procurement_date = '';
    public string $procurement_amount = '0.00';
    public string $procurement_status = 'planned';
    public string $procurement_remarks = '';

    public bool $showObligationForm = false;
    public ?int $editingObligationId = null;
    public string $obligation_number = '';
    public string $obligation_date = '';
    public string $obligation_amount = '0.00';
    public string $obligation_status = 'pending';
    public string $obligation_remarks = '';

    public bool $showDisbursementForm = false;
    public ?int $editingDisbursementId = null;
    public string $disbursement_number = '';
    public string $payment_reference = '';
    public string $payee = '';
    public string $disbursement_date = '';
    public string $disbursement_amount = '0.00';
    public string $disbursement_status = 'for_payment';
    public string $disbursement_remarks = '';

    public bool $showInsuranceForm = false;
    public ?int $editingInsuranceId = null;
    public string $insurance_provider = '';
    public string $policy_number = '';
    public string $coverage_start = '';
    public string $coverage_end = '';
    public string $premium_amount = '0.00';
    public string $covered_amount = '0.00';
    public string $insurance_status = 'pending';
    public string $insurance_remarks = '';

    public string $implementation_start_date = '';
    public string $target_completion_date = '';
    public string $actual_completion_date = '';
    public string $accomplishment_percentage = '0';
    public string $implementation_status = 'not_started';
    public string $implementation_remarks = '';

    public bool $showReplacementForm = false;
    public ?int $editingReplacementId = null;
    public string $replacement_reference_number = '';
    public string $replacement_item_description = '';
    public string $replacement_reason = '';
    public string $requested_amount = '0.00';
    public string $request_date = '';
    public string $resolution_date = '';
    public string $replacement_status = 'pending';
    public string $replacement_remarks = '';

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectProcessingView->value);

        Project::query()->findOrFail($projectId);
        $this->projectId = $projectId;
        $this->loadImplementation();
    }

    public function selectSection(string $section): void
    {
        if (! in_array($section, [
            'procurement',
            'obligation',
            'disbursement',
            'insurance',
            'implementation',
            'replacement',
        ], true)) {
            return;
        }

        $this->activeSection = $section;
        $this->resetValidation();
    }

    public function startProcurement(): void
    {
        $this->authorizeUpdate();
        $this->resetProcurementForm();
        $this->showProcurementForm = true;
    }

    public function editProcurement(int $id): void
    {
        $this->authorizeUpdate();

        $record = ProjectProcurement::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingProcurementId = $record->id;
        $this->procurement_reference_number = $record->reference_number ?? '';
        $this->procurement_description = $record->description;
        $this->procurement_supplier = $record->supplier ?? '';
        $this->procurement_date = $record->procurement_date?->format('Y-m-d') ?? '';
        $this->procurement_amount = $this->money($record->amount);
        $this->procurement_status = $record->status->value;
        $this->procurement_remarks = $record->remarks ?? '';
        $this->showProcurementForm = true;
    }

    public function saveProcurement(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'procurement_reference_number' => ['nullable', 'string', 'max:150'],
            'procurement_description' => ['required', 'string', 'max:255'],
            'procurement_supplier' => ['nullable', 'string', 'max:255'],
            'procurement_date' => ['nullable', 'date'],
            'procurement_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'procurement_status' => ['required', Rule::enum(ProcurementStatus::class)],
            'procurement_remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $record = $this->editingProcurementId
            ? ProjectProcurement::query()->where('project_id', $this->projectId)->findOrFail($this->editingProcurementId)
            : new ProjectProcurement(['project_id' => $this->projectId, 'created_by' => auth()->id()]);

        $record->fill([
            'reference_number' => $this->nullableTrim($validated['procurement_reference_number'] ?? null),
            'description' => trim($validated['procurement_description']),
            'supplier' => $this->nullableTrim($validated['procurement_supplier'] ?? null),
            'procurement_date' => $validated['procurement_date'] ?: null,
            'amount' => $validated['procurement_amount'],
            'status' => $validated['procurement_status'],
            'remarks' => $this->nullableTrim($validated['procurement_remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        $this->resetProcurementForm();
        session()->flash('processing-status', 'Procurement record saved successfully.');
    }

    public function cancelProcurement(): void
    {
        $this->resetProcurementForm();
    }

    public function startObligation(): void
    {
        $this->authorizeUpdate();
        $this->resetObligationForm();
        $this->showObligationForm = true;
    }

    public function editObligation(int $id): void
    {
        $this->authorizeUpdate();

        $record = ProjectObligation::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingObligationId = $record->id;
        $this->obligation_number = $record->obligation_number ?? '';
        $this->obligation_date = $record->obligation_date?->format('Y-m-d') ?? '';
        $this->obligation_amount = $this->money($record->amount);
        $this->obligation_status = $record->status->value;
        $this->obligation_remarks = $record->remarks ?? '';
        $this->showObligationForm = true;
    }

    public function saveObligation(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'obligation_number' => ['nullable', 'string', 'max:150'],
            'obligation_date' => ['nullable', 'date'],
            'obligation_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'obligation_status' => ['required', Rule::enum(ObligationStatus::class)],
            'obligation_remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $project = $this->project();

        if ($validated['obligation_status'] === ObligationStatus::Obligated->value) {
            $otherTotal = (float) ProjectObligation::query()
                ->where('project_id', $this->projectId)
                ->where('status', ObligationStatus::Obligated->value)
                ->when($this->editingObligationId, fn ($query) => $query->where('id', '!=', $this->editingObligationId))
                ->sum('amount');

            $newTotal = round($otherTotal + (float) $validated['obligation_amount'], 2);
            $doleShare = round((float) ($project->financial?->doleShare() ?? 0), 2);

            if ($newTotal > $doleShare) {
                throw ValidationException::withMessages([
                    'obligation_amount' => 'The total obligated amount cannot exceed the saved DOLE share of ₱'.number_format($doleShare, 2).'.',
                ]);
            }
        }

        $record = $this->editingObligationId
            ? ProjectObligation::query()->where('project_id', $this->projectId)->findOrFail($this->editingObligationId)
            : new ProjectObligation(['project_id' => $this->projectId, 'created_by' => auth()->id()]);

        $record->fill([
            'obligation_number' => $this->nullableTrim($validated['obligation_number'] ?? null),
            'obligation_date' => $validated['obligation_date'] ?: null,
            'amount' => $validated['obligation_amount'],
            'status' => $validated['obligation_status'],
            'remarks' => $this->nullableTrim($validated['obligation_remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        $this->resetObligationForm();
        session()->flash('processing-status', 'Obligation record saved successfully.');
    }

    public function cancelObligation(): void
    {
        $this->resetObligationForm();
    }

    public function startDisbursement(): void
    {
        $this->authorizeUpdate();
        $this->resetDisbursementForm();
        $this->showDisbursementForm = true;
    }

    public function editDisbursement(int $id): void
    {
        $this->authorizeUpdate();

        $record = ProjectDisbursement::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingDisbursementId = $record->id;
        $this->disbursement_number = $record->disbursement_number ?? '';
        $this->payment_reference = $record->payment_reference ?? '';
        $this->payee = $record->payee ?? '';
        $this->disbursement_date = $record->disbursement_date?->format('Y-m-d') ?? '';
        $this->disbursement_amount = $this->money($record->amount);
        $this->disbursement_status = $record->status->value;
        $this->disbursement_remarks = $record->remarks ?? '';
        $this->showDisbursementForm = true;
    }

    public function saveDisbursement(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'disbursement_number' => ['nullable', 'string', 'max:150'],
            'payment_reference' => ['nullable', 'string', 'max:150'],
            'payee' => ['nullable', 'string', 'max:255'],
            'disbursement_date' => ['nullable', 'date'],
            'disbursement_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'disbursement_status' => ['required', Rule::enum(DisbursementStatus::class)],
            'disbursement_remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        if ($validated['disbursement_status'] === DisbursementStatus::Paid->value) {
            $obligated = (float) ProjectObligation::query()
                ->where('project_id', $this->projectId)
                ->where('status', ObligationStatus::Obligated->value)
                ->sum('amount');

            $otherPaid = (float) ProjectDisbursement::query()
                ->where('project_id', $this->projectId)
                ->where('status', DisbursementStatus::Paid->value)
                ->when($this->editingDisbursementId, fn ($query) => $query->where('id', '!=', $this->editingDisbursementId))
                ->sum('amount');

            $newPaid = round($otherPaid + (float) $validated['disbursement_amount'], 2);

            if ($newPaid > $obligated) {
                throw ValidationException::withMessages([
                    'disbursement_amount' => 'Total paid disbursements cannot exceed the obligated amount of ₱'.number_format($obligated, 2).'.',
                ]);
            }
        }

        $record = $this->editingDisbursementId
            ? ProjectDisbursement::query()->where('project_id', $this->projectId)->findOrFail($this->editingDisbursementId)
            : new ProjectDisbursement(['project_id' => $this->projectId, 'created_by' => auth()->id()]);

        $record->fill([
            'disbursement_number' => $this->nullableTrim($validated['disbursement_number'] ?? null),
            'payment_reference' => $this->nullableTrim($validated['payment_reference'] ?? null),
            'payee' => $this->nullableTrim($validated['payee'] ?? null),
            'disbursement_date' => $validated['disbursement_date'] ?: null,
            'amount' => $validated['disbursement_amount'],
            'status' => $validated['disbursement_status'],
            'remarks' => $this->nullableTrim($validated['disbursement_remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        $this->resetDisbursementForm();
        session()->flash('processing-status', 'Disbursement record saved successfully.');
    }

    public function cancelDisbursement(): void
    {
        $this->resetDisbursementForm();
    }

    public function startInsurance(): void
    {
        $this->authorizeUpdate();
        $this->resetInsuranceForm();
        $this->showInsuranceForm = true;
    }

    public function editInsurance(int $id): void
    {
        $this->authorizeUpdate();

        $record = ProjectInsurance::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingInsuranceId = $record->id;
        $this->insurance_provider = $record->provider ?? '';
        $this->policy_number = $record->policy_number ?? '';
        $this->coverage_start = $record->coverage_start?->format('Y-m-d') ?? '';
        $this->coverage_end = $record->coverage_end?->format('Y-m-d') ?? '';
        $this->premium_amount = $this->money($record->premium_amount);
        $this->covered_amount = $this->money($record->covered_amount);
        $this->insurance_status = $record->status->value;
        $this->insurance_remarks = $record->remarks ?? '';
        $this->showInsuranceForm = true;
    }

    public function saveInsurance(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'insurance_provider' => ['nullable', 'string', 'max:255'],
            'policy_number' => ['nullable', 'string', 'max:150'],
            'coverage_start' => ['nullable', 'date'],
            'coverage_end' => ['nullable', 'date', 'after_or_equal:coverage_start'],
            'premium_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'covered_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'insurance_status' => ['required', Rule::enum(InsuranceRecordStatus::class)],
            'insurance_remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $record = $this->editingInsuranceId
            ? ProjectInsurance::query()->where('project_id', $this->projectId)->findOrFail($this->editingInsuranceId)
            : new ProjectInsurance(['project_id' => $this->projectId, 'created_by' => auth()->id()]);

        $record->fill([
            'provider' => $this->nullableTrim($validated['insurance_provider'] ?? null),
            'policy_number' => $this->nullableTrim($validated['policy_number'] ?? null),
            'coverage_start' => $validated['coverage_start'] ?: null,
            'coverage_end' => $validated['coverage_end'] ?: null,
            'premium_amount' => $validated['premium_amount'],
            'covered_amount' => $validated['covered_amount'],
            'status' => $validated['insurance_status'],
            'remarks' => $this->nullableTrim($validated['insurance_remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        $this->resetInsuranceForm();
        session()->flash('processing-status', 'Insurance record saved successfully.');
    }

    public function cancelInsurance(): void
    {
        $this->resetInsuranceForm();
    }

    public function saveImplementation(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'implementation_start_date' => ['nullable', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:implementation_start_date'],
            'actual_completion_date' => [
                $this->implementation_status === ImplementationStatus::Completed->value ? 'required' : 'nullable',
                'date',
                'after_or_equal:implementation_start_date',
            ],
            'accomplishment_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'implementation_status' => ['required', Rule::enum(ImplementationStatus::class)],
            'implementation_remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        if ($validated['implementation_status'] === ImplementationStatus::Completed->value
            && (int) $validated['accomplishment_percentage'] !== 100) {
            throw ValidationException::withMessages([
                'accomplishment_percentage' => 'A completed project must have 100% accomplishment.',
            ]);
        }

        $record = ProjectImplementation::query()->firstOrNew([
            'project_id' => $this->projectId,
        ]);

        if (! $record->exists) {
            $record->created_by = auth()->id();
        }

        $record->fill([
            'start_date' => $validated['implementation_start_date'] ?: null,
            'target_completion_date' => $validated['target_completion_date'] ?: null,
            'actual_completion_date' => $validated['actual_completion_date'] ?: null,
            'accomplishment_percentage' => $validated['accomplishment_percentage'],
            'status' => $validated['implementation_status'],
            'remarks' => $this->nullableTrim($validated['implementation_remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        $this->loadImplementation();
        session()->flash('processing-status', 'Implementation progress saved successfully.');
    }

    public function startReplacement(): void
    {
        $this->authorizeUpdate();
        $this->resetReplacementForm();
        $this->showReplacementForm = true;
    }

    public function editReplacement(int $id): void
    {
        $this->authorizeUpdate();

        $record = ProjectReplacementRequest::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingReplacementId = $record->id;
        $this->replacement_reference_number = $record->reference_number ?? '';
        $this->replacement_item_description = $record->item_description;
        $this->replacement_reason = $record->reason;
        $this->requested_amount = $this->money($record->requested_amount);
        $this->request_date = $record->request_date?->format('Y-m-d') ?? '';
        $this->resolution_date = $record->resolution_date?->format('Y-m-d') ?? '';
        $this->replacement_status = $record->status->value;
        $this->replacement_remarks = $record->remarks ?? '';
        $this->showReplacementForm = true;
    }

    public function saveReplacement(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'replacement_reference_number' => ['nullable', 'string', 'max:150'],
            'replacement_item_description' => ['required', 'string', 'max:255'],
            'replacement_reason' => ['required', 'string', 'max:3000'],
            'requested_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'request_date' => ['nullable', 'date'],
            'resolution_date' => ['nullable', 'date', 'after_or_equal:request_date'],
            'replacement_status' => ['required', Rule::enum(ReplacementRequestStatus::class)],
            'replacement_remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $record = $this->editingReplacementId
            ? ProjectReplacementRequest::query()->where('project_id', $this->projectId)->findOrFail($this->editingReplacementId)
            : new ProjectReplacementRequest(['project_id' => $this->projectId, 'created_by' => auth()->id()]);

        $record->fill([
            'reference_number' => $this->nullableTrim($validated['replacement_reference_number'] ?? null),
            'item_description' => trim($validated['replacement_item_description']),
            'reason' => trim($validated['replacement_reason']),
            'requested_amount' => $validated['requested_amount'],
            'request_date' => $validated['request_date'] ?: null,
            'resolution_date' => $validated['resolution_date'] ?: null,
            'status' => $validated['replacement_status'],
            'remarks' => $this->nullableTrim($validated['replacement_remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        $this->resetReplacementForm();
        session()->flash('processing-status', 'Replacement request saved successfully.');
    }

    public function cancelReplacement(): void
    {
        $this->resetReplacementForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectProcessingView->value);

        $project = Project::query()
            ->with([
                'proponent',
                'projectType',
                'projectPurpose',
                'financial',
                'workflowState',
                'procurements',
                'obligations',
                'disbursements',
                'insurances',
                'implementation',
                'replacementRequests',
            ])
            ->findOrFail($this->projectId);

        $service = app(ProjectProcessingService::class);

        return view('livewire.projects.processing-details', [
            'project' => $project,
            'isUnlocked' => $service->isUnlocked($project),
            'summary' => $service->summary($project),
            'procurementStatuses' => ProcurementStatus::cases(),
            'obligationStatuses' => ObligationStatus::cases(),
            'disbursementStatuses' => DisbursementStatus::cases(),
            'insuranceStatuses' => InsuranceRecordStatus::cases(),
            'implementationStatuses' => ImplementationStatus::cases(),
            'replacementStatuses' => ReplacementRequestStatus::cases(),
        ]);
    }

    private function authorizeUpdate(): void
    {
        Gate::authorize(PermissionName::ProjectProcessingUpdate->value);
        app(ProjectProcessingService::class)->assertUnlocked($this->project());
    }

    private function project(): Project
    {
        return Project::query()
            ->with(['financial', 'workflowState'])
            ->findOrFail($this->projectId);
    }

    private function loadImplementation(): void
    {
        $record = ProjectImplementation::query()
            ->where('project_id', $this->projectId)
            ->first();

        $this->implementation_start_date = $record?->start_date?->format('Y-m-d') ?? '';
        $this->target_completion_date = $record?->target_completion_date?->format('Y-m-d') ?? '';
        $this->actual_completion_date = $record?->actual_completion_date?->format('Y-m-d') ?? '';
        $this->accomplishment_percentage = (string) ($record?->accomplishment_percentage ?? 0);
        $this->implementation_status = $record?->status?->value ?? ImplementationStatus::NotStarted->value;
        $this->implementation_remarks = $record?->remarks ?? '';
    }

    private function resetProcurementForm(): void
    {
        $this->editingProcurementId = null;
        $this->procurement_reference_number = '';
        $this->procurement_description = '';
        $this->procurement_supplier = '';
        $this->procurement_date = '';
        $this->procurement_amount = '0.00';
        $this->procurement_status = ProcurementStatus::Planned->value;
        $this->procurement_remarks = '';
        $this->showProcurementForm = false;
        $this->resetValidation();
    }

    private function resetObligationForm(): void
    {
        $this->editingObligationId = null;
        $this->obligation_number = '';
        $this->obligation_date = '';
        $this->obligation_amount = '0.00';
        $this->obligation_status = ObligationStatus::Pending->value;
        $this->obligation_remarks = '';
        $this->showObligationForm = false;
        $this->resetValidation();
    }

    private function resetDisbursementForm(): void
    {
        $this->editingDisbursementId = null;
        $this->disbursement_number = '';
        $this->payment_reference = '';
        $this->payee = '';
        $this->disbursement_date = '';
        $this->disbursement_amount = '0.00';
        $this->disbursement_status = DisbursementStatus::ForPayment->value;
        $this->disbursement_remarks = '';
        $this->showDisbursementForm = false;
        $this->resetValidation();
    }

    private function resetInsuranceForm(): void
    {
        $this->editingInsuranceId = null;
        $this->insurance_provider = '';
        $this->policy_number = '';
        $this->coverage_start = '';
        $this->coverage_end = '';
        $this->premium_amount = '0.00';
        $this->covered_amount = '0.00';
        $this->insurance_status = InsuranceRecordStatus::Pending->value;
        $this->insurance_remarks = '';
        $this->showInsuranceForm = false;
        $this->resetValidation();
    }

    private function resetReplacementForm(): void
    {
        $this->editingReplacementId = null;
        $this->replacement_reference_number = '';
        $this->replacement_item_description = '';
        $this->replacement_reason = '';
        $this->requested_amount = '0.00';
        $this->request_date = '';
        $this->resolution_date = '';
        $this->replacement_status = ReplacementRequestStatus::Pending->value;
        $this->replacement_remarks = '';
        $this->showReplacementForm = false;
        $this->resetValidation();
    }

    private function nullableTrim(mixed $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
