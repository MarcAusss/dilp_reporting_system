<div class="space-y-6">
    @if (session()->has('livelihood-status'))
        <x-ui.alert variant="success" title="Livelihood details">{{ session('livelihood-status') }}</x-ui.alert>
    @endif

    <x-projects.identity-card :project="$project" />

    <x-ui.card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Livelihood Classifications</h3>
                <p class="mt-1 text-xs text-slate-500">Mark one livelihood as primary when multiple classifications apply.</p>
            </div>
            @can('project-livelihoods.update')
                <x-ui.button type="button" wire:click="startCreate">Add Livelihood</x-ui.button>
            @endcan
        </div>
    </x-ui.card>

    @if ($showForm)
        <x-ui.card>
            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Livelihood Type *</label>
                        <select wire:model="livelihood_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            <option value="">Select livelihood</option>
                            @foreach ($livelihoodOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                        @error('livelihood_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Target Beneficiaries</label>
                        <input type="number" min="0" wire:model="target_beneficiaries" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('target_beneficiaries') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" wire:model="is_primary" class="rounded border-slate-300">
                    Primary livelihood for this project
                </label>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea rows="3" wire:model="description" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Remarks</label>
                    <textarea rows="3" wire:model="remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                    <x-ui.button type="button" variant="secondary" wire:click="cancel">Cancel</x-ui.button>
                    <x-ui.button type="submit">Save Livelihood</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Livelihood</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Target</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Description</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr wire:key="livelihood-{{ $record->id }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-800">{{ $record->livelihood->name }}</span>
                                    @if ($record->is_primary)<x-ui.badge variant="info">Primary</x-ui.badge>@endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ number_format($record->target_beneficiaries) }}</td>
                            <td class="max-w-xl px-5 py-4 text-xs leading-5 text-slate-500">{{ $record->description ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @can('project-livelihoods.update')
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $record->id }})" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">Edit</button>
                                        <button wire:click="remove({{ $record->id }})" wire:confirm="Remove this livelihood entry?" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-red-600">Remove</button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-14 text-center text-sm text-slate-500">No livelihood classification recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
