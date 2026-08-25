<div class="space-y-6">

    @if (session()->has('location-status'))
        <x-ui.alert variant="success" title="Changes saved">
            {{ session('location-status') }}
        </x-ui.alert>
    @endif

    @if (session()->has('location-error'))
        <x-ui.alert variant="warning" title="Action not allowed">
            {{ session('location-error') }}
        </x-ui.alert>
    @endif

    {{-- Level selector --}}
    <x-ui.card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#1e5a8a]">
                    Geographic Structure
                </p>

                <h2 class="mt-1 text-xl font-bold text-slate-950">
                    Location Hierarchy
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Maintain provinces, municipalities or cities,
                    and barangays used by DILP project records.
                </p>
            </div>

            @can('master-data.create')
                <x-ui.button wire:click="startCreate">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path d="M12 5v14M5 12h14" />
                    </svg>

                    Add Location
                </x-ui.button>
            @endcan
        </div>

        <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
            @foreach ([
        'province' => 'Provinces',
        'municipality' => 'Municipalities / Cities',
        'barangay' => 'Barangays',
    ] as $key => $label)
                <button type="button" wire:click="selectLevel('{{ $key }}')"
                    class="
                        rounded-lg px-4 py-2 text-sm font-semibold transition
                        {{ $level === $key
                            ? 'bg-[#0f2a44] text-white'
                            : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}
                    ">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Filters --}}
    <x-ui.card>
        <div class="grid gap-4 lg:grid-cols-3">

            <div>
                <label for="location-search" class="block text-xs font-semibold text-slate-600">
                    Search
                </label>

                <input id="location-search" type="search" wire:model.live.debounce.300ms="search"
                    placeholder="Search name or code..."
                    class="
                        mt-2 block w-full rounded-lg border border-slate-300
                        bg-white px-3.5 py-2.5 text-sm outline-none
                        focus:border-[#1e5a8a]
                        focus:ring-4 focus:ring-blue-100
                    ">
            </div>

            @if ($level !== 'province')
                <div>
                    <label for="province-filter" class="block text-xs font-semibold text-slate-600">
                        Province
                    </label>

                    <select id="province-filter" wire:model.live="provinceFilter"
                        class="
                            mt-2 block w-full rounded-lg border border-slate-300
                            bg-white px-3.5 py-2.5 text-sm outline-none
                            focus:border-[#1e5a8a]
                            focus:ring-4 focus:ring-blue-100
                        ">
                        <option value="">
                            All Provinces
                        </option>

                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}">
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($level === 'barangay')
                <div>
                    <label for="municipality-filter" class="block text-xs font-semibold text-slate-600">
                        Municipality / City
                    </label>

                    <select id="municipality-filter" wire:model.live="municipalityFilter"
                        class="
                            mt-2 block w-full rounded-lg border border-slate-300
                            bg-white px-3.5 py-2.5 text-sm outline-none
                            focus:border-[#1e5a8a]
                            focus:ring-4 focus:ring-blue-100
                        ">
                        <option value="">
                            All Municipalities / Cities
                        </option>

                        @foreach ($municipalities as $municipality)
                            <option value="{{ $municipality->id }}">
                                {{ $municipality->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

        </div>
    </x-ui.card>

    {{-- Form --}}
    @if ($showForm)

        <x-ui.card>
            <div class="flex items-center justify-between">

                <div>
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ $editingId ? 'Edit Location' : 'Add Location' }}
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        @switch($level)
                            @case('province')
                                Province
                            @break

                            @case('municipality')
                                Municipality / City
                            @break

                            @case('barangay')
                                Barangay
                            @break
                        @endswitch
                    </p>
                </div>

                <button type="button" wire:click="cancel" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                        <path d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>

            </div>

            <form wire:submit="save" class="mt-6 space-y-5">

                @if ($level === 'municipality')

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Province
                            <span class="text-red-500">*</span>
                        </label>

                        <select wire:model="province_id"
                            class="
                                mt-2 block w-full rounded-lg border
                                border-slate-300 bg-white px-3.5 py-2.5
                                text-sm outline-none
                                focus:border-[#1e5a8a]
                                focus:ring-4 focus:ring-blue-100
                            ">
                            <option value="">
                                Select Province
                            </option>

                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}">
                                    {{ $province->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('province_id')
                            <p class="mt-1.5 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                @endif

                @if ($level === 'barangay')

                    <div class="grid gap-5 md:grid-cols-2">

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Province
                            </label>

                            <select wire:model.live="province_id"
                                class="
                                    mt-2 block w-full rounded-lg border
                                    border-slate-300 bg-white px-3.5 py-2.5
                                    text-sm outline-none
                                ">
                                <option value="">
                                    Select Province
                                </option>

                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}">
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Municipality / City
                                <span class="text-red-500">*</span>
                            </label>

                            <select wire:model="municipality_id"
                                class="
                                    mt-2 block w-full rounded-lg border
                                    border-slate-300 bg-white px-3.5 py-2.5
                                    text-sm outline-none
                                ">
                                <option value="">
                                    Select Municipality / City
                                </option>

                                @foreach ($formMunicipalities as $municipality)
                                    <option value="{{ $municipality->id }}">
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('municipality_id')
                                <p class="mt-1.5 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>

                @endif

                <div class="grid gap-5 md:grid-cols-2">

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Name
                            <span class="text-red-500">*</span>
                        </label>

                        <input type="text" wire:model="name"
                            class="
                                mt-2 block w-full rounded-lg border
                                border-slate-300 bg-white px-3.5 py-2.5
                                text-sm outline-none
                                focus:border-[#1e5a8a]
                                focus:ring-4 focus:ring-blue-100
                            ">

                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Code
                        </label>

                        <input type="text" wire:model="code" placeholder="Optional"
                            class="
                                mt-2 block w-full rounded-lg border
                                border-slate-300 bg-white px-3.5 py-2.5
                                text-sm uppercase outline-none
                            ">

                        @error('code')
                            <p class="mt-1.5 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

                @if ($level === 'municipality')
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Classification
                        </label>

                        <select wire:model="type"
                            class="
                                mt-2 block w-full rounded-lg border
                                border-slate-300 bg-white px-3.5 py-2.5
                                text-sm outline-none
                            ">
                            <option value="municipality">
                                Municipality
                            </option>

                            <option value="city">
                                City
                            </option>
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Description
                    </label>

                    <textarea wire:model="description" rows="3"
                        class="
                            mt-2 block w-full rounded-lg border
                            border-slate-300 bg-white px-3.5 py-2.5
                            text-sm outline-none
                        "></textarea>
                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Sort Order
                        </label>

                        <input type="number" min="0" wire:model="sort_order"
                            class="
                                mt-2 block w-full rounded-lg border
                                border-slate-300 bg-white px-3.5 py-2.5
                                text-sm outline-none
                            ">
                    </div>

                    <label
                        class="
                            mt-7 flex items-center gap-3 rounded-lg
                            border border-slate-300 px-4 py-2.5
                        ">
                        <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-slate-300">

                        <span class="text-sm text-slate-700">
                            Active
                        </span>
                    </label>

                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                    <x-ui.button type="button" variant="secondary" wire:click="cancel">
                        Cancel
                    </x-ui.button>

                    <x-ui.button type="submit">
                        {{ $editingId ? 'Save Changes' : 'Create Location' }}
                    </x-ui.button>
                </div>

            </form>

        </x-ui.card>

    @endif

    {{-- Table --}}
    <x-ui.card :padding="false">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">
                            Code
                        </th>

                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">
                            Name
                        </th>

                        @if ($level !== 'province')
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">
                                Parent
                            </th>
                        @endif

                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase text-slate-500">
                            Status
                        </th>

                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase text-slate-500">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse ($items as $item)
                        <tr wire:key="location-{{ $level }}-{{ $item->id }}"
                            class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <span class="font-mono text-xs text-slate-500">
                                    {{ $item->code ?: '—' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $item->name }}
                                </p>

                                @if ($level === 'municipality')
                                    <p class="mt-1 text-xs capitalize text-slate-400">
                                        {{ $item->type }}
                                    </p>
                                @endif

                            </td>

                            @if ($level === 'municipality')
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ $item->province->name }}
                                </td>
                            @elseif ($level === 'barangay')
                                <td class="px-5 py-4">
                                    <p class="text-sm text-slate-600">
                                        {{ $item->municipality->name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $item->municipality->province->name }}
                                    </p>
                                </td>
                            @endif

                            <td class="px-5 py-4">
                                <x-ui.badge :variant="$item->is_active ? 'success' : 'neutral'">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </td>

                            <td class="px-5 py-4">

                                <div class="flex justify-end gap-2">

                                    <button type="button" wire:click="edit({{ $item->id }})"
                                        class="
                                            rounded-lg border border-slate-200
                                            px-3 py-1.5 text-xs font-semibold
                                            text-slate-600 hover:bg-slate-50
                                        ">
                                        Edit
                                    </button>

                                    <button type="button" wire:click="toggleStatus({{ $item->id }})"
                                        class="
                                            rounded-lg border border-slate-200
                                            px-3 py-1.5 text-xs font-semibold
                                            {{ $item->is_active ? 'text-amber-600' : 'text-emerald-600' }}
                                        ">
                                        {{ $item->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>

                                </div>

                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-600">
                                    No location records found
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Create a location record to begin.
                                </p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $items->links() }}
            </div>
        @endif

    </x-ui.card>

</div>
