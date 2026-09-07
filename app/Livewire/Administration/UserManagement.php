<?php

namespace App\Livewire\Administration;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'gip';
    public bool $is_active = true;
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        Gate::authorize(PermissionName::UsersView->value);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        Gate::authorize(PermissionName::UsersCreate->value);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize(PermissionName::UsersUpdate->value);
        $user = User::query()->with('roles')->findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->getRoleNames()->first() ?? UserRole::GIP->value;
        $this->is_active = $user->is_active;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize($this->editingId ? PermissionName::UsersUpdate->value : PermissionName::UsersCreate->value);

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:12', 'confirmed'],
        ];

        $validated = $this->validate($rules);
        $editing = $this->editingId ? User::query()->with('roles')->findOrFail($this->editingId) : null;

        if ($editing && $editing->id === auth()->id() && ! $validated['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own signed-in account.']);
        }

        if ($editing && $editing->hasRole(UserRole::SuperAdmin->value)) {
            $removingSuperAdmin = $validated['role'] !== UserRole::SuperAdmin->value || ! $validated['is_active'];
            if ($removingSuperAdmin && $this->activeSuperAdminCount() <= 1) {
                throw ValidationException::withMessages(['role' => 'The system must retain at least one active Super Admin account.']);
            }
        }

        $data = [
            'name' => trim($validated['name']),
            'email' => mb_strtolower(trim($validated['email'])),
            'is_active' => (bool) $validated['is_active'],
        ];
        if (filled($validated['password'] ?? null)) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($editing) {
            $old = [
                'name' => $editing->name,
                'email' => $editing->email,
                'role' => $editing->getRoleNames()->first(),
                'is_active' => $editing->is_active,
            ];
            $editing->update($data);
            $editing->syncRoles([$validated['role']]);
            app(AuditLogService::class)->record('user', 'updated', 'Updated user account '.$editing->email.'.', $editing, $old, [
                'name' => $editing->name,
                'email' => $editing->email,
                'role' => $validated['role'],
                'is_active' => $editing->is_active,
                'password_changed' => filled($validated['password'] ?? null),
            ]);
            session()->flash('user-status', 'User account updated successfully.');
        } else {
            $user = User::query()->create($data);
            $user->assignRole($validated['role']);
            app(AuditLogService::class)->record('user', 'created', 'Created user account '.$user->email.'.', $user, null, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $validated['role'],
                'is_active' => $user->is_active,
            ]);
            session()->flash('user-status', 'User account created successfully.');
        }

        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::UsersView->value);

        $users = User::query()
            ->with('roles')
            ->when(filled($this->search), function (Builder $query): void {
                $search = trim($this->search);
                $query->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.administration.user-management', [
            'users' => $users,
            'roles' => UserRole::cases(),
        ]);
    }

    private function activeSuperAdminCount(): int
    {
        return User::query()->where('is_active', true)->role(UserRole::SuperAdmin->value)->count();
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->role = UserRole::GIP->value;
        $this->is_active = true;
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetValidation();
    }
}
