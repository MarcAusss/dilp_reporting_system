<div class="space-y-6">

    @if (session()->has('project-status'))
        <x-ui.alert variant="success" title="Project Registry">
            {{ session('project-status') }}
        </x-ui.alert>
    @endif

    <x-ui.card>

        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#1e5a8a]">
                    Project Management
                </p>

                <h2 class="mt-1 text-xl font-bold text-slate-950">
                    Project Registry
                </h2>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Central registry of DILP project records and proponents.
                </p>
            </div>

            @can('projects.create')
                <x-ui.button wire:click="startCreate">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path d="M12 5v14M5 12h14" />
                    </svg>

                    Add Project
                </x-ui.button>
            @endcan
        </div>

        <div class="mt-6 grid gap-4 border-t border-slate-100 pt-5 lg:grid-cols-4">
            <input type="search" wire:model.live.debounce.300ms="search"
                placeholder="Search project, code, proponent..."
                class="
                    rounded-lg border border-slate-300 bg-white
                    px-3.5 py-2.5 text-sm outline-none
                    focus:border-[#1e5a8a]
                    focus:ring-4 focus:ring-blue-100
                ">

            <input type="number" wire:model.live="fiscalYearFilter" placeholder="Fiscal year"
                class="
                    rounded-lg border border-slate-300 bg-white
                    px-3.5 py-2.5 text-sm outline-none
                ">

            <select wire:model.live="projectTypeFilter"
                class="
                    rounded-lg border border-slate-300 bg-white
                    px-3.5 py-2.5 text-sm outline-none
                ">
                <option value="">
                    All Project Types
                </option>

                @foreach ($projectTypes as $type)
                    <option value="{{ $type->id }}">
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter"
                class="
                    rounded-lg border border-slate-300 bg-white
                    px-3.5 py-2.5 text-sm outline-none
                ">
                <option value="">
                    All Record Statuses
                </option>

                @foreach ($recordStatuses as $status)
                    <option value="{{ $status->value }}">
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </div>

    </x-ui.card>

    @if ($showForm)

        <x-ui.card>

            <div class="flex items-center justify-between">

                <div>
                    <h3 class="text-base font-bold text-slate-900">
                        {{ $editingId ? 'Edit Project' : 'Register Project' }}
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Project identity, proponent, classification,
                        and primary location.
                    </p>
                </div>

                <button type="button" wire:click="cancel" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">
                    ✕
                </button>

            </div>

            <form wire:submit="save" class="mt-6 space-y-7">

                {{-- Project --}}
                <div>

                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Project Information
                    </h4>

                    <div class="mt-4 grid gap-5 md:grid-cols-2">

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Fiscal Year *
                            </label>

                            <input type="number" wire:model="fiscal_year"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">

                            @error('fiscal_year')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Official Project Code
                            </label>

                            <input type="text" wire:model="project_code" placeholder="Optional until assigned"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm uppercase">

                            @error('project_code')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-5">
                        <label class="block text-sm font-medium text-slate-700">
                            Project Title *
                        </label>

                        <input type="text" wire:model="title"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">

                        @error('title')
                            <p class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

                {{-- Proponent --}}
                <div class="border-t border-slate-100 pt-6">

                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Proponent
                    </h4>

                    <div class="mt-4 grid gap-5 md:grid-cols-2">

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Proponent Type *
                            </label>

                            <select wire:model="proponent_type"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                @foreach ($proponentTypes as $type)
                                    <option value="{{ $type->value }}">
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Proponent Name *
                            </label>

                            <input type="text" wire:model="proponent_name"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">

                            @error('proponent_name')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Contact Person
                            </label>

                            <input type="text" wire:model="contact_person"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Contact Number
                            </label>

                            <input type="text" wire:model="contact_number"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Email
                            </label>

                            <input type="email" wire:model="email"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Address
                            </label>

                            <input type="text" wire:model="proponent_address"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        </div>

                    </div>

                </div>

                {{-- Classification --}}
                <div class="border-t border-slate-100 pt-6">

                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Classification
                    </h4>

                    <div class="mt-4 grid gap-5 md:grid-cols-2 xl:grid-cols-3">

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Project Type *
                            </label>

                            <select wire:model="project_type_id"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                <option value="">Select</option>

                                @foreach ($projectTypes as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('project_type_id')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Project Purpose *
                            </label>

                            <select wire:model="project_purpose_id"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                <option value="">Select</option>

                                @foreach ($projectPurposes as $purpose)
                                    <option value="{{ $purpose->id }}">
                                        {{ $purpose->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Implementation Mode
                            </label>

                            <select wire:model="implementation_mode_id"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                <option value="">Not specified</option>

                                @foreach ($implementationModes as $mode)
                                    <option value="{{ $mode->id }}">
                                        {{ $mode->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Office
                            </label>

                            <select wire:model="office_id"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                <option value="">Not specified</option>

                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}">
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Fund Source
                            </label>

                            <select wire:model="fund_source_id"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                <option value="">Not specified</option>

                                @foreach ($fundSources as $fundSource)
                                    <option value="{{ $fundSource->id }}">
                                        {{ $fundSource->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Date Received
                            </label>

                            <input type="date" wire:model="date_received"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        </div>

                    </div>

                </div>

                {{-- Location --}}
                <div class="border-t border-slate-100 pt-6">

                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Primary Project Location
                    </h4>

                    <div class="mt-4 grid gap-5 md:grid-cols-3">

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Province *
                            </label>

                            <select wire:model.live="province_id"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                <option value="">Select Province</option>

                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}">
                                        {{ $province->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('province_id')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Municipality / City
                            </label>

                            <select wire:model.live="municipality_id"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                <option value="">Select</option>

                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}">
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('municipality_id')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Barangay
                            </label>

                            <select wire:model="barangay_id"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                <option value="">Select</option>

                                @foreach ($barangays as $barangay)
                                    <option value="{{ $barangay->id }}">
                                        {{ $barangay->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('barangay_id')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-5">
                        <label class="block text-sm font-medium text-slate-700">
                            Additional Location Details
                        </label>

                        <input type="text" wire:model="address_detail"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                    </div>

                </div>

                {{-- Registry --}}
                <div class="border-t border-slate-100 pt-6">

                    <div class="grid gap-5 md:grid-cols-3">

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Record Status
                            </label>

                            <select wire:model="record_status"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                                @foreach ($recordStatuses as $status)
                                    <option value="{{ $status->value }}">
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Source Reference
                            </label>

                            <input type="text" wire:model="source_reference"
                                placeholder="Workbook/source reference"
                                class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
                        </div>

                    </div>

                    <div class="mt-5">
                        <label class="block text-sm font-medium text-slate-700">
                            Remarks
                        </label>

                        <textarea wire:model="remarks" rows="3"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>
                    </div>

                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">

                    <x-ui.button type="button" variant="secondary" wire:click="cancel">
                        Cancel
                    </x-ui.button>

                    <x-ui.button type="submit">
                        {{ $editingId ? 'Save Changes' : 'Register Project' }}
                    </x-ui.button>

                </div>

            </form>

        </x-ui.card>

    @endif

    <x-ui.card :padding="false">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Registry
                        </th>

                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Project
                        </th>

                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Proponent
                        </th>

                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Location
                        </th>

                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Status
                        </th>

                        <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse ($projects as $project)

                        <tr wire:key="project-{{ $project->id }}" class="hover:bg-slate-50/70">

                            <td class="whitespace-nowrap px-5 py-4">

                                <p class="font-mono text-xs font-semibold text-[#164b73]">
                                    {{ $project->registry_number }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    FY {{ $project->fiscal_year }}
                                </p>

                            </td>

                            <td class="min-w-72 px-5 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $project->title }}
                                </p>

                                <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">

                                    @if ($project->project_code)
                                        <span>
                                            {{ $project->project_code }}
                                        </span>
                                    @endif

                                    <span>
                                        {{ $project->projectType->name }}
                                    </span>

                                    <span>
                                        {{ $project->projectPurpose->name }}
                                    </span>

                                </div>

                            </td>

                            <td class="px-5 py-4">

                                <p class="text-sm font-medium text-slate-700">
                                    {{ $project->proponent->name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $project->proponent->type->label() }}
                                </p>

                            </td>

                            <td class="px-5 py-4">

                                @if ($project->primaryLocation)
                                    <p class="text-sm text-slate-700">
                                        {{ $project->primaryLocation->province->name }}
                                    </p>

                                    @if ($project->primaryLocation->municipality)
                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $project->primaryLocation->municipality->name }}

                                            @if ($project->primaryLocation->barangay)
                                                /
                                                {{ $project->primaryLocation->barangay->name }}
                                            @endif
                                        </p>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">
                                        —
                                    </span>
                                @endif

                            </td>

                            <td class="whitespace-nowrap px-5 py-4">

                                @php
                                    $statusVariant = match ($project->record_status) {
                                        \App\Enums\ProjectRecordStatus::Active => 'success',

                                        \App\Enums\ProjectRecordStatus::Archived => 'neutral',

                                        default => 'warning',
                                    };
                                @endphp

                                <x-ui.badge :variant="$statusVariant">
                                    {{ $project->record_status->label() }}
                                </x-ui.badge>

                            </td>

                            <td class="whitespace-nowrap px-5 py-4">

                                <div class="flex justify-end gap-2">

                                    @can('project-financials.view')
                                        <a href="{{ route('projects.financials', $project) }}"
                                            class=" rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-[#164b73] transition hover:bg-blue-50">
                                            Financials
                                        </a>
                                    @endcan
                                    @can('projects.update')
                                        <button type="button" wire:click="edit({{ $project->id }})"
                                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                                            Edit
                                        </button>
                                    @endcan

                                    @can('projects.archive')
                                        <button type="button" wire:click="toggleArchive({{ $project->id }})"
                                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-amber-600 hover:bg-amber-50">
                                            {{ $project->record_status === \App\Enums\ProjectRecordStatus::Archived ? 'Restore' : 'Archive' }}
                                        </button>
                                    @endcan

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <p class="text-sm font-semibold text-slate-600">
                                    No projects found
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Project records will appear here once registered.
                                </p>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($projects->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $projects->links() }}
            </div>
        @endif

    </x-ui.card>

</div>
