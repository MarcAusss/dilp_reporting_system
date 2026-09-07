<div class="space-y-6">
    @if (session()->has('convergence-status'))
        <x-ui.alert variant="success" title="Convergence details">{{ session('convergence-status') }}</x-ui.alert>
    @endif

    <x-projects.identity-card :project="$project" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Interventions</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($records->count()) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Recorded Assistance</p>
            <p class="mt-3 text-2xl font-bold text-[#164b73]">₱{{ number_format($totalAssistance, 2) }}</p>
        </x-ui.card>
    </div>

    <x-ui.card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Convergence Program Records</h3>
                <p class="mt-1 text-xs text-slate-500">Partner and referral assistance linked to the project.</p>
            </div>
            @can('project-convergence.update')
                <x-ui.button type="button" wire:click="startCreate">Add Convergence Record</x-ui.button>
            @endcan
        </div>
    </x-ui.card>

    @if ($showForm)
        <x-ui.card>
            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Program *</label>
                        <select wire:model="convergence_program_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            <option value="">Select program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                            @endforeach
                        </select>
                        @error('convergence_program_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Reference No.</label>
                        <input type="text" wire:model="reference_number" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Assistance Date</label>
                        <input type="date" wire:model="assistance_date" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Assistance Amount</label>
                        <input type="number" min="0" step="0.01" wire:model="assistance_amount" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Assistance Description</label>
                    <textarea rows="3" wire:model="assistance_description" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Remarks</label>
                    <textarea rows="3" wire:model="remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                    <x-ui.button type="button" variant="secondary" wire:click="cancel">Cancel</x-ui.button>
                    <x-ui.button type="submit">Save Convergence Record</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Program</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Reference / Date</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Assistance</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Amount</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr wire:key="convergence-{{ $record->id }}">
                            <td class="px-5 py-4 text-sm font-semibold text-slate-800">{{ $record->program->name }}</td>
                            <td class="px-5 py-4 text-xs text-slate-600">
                                <p>{{ $record->reference_number ?: '—' }}</p>
                                <p class="mt-1 text-slate-400">{{ $record->assistance_date?->format('M d, Y') ?? '—' }}</p>
                            </td>
                            <td class="max-w-xl px-5 py-4 text-xs leading-5 text-slate-500">{{ $record->assistance_description ?: '—' }}</td>
                            <td class="px-5 py-4 text-right text-sm font-semibold text-slate-800">₱{{ number_format((float) $record->assistance_amount, 2) }}</td>
                            <td class="px-5 py-4">
                                @can('project-convergence.update')
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $record->id }})" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">Edit</button>
                                        <button wire:click="remove({{ $record->id }})" wire:confirm="Remove this convergence record?" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-red-600">Remove</button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">No convergence records linked to this project.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
