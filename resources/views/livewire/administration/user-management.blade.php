<div class="space-y-7">
    <x-ui.page-header
        title="User Management"
        eyebrow="Administration"
        description="Create accounts, assign system roles, reset passwords, and activate or deactivate access."
    >
        <x-slot:actions>
            @can('users.create')
                <x-ui.button wire:click="startCreate">
                    Add User
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if (session()->has('user-status'))
        <x-ui.alert variant="success" title="User Management">
            {{ session('user-status') }}
        </x-ui.alert>
    @endif

    @if ($showForm)
        <x-ui.card>
            <h2 class="text-sm font-bold text-slate-900">
                {{ $editingId ? 'Edit User' : 'Create User' }}
            </h2>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-slate-600">
                        Name
                    </label>
                    <input
                        wire:model="name"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                    >
                    @error('name')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-600">
                        Email
                    </label>
                    <input
                        type="email"
                        wire:model="email"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                    >
                    @error('email')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-600">
                        Role
                    </label>
                    <select
                        wire:model="role"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                    >
                        @foreach ($roles as $item)
                            <option value="{{ $item->value }}">
                                {{ $item->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <div>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="is_active">
                            Active account
                        </label>
                        @error('is_active')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-600">
                        {{ $editingId ? 'New Password (optional)' : 'Password' }}
                    </label>
                    <input
                        type="password"
                        wire:model="password"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                    >
                    @error('password')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-600">
                        Confirm Password
                    </label>
                    <input
                        type="password"
                        wire:model="password_confirmation"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                    >
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <x-ui.button wire:click="save">
                    Save User
                </x-ui.button>

                <x-ui.button variant="secondary" wire:click="cancel">
                    Cancel
                </x-ui.button>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card :padding="false">
        <div class="border-b border-slate-100 px-5 py-5">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search user by name or email..."
                class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
            >
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">
                            User
                        </th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">
                            Role
                        </th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">
                            Status
                        </th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-bold text-slate-900">
                                    {{ $user->name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $user->email }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-xs font-semibold">
                                {{ \App\Enums\UserRole::tryFrom($user->getRoleNames()->first() ?? '')?->label() ?? 'Unassigned' }}
                            </td>

                            <td class="px-5 py-4">
                                <x-ui.badge :variant="$user->is_active ? 'success' : 'danger'">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </td>

                            <td class="px-5 py-4 text-right">
                                @can('users.update')
                                    <button
                                        wire:click="edit({{ $user->id }})"
                                        class="text-xs font-bold text-[#164b73]"
                                    >
                                        Edit
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
