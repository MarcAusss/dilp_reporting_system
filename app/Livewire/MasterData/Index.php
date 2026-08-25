<?php

namespace App\Livewire\MasterData;

use App\Enums\PermissionName;
use App\Models\BeneficiarySector;
use App\Models\ConvergenceProgram;
use App\Models\FundSource;
use App\Models\ImplementationMode;
use App\Models\Livelihood;
use App\Models\Office;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Province;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $category = 'project-types';

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize(PermissionName::MasterDataView->value);

        if (!array_key_exists($this->category, $this->categories())) {
            $this->category = 'project-types';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectCategory(string $category): void
    {
        Gate::authorize(PermissionName::MasterDataView->value);

        abort_unless(
            array_key_exists($category, $this->categories()),
            404
        );

        $this->category = $category;
        $this->search = '';

        $this->resetForm();
        $this->resetPage();
    }

    public function startCreate(): void
    {
        Gate::authorize(PermissionName::MasterDataCreate->value);

        $this->resetForm();

        $this->showForm = true;
        $this->is_active = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(PermissionName::MasterDataUpdate->value);

        $model = $this->findRecord($id);

        $this->editingId = $model->getKey();
        $this->name = $model->name;
        $this->code = $model->code ?? '';
        $this->description = $model->description ?? '';
        $this->sort_order = $model->sort_order;
        $this->is_active = $model->is_active;

        $this->showForm = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            Gate::authorize(PermissionName::MasterDataUpdate->value);
        } else {
            Gate::authorize(PermissionName::MasterDataCreate->value);
        }

        $class = $this->modelClass();

        /** @var Model $model */
        $model = new $class();

        $table = $model->getTable();

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique($table, 'name')
                    ->ignore($this->editingId),
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique($table, 'code')
                    ->ignore($this->editingId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);

        $data = [
            'name' => trim($validated['name']),

            'code' => filled($validated['code'])
                ? strtoupper(trim($validated['code']))
                : null,

            'description' => filled($validated['description'])
                ? trim($validated['description'])
                : null,

            'sort_order' => $validated['sort_order'],
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingId) {
            $record = $class::query()
                ->findOrFail($this->editingId);

            $record->update($data);

            session()->flash(
                'master-data-status',
                'Master data record updated successfully.'
            );
        } else {
            $class::query()->create($data);

            session()->flash(
                'master-data-status',
                'Master data record created successfully.'
            );
        }

        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        Gate::authorize(PermissionName::MasterDataToggle->value);

        $record = $this->findRecord($id);

        $record->update([
            'is_active' => !$record->is_active,
        ]);

        session()->flash(
            'master-data-status',
            $record->is_active
            ? 'Record activated successfully.'
            : 'Record deactivated successfully.'
        );
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::MasterDataView->value);

        $class = $this->modelClass();

        $items = $class::query()
            ->when(
                filled($this->search),
                function ($query): void {
                    $search = trim($this->search);

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
                }
            )
            ->ordered()
            ->paginate(15);

        return view('livewire.master-data.index', [
            'items' => $items,
            'categories' => $this->categories(),
            'currentCategory' => $this->categories()[$this->category],
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm',
            'editingId',
            'name',
            'code',
            'description',
            'sort_order',
        ]);

        $this->is_active = true;

        $this->resetValidation();
    }

    private function findRecord(int $id): Model
    {
        $class = $this->modelClass();

        return $class::query()->findOrFail($id);
    }

    private function modelClass(): string
    {
        return $this->categories()[$this->category]['model'];
    }

    private function categories(): array
    {
        return [
            'offices' => [
                'label' => 'Offices',
                'model' => Office::class,
                'description' => 'DOLE offices and organizational units.',
            ],

            'provinces' => [
                'label' => 'Provinces',
                'model' => Province::class,
                'description' => 'Province-level geographic references.',
            ],

            'fund-sources' => [
                'label' => 'Fund Sources',
                'model' => FundSource::class,
                'description' => 'Funding sources used by DILP projects.',
            ],

            'project-types' => [
                'label' => 'Project Types',
                'model' => ProjectType::class,
                'description' => 'Individual and group project classifications.',
            ],

            'project-purposes' => [
                'label' => 'Project Purposes',
                'model' => ProjectPurpose::class,
                'description' => 'Formation, enhancement, and future classifications.',
            ],

            'implementation-modes' => [
                'label' => 'Implementation Modes',
                'model' => ImplementationMode::class,
                'description' => 'Approved modes of project implementation.',
            ],

            'beneficiary-sectors' => [
                'label' => 'Beneficiary Sectors',
                'model' => BeneficiarySector::class,
                'description' => 'Priority and special beneficiary sectors.',
            ],

            'livelihoods' => [
                'label' => 'Livelihood Types',
                'model' => Livelihood::class,
                'description' => 'Livelihood and enterprise classifications.',
            ],

            'convergence-programs' => [
                'label' => 'Convergence Programs',
                'model' => ConvergenceProgram::class,
                'description' => 'Convergence, referral, and special programs.',
            ],
        ];
    }
}