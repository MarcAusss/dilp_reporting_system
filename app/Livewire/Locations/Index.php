<?php

namespace App\Livewire\Locations;

use App\Enums\PermissionName;
use App\Models\Barangay;
use App\Models\Municipality;
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

    public string $level = 'province';

    public string $search = '';

    public ?int $provinceFilter = null;

    public ?int $municipalityFilter = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public ?int $province_id = null;

    public ?int $municipality_id = null;

    public string $type = 'municipality';

    public function mount(): void
    {
        Gate::authorize(
            PermissionName::MasterDataView->value
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProvinceFilter(): void
    {
        $this->municipalityFilter = null;

        $this->resetPage();
    }

    public function updatedMunicipalityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProvinceId(): void
    {
        if ($this->level === 'barangay') {
            $this->municipality_id = null;
        }
    }

    public function selectLevel(string $level): void
    {
        abort_unless(
            in_array(
                $level,
                [
                    'province',
                    'municipality',
                    'barangay',
                ],
                true
            ),
            404
        );

        $this->level = $level;

        $this->search = '';
        $this->provinceFilter = null;
        $this->municipalityFilter = null;

        $this->resetForm();
        $this->resetPage();
    }

    public function startCreate(): void
    {
        Gate::authorize(
            PermissionName::MasterDataCreate->value
        );

        $this->resetForm();

        $this->showForm = true;
        $this->is_active = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(
            PermissionName::MasterDataUpdate->value
        );

        $record = $this->findRecord($id);

        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code ?? '';
        $this->description = $record->description ?? '';
        $this->is_active = $record->is_active;
        $this->sort_order = $record->sort_order;

        if ($record instanceof Municipality) {
            $this->province_id = $record->province_id;
            $this->type = $record->type;
        }

        if ($record instanceof Barangay) {
            $this->municipality_id = $record->municipality_id;

            $this->province_id =
                $record->municipality->province_id;
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(
            $this->editingId
            ? PermissionName::MasterDataUpdate->value
            : PermissionName::MasterDataCreate->value
        );

        $validated = match ($this->level) {
            'province' => $this->validateProvince(),

            'municipality' => $this->validateMunicipality(),

            'barangay' => $this->validateBarangay(),

            default => abort(404),
        };

        $data = [
            'name' => trim($validated['name']),

            'code' => filled($validated['code'] ?? null)
                ? strtoupper(trim($validated['code']))
                : null,

            'description' => filled(
                $validated['description'] ?? null
            )
                ? trim($validated['description'])
                : null,

            'sort_order' => $validated['sort_order'],

            'is_active' => $validated['is_active'],
        ];

        if ($this->level === 'municipality') {
            $data['province_id'] =
                $validated['province_id'];

            $data['type'] =
                $validated['type'];
        }

        if ($this->level === 'barangay') {
            $data['municipality_id'] =
                $validated['municipality_id'];
        }

        $class = $this->modelClass();

        if ($this->editingId) {
            $record = $class::query()
                ->findOrFail($this->editingId);

            $record->update($data);

            session()->flash(
                'location-status',
                'Location record updated successfully.'
            );
        } else {
            $class::query()->create($data);

            session()->flash(
                'location-status',
                'Location record created successfully.'
            );
        }

        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        Gate::authorize(
            PermissionName::MasterDataToggle->value
        );

        $record = $this->findRecord($id);

        /*
        |--------------------------------------------------------------------------
        | Protect parent records
        |--------------------------------------------------------------------------
        */

        if (
            $record instanceof Province
            && $record->is_active
            && $record->municipalities()
                ->where('is_active', true)
                ->exists()
        ) {
            session()->flash(
                'location-error',
                'This province cannot be deactivated while it has active municipalities or cities.'
            );

            return;
        }

        if (
            $record instanceof Municipality
            && $record->is_active
            && $record->barangays()
                ->where('is_active', true)
                ->exists()
        ) {
            session()->flash(
                'location-error',
                'This municipality or city cannot be deactivated while it has active barangays.'
            );

            return;
        }

        $record->update([
            'is_active' => !$record->is_active,
        ]);

        session()->flash(
            'location-status',
            $record->is_active
            ? 'Location activated successfully.'
            : 'Location deactivated successfully.'
        );
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(
            PermissionName::MasterDataView->value
        );

        $query = match ($this->level) {
            'province' => Province::query(),

            'municipality' => Municipality::query()
                ->with('province'),

            'barangay' => Barangay::query()
                ->with('municipality.province'),

            default => abort(404),
        };

        if (filled($this->search)) {
            $search = trim($this->search);

            $query->where(function ($query) use ($search): void {
                $query
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'code',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if (
            $this->level === 'municipality'
            && $this->provinceFilter
        ) {
            $query->where(
                'province_id',
                $this->provinceFilter
            );
        }

        if (
            $this->level === 'barangay'
            && $this->provinceFilter
        ) {
            $query->whereHas(
                'municipality',
                function ($query): void {
                    $query->where(
                        'province_id',
                        $this->provinceFilter
                    );
                }
            );
        }

        if (
            $this->level === 'barangay'
            && $this->municipalityFilter
        ) {
            $query->where(
                'municipality_id',
                $this->municipalityFilter
            );
        }

        $items = $query
            ->ordered()
            ->paginate(15);

        $provinces = Province::query()
            ->ordered()
            ->get();

        $municipalities = Municipality::query()
            ->with('province')
            ->when(
                $this->provinceFilter,
                fn($query) => $query->where(
                    'province_id',
                    $this->provinceFilter
                )
            )
            ->ordered()
            ->get();

        $formMunicipalities = Municipality::query()
            ->with('province')
            ->when(
                $this->province_id,
                fn($query) => $query->where(
                    'province_id',
                    $this->province_id
                )
            )
            ->ordered()
            ->get();

        return view(
            'livewire.locations.index',
            [
                'items' => $items,
                'provinces' => $provinces,
                'municipalities' => $municipalities,
                'formMunicipalities' => $formMunicipalities,
            ]
        );
    }

    private function validateProvince(): array
    {
        return $this->validate([
            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'provinces',
                    'name'
                )->ignore($this->editingId),
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'provinces',
                    'code'
                )->ignore($this->editingId),
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
    }

    private function validateMunicipality(): array
    {
        return $this->validate([
            'province_id' => [
                'required',
                'integer',
                'exists:provinces,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'municipalities',
                    'name'
                )
                    ->where(
                        fn($query) => $query->where(
                            'province_id',
                            $this->province_id
                        )
                    )
                    ->ignore($this->editingId),
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'municipalities',
                    'code'
                )->ignore($this->editingId),
            ],

            'type' => [
                'required',
                Rule::in([
                    'municipality',
                    'city',
                ]),
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
    }

    private function validateBarangay(): array
    {
        return $this->validate([
            'municipality_id' => [
                'required',
                'integer',
                'exists:municipalities,id',
            ],

            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'barangays',
                    'name'
                )
                    ->where(
                        fn($query) => $query->where(
                            'municipality_id',
                            $this->municipality_id
                        )
                    )
                    ->ignore($this->editingId),
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'barangays',
                    'code'
                )->ignore($this->editingId),
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
    }

    private function resetForm(): void
    {
        $this->reset([
            'showForm',
            'editingId',
            'name',
            'code',
            'description',
            'province_id',
            'municipality_id',
            'sort_order',
        ]);

        $this->type = 'municipality';
        $this->is_active = true;

        $this->resetValidation();
    }

    private function findRecord(int $id): Model
    {
        $class = $this->modelClass();

        return $class::query()
            ->findOrFail($id);
    }

    private function modelClass(): string
    {
        return match ($this->level) {
            'province' => Province::class,
            'municipality' => Municipality::class,
            'barangay' => Barangay::class,

            default => abort(404),
        };
    }
}