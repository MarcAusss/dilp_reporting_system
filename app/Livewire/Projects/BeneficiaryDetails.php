<?php

namespace App\Livewire\Projects;

use App\Enums\BeneficiarySex;
use App\Enums\PermissionName;
use App\Models\BeneficiarySector;
use App\Models\Project;
use App\Models\ProjectBeneficiary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class BeneficiaryDetails extends Component
{
    use WithPagination;

    public int $projectId;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $reference_number = '';

    public string $first_name = '';

    public string $middle_name = '';

    public string $last_name = '';

    public string $suffix = '';

    public string $sex = '';

    public string $birth_date = '';

    public string $contact_number = '';

    public string $email = '';

    public string $address = '';

    public bool $is_active = true;

    public string $remarks = '';

    public array $sector_ids = [];

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectBeneficiariesView->value);

        Project::query()->findOrFail($projectId);

        $this->projectId = $projectId;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        Gate::authorize(PermissionName::ProjectBeneficiariesUpdate->value);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(PermissionName::ProjectBeneficiariesUpdate->value);

        $beneficiary = ProjectBeneficiary::query()
            ->with('sectors')
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingId = $beneficiary->id;
        $this->reference_number = $beneficiary->reference_number ?? '';
        $this->first_name = $beneficiary->first_name;
        $this->middle_name = $beneficiary->middle_name ?? '';
        $this->last_name = $beneficiary->last_name;
        $this->suffix = $beneficiary->suffix ?? '';
        $this->sex = $beneficiary->sex?->value ?? '';
        $this->birth_date = $beneficiary->birth_date?->format('Y-m-d') ?? '';
        $this->contact_number = $beneficiary->contact_number ?? '';
        $this->email = $beneficiary->email ?? '';
        $this->address = $beneficiary->address ?? '';
        $this->is_active = $beneficiary->is_active;
        $this->remarks = $beneficiary->remarks ?? '';
        $this->sector_ids = $beneficiary->sectors
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(PermissionName::ProjectBeneficiariesUpdate->value);

        $validated = $this->validate([
            'reference_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('project_beneficiaries', 'reference_number')
                    ->where(fn ($query) => $query->where('project_id', $this->projectId))
                    ->ignore($this->editingId),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'sex' => [
                'nullable',
                Rule::in(array_map(
                    fn (BeneficiarySex $sex): string => $sex->value,
                    BeneficiarySex::cases()
                )),
            ],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'sector_ids' => ['array'],
            'sector_ids.*' => [
                'integer',
                Rule::exists('beneficiary_sectors', 'id')->where('is_active', true),
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $beneficiary = $this->editingId
                ? ProjectBeneficiary::query()
                    ->where('project_id', $this->projectId)
                    ->findOrFail($this->editingId)
                : new ProjectBeneficiary([
                    'project_id' => $this->projectId,
                    'created_by' => auth()->id(),
                ]);

            $beneficiary->fill([
                'reference_number' => $this->nullableTrim($validated['reference_number'] ?? null),
                'first_name' => trim($validated['first_name']),
                'middle_name' => $this->nullableTrim($validated['middle_name'] ?? null),
                'last_name' => trim($validated['last_name']),
                'suffix' => $this->nullableTrim($validated['suffix'] ?? null),
                'sex' => filled($validated['sex'] ?? null) ? $validated['sex'] : null,
                'birth_date' => filled($validated['birth_date'] ?? null) ? $validated['birth_date'] : null,
                'contact_number' => $this->nullableTrim($validated['contact_number'] ?? null),
                'email' => filled($validated['email'] ?? null)
                    ? strtolower(trim($validated['email']))
                    : null,
                'address' => $this->nullableTrim($validated['address'] ?? null),
                'is_active' => $validated['is_active'],
                'remarks' => $this->nullableTrim($validated['remarks'] ?? null),
                'updated_by' => auth()->id(),
            ]);

            $beneficiary->save();
            $beneficiary->sectors()->sync($validated['sector_ids'] ?? []);
        });

        session()->flash(
            'beneficiary-status',
            $this->editingId
                ? 'Beneficiary record updated successfully.'
                : 'Beneficiary added successfully.'
        );

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize(PermissionName::ProjectBeneficiariesUpdate->value);

        $beneficiary = ProjectBeneficiary::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $beneficiary->update([
            'is_active' => ! $beneficiary->is_active,
            'updated_by' => auth()->id(),
        ]);

        session()->flash('beneficiary-status', 'Beneficiary status updated.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectBeneficiariesView->value);

        $project = Project::query()
            ->with(['proponent', 'projectType', 'projectPurpose'])
            ->findOrFail($this->projectId);

        $beneficiaries = ProjectBeneficiary::query()
            ->with('sectors')
            ->where('project_id', $this->projectId)
            ->when(filled($this->search), function ($query): void {
                $search = trim($this->search);

                $query->where(function ($query) use ($search): void {
                    $query->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('livewire.projects.beneficiary-details', [
            'project' => $project,
            'beneficiaries' => $beneficiaries,
            'sectors' => BeneficiarySector::query()->active()->ordered()->get(),
            'sexOptions' => BeneficiarySex::cases(),
            'totalBeneficiaries' => ProjectBeneficiary::query()
                ->where('project_id', $this->projectId)
                ->count(),
            'activeBeneficiaries' => ProjectBeneficiary::query()
                ->where('project_id', $this->projectId)
                ->where('is_active', true)
                ->count(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->reference_number = '';
        $this->first_name = '';
        $this->middle_name = '';
        $this->last_name = '';
        $this->suffix = '';
        $this->sex = '';
        $this->birth_date = '';
        $this->contact_number = '';
        $this->email = '';
        $this->address = '';
        $this->is_active = true;
        $this->remarks = '';
        $this->sector_ids = [];
        $this->showForm = false;
        $this->resetValidation();
    }

    private function nullableTrim(mixed $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }
}
