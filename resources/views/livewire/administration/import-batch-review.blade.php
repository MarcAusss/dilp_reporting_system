<div class="space-y-7">
    <x-ui.page-header
        title="Review Import Batch #{{ $batch->id }}"
        eyebrow="Legacy Data Migration"
        description="{{ $batch->original_filename }} — map source columns, run a dry-run, review every exception, then commit validated project rows."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('data-imports.index') }}">Back to Imports</x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('data-imports.download', $batch) }}">Download Source</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session()->has('import-status'))<x-ui.alert variant="success" title="Legacy Import">{{ session('import-status') }}</x-ui.alert>@endif
    @error('mapping')<x-ui.alert variant="danger" title="Column Mapping">{{ $message }}</x-ui.alert>@enderror
    @error('import')<x-ui.alert variant="danger" title="Import Commit">{{ $message }}</x-ui.alert>@enderror

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Total Rows</p><p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($batch->total_rows) }}</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Valid</p><p class="mt-3 text-2xl font-bold text-emerald-700">{{ number_format($batch->valid_rows) }}</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Warnings</p><p class="mt-3 text-2xl font-bold text-amber-700">{{ number_format($batch->warning_rows) }}</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Errors</p><p class="mt-3 text-2xl font-bold text-red-700">{{ number_format($batch->error_rows) }}</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Duplicates</p><p class="mt-3 text-2xl font-bold text-amber-700">{{ number_format($batch->duplicate_rows) }}</p></x-ui.card>
    </div>

    <x-ui.card>
        <div class="flex flex-col gap-4 border-b border-slate-100 pb-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#1e5a8a]">Step 2</p>
                <h2 class="mt-1 text-base font-bold text-slate-950">Column Mapping</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">Required destination fields are marked with *. Auto-mapping is based on common legacy DILP header names and can be corrected manually.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('data-imports.manage')
                    @if(!in_array($batch->status, [\App\Enums\DataImportStatus::Completed, \App\Enums\DataImportStatus::Cancelled], true))
                        <x-ui.button variant="secondary" wire:click="autoMap">Auto-map Again</x-ui.button>
                        <x-ui.button wire:click="validateDryRun" wire:loading.attr="disabled" wire:target="validateDryRun">Run Dry-Run Validation</x-ui.button>
                    @endif
                @endcan
            </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach(($batch->headers ?? []) as $header)
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <label class="block text-xs font-bold text-slate-700">{{ $header }}</label>
                    <select wire:model="columnMap.{{ $loop->index }}" @disabled(in_array($batch->status, [\App\Enums\DataImportStatus::Completed, \App\Enums\DataImportStatus::Cancelled], true)) class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        <option value="">Ignore this column</option>
                        @foreach($fieldOptions as $field => $label)
                            <option value="{{ $field }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    @if($batch->validated_at)
        <x-ui.card>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#1e5a8a]">Step 3</p>
                    <h2 class="mt-1 text-base font-bold text-slate-950">Dry-Run Result</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Validated {{ $batch->validated_at->format('M d, Y h:i A') }}. Existing records are never overwritten.</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-ui.badge :variant="$batch->status->badgeVariant()">{{ $batch->status->label() }}</x-ui.badge>
                    @can('data-imports.manage')
                        @if($batch->canCommit())
                            <x-ui.button wire:click="commitImport" wire:confirm="Commit all validated rows? This will create new project records and cannot be automatically rolled back from the UI." wire:loading.attr="disabled" wire:target="commitImport">Commit Import</x-ui.button>
                        @elseif($batch->status !== \App\Enums\DataImportStatus::Completed)
                            <span class="text-xs font-semibold text-amber-700">Resolve validation errors and re-run validation. Duplicate rows are safely skipped on commit.</span>
                        @endif
                    @endcan
                </div>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card :padding="false">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Row Review</h2>
                    <p class="mt-1 text-xs text-slate-500">Inspect normalized values and validation messages before migration.</p>
                </div>
                <select wire:model.live="rowStatus" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                    <option value="all">All rows</option>
                    @foreach($rowStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Source</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Project Preview</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Validation Messages</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($rows as $row)
                        @php($data = $row->normalized_data ?? [])
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600 sm:px-6">
                                <p class="font-semibold text-slate-800">{{ $row->source_sheet ?: 'Sheet' }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">Row {{ number_format($row->row_number) }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4"><x-ui.badge :variant="$row->status->badgeVariant()">{{ $row->status->label() }}</x-ui.badge></td>
                            <td class="min-w-[300px] px-5 py-4 text-xs text-slate-600">
                                @if($data)
                                    <p class="font-bold text-slate-900">{{ $data['title'] ?? 'Untitled project' }}</p>
                                    <p class="mt-1">FY {{ $data['fiscal_year'] ?? '—' }} · {{ $data['project_code'] ?? 'No project code' }}</p>
                                    <p class="mt-1 text-slate-500">{{ $data['proponent_name'] ?? 'No proponent' }}</p>
                                    @if($row->duplicateProject)<p class="mt-2 font-semibold text-amber-700">Matches {{ $row->duplicateProject->registry_number }}</p>@endif
                                    @if($row->importedProject)<a href="{{ route('projects.show', $row->importedProject) }}" class="mt-2 inline-block font-bold text-emerald-700">Open imported project</a>@endif
                                @else
                                    <span class="text-slate-400">Run dry-run validation to generate normalized values.</span>
                                @endif
                            </td>
                            <td class="min-w-[360px] px-5 py-4 text-xs leading-5 text-slate-600">
                                @if($row->messages)
                                    <ul class="space-y-1.5">@foreach($row->messages as $message)<li>• {{ $message }}</li>@endforeach</ul>
                                @else
                                    <span class="text-slate-400">No validation messages.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500 sm:px-6">No rows match the selected status.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $rows->links() }}</div>@endif
    </x-ui.card>
</div>
