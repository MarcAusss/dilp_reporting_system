<div class="space-y-6">
    @if (session()->has('budget-status'))
        <x-ui.alert variant="success" title="Budget items">{{ session('budget-status') }}</x-ui.alert>
    @endif

    <x-projects.identity-card :project="$project" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Detailed Budget Total</p>
            <p class="mt-3 text-xl font-bold text-[#164b73]">₱{{ number_format($detailTotal, 2) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Financial Total</p>
            <p class="mt-3 text-xl font-bold text-slate-950">₱{{ number_format($project->financial?->totalProjectCost() ?? 0, 2) }}</p>
        </x-ui.card>
        <x-ui.card>
            @php $overallDifference = round($detailTotal - ($project->financial?->totalProjectCost() ?? 0), 2); @endphp
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Difference</p>
            <p @class(['mt-3 text-xl font-bold', 'text-emerald-700' => abs($overallDifference) < 0.01, 'text-amber-700' => abs($overallDifference) >= 0.01])>
                ₱{{ number_format($overallDifference, 2) }}
            </p>
        </x-ui.card>
    </div>

    <x-ui.card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Budget Reconciliation</h3>
                <p class="mt-1 text-xs text-slate-500">Detailed line items are compared with the existing aggregate financial record.</p>
            </div>
            @can('project-budget-items.update')
                <x-ui.button type="button" wire:click="startCreate">Add Budget Item</x-ui.button>
            @endcan
        </div>

        <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Component</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Detail</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Financial</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Difference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($components as $option)
                        @php $row = $reconciliation[$option->value]; @endphp
                        <tr>
                            <td class="px-4 py-3 text-xs font-semibold text-slate-700">{{ $option->label() }}</td>
                            <td class="px-4 py-3 text-right text-xs text-slate-700">₱{{ number_format($row['detail'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-xs text-slate-700">₱{{ number_format($row['financial'], 2) }}</td>
                            <td @class(['px-4 py-3 text-right text-xs font-semibold', 'text-emerald-700' => abs($row['difference']) < 0.01, 'text-amber-700' => abs($row['difference']) >= 0.01])>
                                ₱{{ number_format($row['difference'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if ($showForm)
        <x-ui.card>
            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Financial Component *</label>
                        <select wire:model="component" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                            @foreach ($components as $option)
                                <option value="{{ $option->value }}">{{ $option->groupLabel() }} — {{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-3">
                        <label class="block text-sm font-medium text-slate-700">Item Description *</label>
                        <input type="text" wire:model="item_description" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('item_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Quantity *</label>
                        <input type="number" min="0.01" step="0.01" wire:model="quantity" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Unit</label>
                        <input type="text" wire:model="unit" placeholder="set, pc, lot..." class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Unit Cost *</label>
                        <input type="number" min="0" step="0.01" wire:model="unit_cost" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        @error('unit_cost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Preview Amount</label>
                        <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-right text-sm font-semibold text-slate-700">
                            ₱{{ number_format((is_numeric($quantity) ? (float) $quantity : 0) * (is_numeric($unit_cost) ? (float) $unit_cost : 0), 2) }}
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Remarks</label>
                    <textarea rows="3" wire:model="remarks" class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                    <x-ui.button type="button" variant="secondary" wire:click="cancel">Cancel</x-ui.button>
                    <x-ui.button type="submit">Save Budget Item</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Component</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Item</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Qty</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Unit Cost</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Amount</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr wire:key="budget-item-{{ $item->id }}">
                            <td class="px-5 py-4"><x-ui.badge>{{ $item->component->label() }}</x-ui.badge></td>
                            <td class="px-5 py-4 text-sm font-medium text-slate-700">{{ $item->item_description }}</td>
                            <td class="px-5 py-4 text-right text-xs text-slate-600">{{ number_format((float) $item->quantity, 2) }} {{ $item->unit }}</td>
                            <td class="px-5 py-4 text-right text-xs text-slate-600">₱{{ number_format((float) $item->unit_cost, 2) }}</td>
                            <td class="px-5 py-4 text-right text-sm font-semibold text-slate-800">₱{{ number_format((float) $item->amount, 2) }}</td>
                            <td class="px-5 py-4">
                                @can('project-budget-items.update')
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $item->id }})" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">Edit</button>
                                        <button wire:click="remove({{ $item->id }})" wire:confirm="Remove this budget item?" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-red-600">Remove</button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-14 text-center text-sm text-slate-500">No detailed budget items recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
