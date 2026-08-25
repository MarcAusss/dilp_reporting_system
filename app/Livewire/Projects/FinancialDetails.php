<?php

namespace App\Livewire\Projects;

use App\Enums\PermissionName;
use App\Models\Project;
use App\Models\ProjectFinancial;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class FinancialDetails extends Component
{
    public int $projectId;

    public string $equipment_materials_tools = '0.00';

    public string $insurance = '0.00';

    public string $training = '0.00';

    public string $proponent_partner_equity = '0.00';

    public string $beneficiary_equity = '0.00';

    public string $remarks = '';

    public function mount(int $projectId): void
    {
        Gate::authorize(
            PermissionName::ProjectFinancialsView->value
        );

        Project::query()
            ->findOrFail($projectId);

        $this->projectId = $projectId;

        $this->loadFinancial();
    }

    public function save(): void
    {
        Gate::authorize(
            PermissionName::ProjectFinancialsUpdate->value
        );

        $validated = $this->validate([
            'equipment_materials_tools' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'insurance' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'training' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'proponent_partner_equity' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'beneficiary_equity' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        $financial = ProjectFinancial::query()
            ->firstOrNew([
                'project_id' => $this->projectId,
            ]);

        if (!$financial->exists) {
            $financial->created_by =
                auth()->id();
        }

        $financial->fill([
            'equipment_materials_tools' =>
                $validated['equipment_materials_tools'],

            'insurance' =>
                $validated['insurance'],

            'training' =>
                $validated['training'],

            'proponent_partner_equity' =>
                $validated['proponent_partner_equity'],

            'beneficiary_equity' =>
                $validated['beneficiary_equity'],

            'remarks' =>
                filled($validated['remarks'] ?? null)
                ? trim($validated['remarks'])
                : null,

            'updated_by' =>
                auth()->id(),
        ]);

        $financial->save();

        $this->loadFinancial();

        session()->flash(
            'financial-status',
            'Project financial details saved successfully.'
        );
    }

    public function render(): View
    {
        Gate::authorize(
            PermissionName::ProjectFinancialsView->value
        );

        $project = Project::query()
            ->with([
                'proponent',
                'projectType',
                'projectPurpose',
                'primaryLocation.province',
                'primaryLocation.municipality',
            ])
            ->findOrFail($this->projectId);

        $preview = new ProjectFinancial([
            'equipment_materials_tools' =>
                $this->numericValue(
                    $this->equipment_materials_tools
                ),

            'insurance' =>
                $this->numericValue(
                    $this->insurance
                ),

            'training' =>
                $this->numericValue(
                    $this->training
                ),

            'proponent_partner_equity' =>
                $this->numericValue(
                    $this->proponent_partner_equity
                ),

            'beneficiary_equity' =>
                $this->numericValue(
                    $this->beneficiary_equity
                ),
        ]);

        return view(
            'livewire.projects.financial-details',
            [
                'project' => $project,

                'doleShare' =>
                    $preview->doleShare(),

                'totalEquity' =>
                    $preview->totalEquity(),

                'totalProjectCost' =>
                    $preview->totalProjectCost(),

                'equityPercentage' =>
                    $preview->equityPercentage(),
            ]
        );
    }

    private function loadFinancial(): void
    {
        $financial = ProjectFinancial::query()
            ->where(
                'project_id',
                $this->projectId
            )
            ->first();

        if (!$financial) {
            $this->equipment_materials_tools =
                '0.00';

            $this->insurance =
                '0.00';

            $this->training =
                '0.00';

            $this->proponent_partner_equity =
                '0.00';

            $this->beneficiary_equity =
                '0.00';

            $this->remarks =
                '';

            return;
        }

        $this->equipment_materials_tools =
            number_format(
                (float) $financial->equipment_materials_tools,
                2,
                '.',
                ''
            );

        $this->insurance =
            number_format(
                (float) $financial->insurance,
                2,
                '.',
                ''
            );

        $this->training =
            number_format(
                (float) $financial->training,
                2,
                '.',
                ''
            );

        $this->proponent_partner_equity =
            number_format(
                (float) $financial->proponent_partner_equity,
                2,
                '.',
                ''
            );

        $this->beneficiary_equity =
            number_format(
                (float) $financial->beneficiary_equity,
                2,
                '.',
                ''
            );

        $this->remarks =
            $financial->remarks ?? '';
    }

    private function numericValue(
        mixed $value
    ): float {
        if (!is_numeric($value)) {
            return 0;
        }

        return round(
            (float) $value,
            2
        );
    }
}