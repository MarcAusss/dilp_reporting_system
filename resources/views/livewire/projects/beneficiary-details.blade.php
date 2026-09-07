<div class="space-y-6">
    @if (session()->has('beneficiary-status'))
        <x-ui.alert variant="success" title="Beneficiary records">
            {{ session('beneficiary-status') }}
        </x-ui.alert>
    @endif

    <x-projects.identity-card :project="$project" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Total Beneficiaries</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($totalBeneficiaries) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Active Beneficiaries</p>
            <p class="mt-3 text-2xl font-bold text-[#164b73]">{{ number_format($activeBeneficiaries) }}</p>
        </x-ui.card>
    </div>

    <x-ui.card>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Beneficiary Registry</h3>
                <p class="mt-1 text-xs text-slate-500">One beneficiary may be tagged under multiple priority sectors.</p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search beneficiary..."
                    class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">

                @can('project-beneficiaries.update')
                    <x-ui.button type="button" wire:click="startCreate">Add Beneficiary</x-ui.button>
                @endcan
            </div>
        </div>
    </x-ui.card>

    @if ($showForm)
        <x-ui.card>
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900">{{ $editingId ? 'Edit Beneficiary' : 'New Beneficiary' }}</h3>
                <p class="mt-1 text-xs text-slate-500">Record identity, contact information, and applicable beneficiary sectors.</p>
            </div>

            <form wire:submit="save" class="mt-6 space-y-6">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Reference No.</label>
                        <input type="text" wire:model="reference_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('reference_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">First Name *</label>
                        <input type="text" wire:model="first_name" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Middle Name</label>
                        <input type="text" wire:model="middle_name" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Last Name *</label>
                        <input type="text" wire:model="last_name" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Suffix</label>
                        <input type="text" wire:model="suffix" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Sex</label>
                        <select wire:model="sex" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            <option value="">Not specified</option>
                            @foreach ($sexOptions as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Birth Date</label>
                        <input type="date" wire:model="birth_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('birth_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select wire:model="is_active" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Contact Number</label>
                        <input type="text" wire:model="contact_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" wire:model="email" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Address</label>
                        <input type="text" wire:model="address" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <label class="block text-sm font-semibold text-slate-700">Beneficiary Sectors</label>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($sectors as $sector)
                            <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-xs text-slate-700">
                                <input type="checkbox" value="{{ $sector->id }}" wire:model="sector_ids" class="mt-0.5 rounded border-slate-300">
                                <span>{{ $sector->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('sector_ids.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Remarks</label>
                    <textarea rows="3" wire:model="remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                    <x-ui.button type="button" variant="secondary" wire:click="cancel">Cancel</x-ui.button>
                    <x-ui.button type="submit">Save Beneficiary</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Beneficiary</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Sex / Birth Date</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Sectors</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($beneficiaries as $beneficiary)
                        <tr wire:key="beneficiary-{{ $beneficiary->id }}" class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-800">{{ $beneficiary->fullName() }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $beneficiary->reference_number ?: 'No reference number' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600">
                                <p>{{ $beneficiary->sex?->label() ?? '—' }}</p>
                                <p class="mt-1 text-slate-400">{{ $beneficiary->birth_date?->format('M d, Y') ?? '—' }}</p>
                            </td>
                            <td class="min-w-72 px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($beneficiary->sectors as $sector)
                                        <x-ui.badge>{{ $sector->name }}</x-ui.badge>
                                    @empty
                                        <span class="text-xs text-slate-400">No sector tag</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <x-ui.badge :variant="$beneficiary->is_active ? 'success' : 'neutral'">
                                    {{ $beneficiary->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-5 py-4">
                                @can('project-beneficiaries.update')
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="edit({{ $beneficiary->id }})" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Edit</button>
                                        <button type="button" wire:click="toggleActive({{ $beneficiary->id }})" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-amber-600 hover:bg-amber-50">
                                            {{ $beneficiary->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">No beneficiaries recorded for this project.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($beneficiaries->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $beneficiaries->links() }}</div>
        @endif
    </x-ui.card>
</div>
