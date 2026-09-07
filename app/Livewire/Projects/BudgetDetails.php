<?php

namespace App\Livewire\Projects;

use App\Enums\PermissionName;
use App\Enums\ProjectBudgetComponent;
use App\Models\Project;
use App\Models\ProjectBudgetItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BudgetDetails extends Component
{
    public int $projectId;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $component = 'equipment_materials_tools';

    public string $item_description = '';

    public string $quantity = '1.00';

    public string $unit = '';

    public string $unit_cost = '0.00';

    public string $remarks = '';

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectBudgetItemsView->value);
        Project::query()->findOrFail($projectId);
        $this->projectId = $projectId;
    }

    public function startCreate(): void
    {
        Gate::authorize(PermissionName::ProjectBudgetItemsUpdate->value);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(PermissionName::ProjectBudgetItemsUpdate->value);

        $item = ProjectBudgetItem::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingId = $item->id;
        $this->component = $item->component->value;
        $this->item_description = $item->item_description;
        $this->quantity = number_format((float) $item->quantity, 2, '.', '');
        $this->unit = $item->unit ?? '';
        $this->unit_cost = number_format((float) $item->unit_cost, 2, '.', '');
        $this->remarks = $item->remarks ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize(PermissionName::ProjectBudgetItemsUpdate->value);

        $validated = $this->validate([
            'component' => [
                'required',
                Rule::in(array_map(
                    fn (ProjectBudgetComponent $component): string => $component->value,
                    ProjectBudgetComponent::cases()
                )),
            ],
            'item_description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'unit' => ['nullable', 'string', 'max:50'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $item = $this->editingId
            ? ProjectBudgetItem::query()
                ->where('project_id', $this->projectId)
                ->findOrFail($this->editingId)
            : new ProjectBudgetItem([
                'project_id' => $this->projectId,
                'created_by' => auth()->id(),
            ]);

        $item->fill([
            'component' => $validated['component'],
            'item_description' => trim($validated['item_description']),
            'quantity' => $validated['quantity'],
            'unit' => $this->nullableTrim($validated['unit'] ?? null),
            'unit_cost' => $validated['unit_cost'],
            'remarks' => $this->nullableTrim($validated['remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        session()->flash('budget-status', 'Budget item saved successfully.');
        $this->resetForm();
    }

    public function remove(int $id): void
    {
        Gate::authorize(PermissionName::ProjectBudgetItemsUpdate->value);

        ProjectBudgetItem::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id)
            ->delete();

        session()->flash('budget-status', 'Budget item removed.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectBudgetItemsView->value);

        $project = Project::query()
            ->with([
                'proponent',
                'projectType',
                'projectPurpose',
                'financial',
                'budgetItems',
            ])
            ->findOrFail($this->projectId);

        $items = ProjectBudgetItem::query()
            ->where('project_id', $this->projectId)
            ->orderBy('component')
            ->orderBy('id')
            ->get();

        $componentTotals = [];
        $reconciliation = [];

        foreach (ProjectBudgetComponent::cases() as $component) {
            $detailTotal = round(
                (float) $items
                    ->filter(fn (ProjectBudgetItem $item): bool => $item->component === $component)
                    ->sum(fn (ProjectBudgetItem $item): float => (float) $item->amount),
                2
            );

            $financialTotal = round(
                (float) ($project->financial?->{$component->financialColumn()} ?? 0),
                2
            );

            $componentTotals[$component->value] = $detailTotal;
            $reconciliation[$component->value] = [
                'detail' => $detailTotal,
                'financial' => $financialTotal,
                'difference' => round($detailTotal - $financialTotal, 2),
            ];
        }

        return view('livewire.projects.budget-details', [
            'project' => $project,
            'items' => $items,
            'components' => ProjectBudgetComponent::cases(),
            'componentTotals' => $componentTotals,
            'reconciliation' => $reconciliation,
            'detailTotal' => round(array_sum($componentTotals), 2),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->component = ProjectBudgetComponent::EquipmentMaterialsTools->value;
        $this->item_description = '';
        $this->quantity = '1.00';
        $this->unit = '';
        $this->unit_cost = '0.00';
        $this->remarks = '';
        $this->showForm = false;
        $this->resetValidation();
    }

    private function nullableTrim(mixed $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }
}
