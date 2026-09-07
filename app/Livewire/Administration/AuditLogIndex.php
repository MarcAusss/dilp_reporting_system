<?php

namespace App\Livewire\Administration;

use App\Enums\PermissionName;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $category = 'all';

    public function mount(): void
    {
        Gate::authorize(PermissionName::AuditLogsView->value);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::AuditLogsView->value);

        $logs = AuditLog::query()
            ->with('user')
            ->when($this->category !== 'all', fn (Builder $query) => $query->where('category', $this->category))
            ->when(filled($this->search), function (Builder $query): void {
                $search = trim($this->search);
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('auditable_type', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(25);

        $categories = AuditLog::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        return view('livewire.administration.audit-log-index', [
            'logs' => $logs,
            'categories' => $categories,
        ]);
    }
}
