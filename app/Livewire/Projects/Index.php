<?php

namespace App\Livewire\Projects;

use App\Enums\PermissionName;
use App\Enums\ProjectRecordStatus;
use App\Enums\ProponentType;
use App\Models\Barangay;
use App\Models\FundSource;
use App\Models\ImplementationMode;
use App\Models\Municipality;
use App\Models\Office;
use App\Models\Project;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\Province;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $fiscalYearFilter = '';

    public string $projectTypeFilter = '';

    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public int $fiscal_year = 2026;

    public string $project_code = '';

    public string $title = '';

    public string $proponent_type = 'individual';

    public string $proponent_name = '';

    public string $contact_person = '';

    public string $contact_number = '';

    public string $email = '';

    public string $proponent_address = '';

    public ?int $office_id = null;

    public ?int $fund_source_id = null;

    public ?int $project_type_id = null;

    public ?int $project_purpose_id = null;

    public ?int $implementation_mode_id = null;

    public string $date_received = '';

    public string $record_status = 'draft';

    public string $source_reference = '';

    public string $remarks = '';

    public ?int $province_id = null;

    public ?int $municipality_id = null;

    public ?int $barangay_id = null;

    public string $address_detail = '';

    public function mount(): void
    {
        Gate::authorize(
            PermissionName::ProjectsView->value
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFiscalYearFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProjectTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProvinceId(): void
    {
        $this->municipality_id = null;
        $this->barangay_id = null;
    }

    public function updatedMunicipalityId(): void
    {
        $this->barangay_id = null;
    }

    public function startCreate(): void
    {
        Gate::authorize(
            PermissionName::ProjectsCreate->value
        );

        $this->resetForm();

        $this->fiscal_year = 2026;
        $this->record_status =
            ProjectRecordStatus::Draft->value;

        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(
            PermissionName::ProjectsUpdate->value
        );

        $project = Project::query()
            ->with([
                'proponent',
                'primaryLocation',
            ])
            ->findOrFail($id);

        $this->editingId = $project->id;

        $this->fiscal_year = $project->fiscal_year;
        $this->project_code = $project->project_code ?? '';
        $this->title = $project->title;

        $this->proponent_type =
            $project->proponent->type->value;

        $this->proponent_name =
            $project->proponent->name;

        $this->contact_person =
            $project->proponent->contact_person ?? '';

        $this->contact_number =
            $project->proponent->contact_number ?? '';

        $this->email =
            $project->proponent->email ?? '';

        $this->proponent_address =
            $project->proponent->address ?? '';

        $this->office_id = $project->office_id;
        $this->fund_source_id = $project->fund_source_id;
        $this->project_type_id = $project->project_type_id;
        $this->project_purpose_id = $project->project_purpose_id;

        $this->implementation_mode_id =
            $project->implementation_mode_id;

        $this->date_received =
            $project->date_received?->format('Y-m-d') ?? '';

        $this->record_status =
            $project->record_status->value;

        $this->source_reference =
            $project->source_reference ?? '';

        $this->remarks =
            $project->remarks ?? '';

        if ($project->primaryLocation) {
            $this->province_id =
                $project->primaryLocation->province_id;

            $this->municipality_id =
                $project->primaryLocation->municipality_id;

            $this->barangay_id =
                $project->primaryLocation->barangay_id;

            $this->address_detail =
                $project->primaryLocation->address_detail ?? '';
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(
            $this->editingId
            ? PermissionName::ProjectsUpdate->value
            : PermissionName::ProjectsCreate->value
        );

        $validated = $this->validateProject();

        $this->validateLocationHierarchy();

        DB::transaction(function () use ($validated): void {
            if ($this->editingId) {
                $project = Project::query()
                    ->with('proponent')
                    ->findOrFail($this->editingId);

                $project->proponent->update(
                    $this->proponentData($validated)
                );

                $project->update(
                    $this->projectData(
                        $validated,
                        false
                    )
                );
            } else {
                $proponent = Proponent::query()->create(
                    $this->proponentData($validated)
                );

                $project = Project::query()->create([
                    ...$this->projectData(
                        $validated,
                        true
                    ),

                    'proponent_id' =>
                        $proponent->id,
                ]);
            }

            $project->locations()
                ->updateOrCreate(
                    [
                        'is_primary' => true,
                    ],
                    [
                        'province_id' =>
                            $validated['province_id'],

                        'municipality_id' =>
                            $validated['municipality_id']
                            ?? null,

                        'barangay_id' =>
                            $validated['barangay_id']
                            ?? null,

                        'address_detail' =>
                            filled(
                                $validated['address_detail']
                                ?? null
                            )
                            ? trim(
                                $validated['address_detail']
                            )
                            : null,
                    ]
                );
        });

        session()->flash(
            'project-status',
            $this->editingId
            ? 'Project record updated successfully.'
            : 'Project record created successfully.'
        );

        $this->resetForm();
    }

    public function toggleArchive(int $id): void
    {
        Gate::authorize(
            PermissionName::ProjectsArchive->value
        );

        $project = Project::query()
            ->findOrFail($id);

        $newStatus =
            $project->record_status ===
            ProjectRecordStatus::Archived
            ? ProjectRecordStatus::Active
            : ProjectRecordStatus::Archived;

        $project->update([
            'record_status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        session()->flash(
            'project-status',
            $newStatus === ProjectRecordStatus::Archived
            ? 'Project archived successfully.'
            : 'Project restored successfully.'
        );
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(
            PermissionName::ProjectsView->value
        );

        $projects = Project::query()
            ->with([
                'proponent',
                'office',
                'fundSource',
                'projectType',
                'projectPurpose',
                'primaryLocation.province',
                'primaryLocation.municipality',
                'primaryLocation.barangay',
            ])
            ->when(
                filled($this->search),
                function ($query): void {
                    $search = trim($this->search);

                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'registry_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'project_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'title',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'proponent',
                                    fn($query) =>
                                    $query->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                );
                        }
                    );
                }
            )
            ->when(
                filled($this->fiscalYearFilter),
                fn($query) =>
                $query->where(
                    'fiscal_year',
                    $this->fiscalYearFilter
                )
            )
            ->when(
                filled($this->projectTypeFilter),
                fn($query) =>
                $query->where(
                    'project_type_id',
                    $this->projectTypeFilter
                )
            )
            ->when(
                filled($this->statusFilter),
                fn($query) =>
                $query->where(
                    'record_status',
                    $this->statusFilter
                )
            )
            ->latest('id')
            ->paginate(15);

        $provinces = Province::query()
            ->active()
            ->ordered()
            ->get();

        $municipalities = Municipality::query()
            ->active()
            ->when(
                $this->province_id,
                fn($query) =>
                $query->where(
                    'province_id',
                    $this->province_id
                )
            )
            ->ordered()
            ->get();

        $barangays = Barangay::query()
            ->active()
            ->when(
                $this->municipality_id,
                fn($query) =>
                $query->where(
                    'municipality_id',
                    $this->municipality_id
                )
            )
            ->ordered()
            ->get();

        return view(
            'livewire.projects.index',
            [
                'projects' => $projects,

                'offices' => Office::query()
                    ->active()
                    ->ordered()
                    ->get(),

                'fundSources' => FundSource::query()
                    ->active()
                    ->ordered()
                    ->get(),

                'projectTypes' => ProjectType::query()
                    ->active()
                    ->ordered()
                    ->get(),

                'projectPurposes' => ProjectPurpose::query()
                    ->active()
                    ->ordered()
                    ->get(),

                'implementationModes' =>
                    ImplementationMode::query()
                        ->active()
                        ->ordered()
                        ->get(),

                'provinces' => $provinces,
                'municipalities' => $municipalities,
                'barangays' => $barangays,

                'recordStatuses' =>
                    ProjectRecordStatus::cases(),

                'proponentTypes' =>
                    ProponentType::cases(),
            ]
        );
    }

    private function validateProject(): array
    {
        return $this->validate([
            'fiscal_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'project_code' => [
                'nullable',
                'string',
                'max:100',

                Rule::unique(
                    'projects',
                    'project_code'
                )
                    ->where(
                        fn($query) =>
                        $query->where(
                            'fiscal_year',
                            $this->fiscal_year
                        )
                    )
                    ->ignore($this->editingId),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'proponent_type' => [
                'required',
                Rule::in(
                    array_map(
                        fn(ProponentType $type) =>
                        $type->value,
                        ProponentType::cases()
                    )
                ),
            ],

            'proponent_name' => [
                'required',
                'string',
                'max:200',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:150',
            ],

            'contact_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'proponent_address' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'office_id' => [
                'nullable',
                'integer',
                'exists:offices,id',
            ],

            'fund_source_id' => [
                'nullable',
                'integer',
                'exists:fund_sources,id',
            ],

            'project_type_id' => [
                'required',
                'integer',
                'exists:project_types,id',
            ],

            'project_purpose_id' => [
                'required',
                'integer',
                'exists:project_purposes,id',
            ],

            'implementation_mode_id' => [
                'nullable',
                'integer',
                'exists:implementation_modes,id',
            ],

            'date_received' => [
                'nullable',
                'date',
            ],

            'record_status' => [
                'required',

                Rule::in(
                    array_map(
                        fn(ProjectRecordStatus $status) =>
                        $status->value,
                        ProjectRecordStatus::cases()
                    )
                ),
            ],

            'source_reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'province_id' => [
                'required',
                'integer',
                'exists:provinces,id',
            ],

            'municipality_id' => [
                'nullable',
                'integer',
                'exists:municipalities,id',
            ],

            'barangay_id' => [
                'nullable',
                'integer',
                'exists:barangays,id',
            ],

            'address_detail' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);
    }

    private function validateLocationHierarchy(): void
    {
        if ($this->municipality_id) {
            $municipality = Municipality::query()
                ->findOrFail($this->municipality_id);

            if (
                $municipality->province_id !==
                $this->province_id
            ) {
                throw ValidationException::withMessages([
                    'municipality_id' =>
                        'The selected municipality or city does not belong to the selected province.',
                ]);
            }
        }

        if ($this->barangay_id) {
            if (!$this->municipality_id) {
                throw ValidationException::withMessages([
                    'barangay_id' =>
                        'Select a municipality or city before selecting a barangay.',
                ]);
            }

            $barangay = Barangay::query()
                ->findOrFail($this->barangay_id);

            if (
                $barangay->municipality_id !==
                $this->municipality_id
            ) {
                throw ValidationException::withMessages([
                    'barangay_id' =>
                        'The selected barangay does not belong to the selected municipality or city.',
                ]);
            }
        }
    }

    private function proponentData(array $validated): array
    {
        return [
            'type' => $validated['proponent_type'],

            'name' =>
                trim($validated['proponent_name']),

            'contact_person' =>
                filled($validated['contact_person'] ?? null)
                ? trim($validated['contact_person'])
                : null,

            'contact_number' =>
                filled($validated['contact_number'] ?? null)
                ? trim($validated['contact_number'])
                : null,

            'email' =>
                filled($validated['email'] ?? null)
                ? strtolower(trim($validated['email']))
                : null,

            'address' =>
                filled($validated['proponent_address'] ?? null)
                ? trim($validated['proponent_address'])
                : null,

            'is_active' => true,
        ];
    }

    private function projectData(
        array $validated,
        bool $creating
    ): array {
        return [
            'fiscal_year' =>
                $validated['fiscal_year'],

            'project_code' =>
                filled($validated['project_code'] ?? null)
                ? strtoupper(
                    trim($validated['project_code'])
                )
                : null,

            'title' =>
                trim($validated['title']),

            'office_id' =>
                $validated['office_id'] ?? null,

            'fund_source_id' =>
                $validated['fund_source_id'] ?? null,

            'project_type_id' =>
                $validated['project_type_id'],

            'project_purpose_id' =>
                $validated['project_purpose_id'],

            'implementation_mode_id' =>
                $validated['implementation_mode_id']
                ?? null,

            'date_received' =>
                $validated['date_received'] ?? null,

            'record_status' =>
                $validated['record_status'],

            'source_reference' =>
                filled(
                    $validated['source_reference']
                    ?? null
                )
                ? trim(
                    $validated['source_reference']
                )
                : null,

            'remarks' =>
                filled($validated['remarks'] ?? null)
                ? trim($validated['remarks'])
                : null,

            'created_by' =>
                $creating
                ? auth()->id()
                : Project::query()
                    ->findOrFail(
                        $this->editingId
                    )
                    ->created_by,

            'updated_by' =>
                auth()->id(),
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm',
            'editingId',
            'project_code',
            'title',
            'proponent_name',
            'contact_person',
            'contact_number',
            'email',
            'proponent_address',
            'office_id',
            'fund_source_id',
            'project_type_id',
            'project_purpose_id',
            'implementation_mode_id',
            'date_received',
            'source_reference',
            'remarks',
            'province_id',
            'municipality_id',
            'barangay_id',
            'address_detail',
        ]);

        $this->fiscal_year = 2026;

        $this->proponent_type =
            ProponentType::Individual->value;

        $this->record_status =
            ProjectRecordStatus::Draft->value;

        $this->resetValidation();
    }
}