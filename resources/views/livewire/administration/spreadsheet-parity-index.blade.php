<div class="space-y-7">
    <x-ui.page-header
        title="Spreadsheet Parity Audit"
        eyebrow="Phase 9"
        description="Column-by-column and worksheet-level audit of the FY2026 DILP workbook against the normalized system. A field is not considered accounted for until it has a system destination, calculation, generated-report destination, or explicit archive classification."
    >
        <x-slot:actions>
            <a
                href="{{ route('spreadsheet-parity.export', [
                    'search' => $search,
                    'sheet' => $sheetFilter,
                    'coverage' => $coverageFilter,
                    'domain' => $domainFilter,
                ]) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50"
            >
                Export Audit CSV
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Workbook Audited</p>
                <h2 class="mt-1 text-base font-bold text-slate-950">{{ $workbook['filename'] }}</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    {{ number_format($workbook['worksheet_count']) }} worksheets ·
                    {{ number_format($workbook['working_sheet_labeled_columns']) }} labeled fields on the main working sheet ·
                    columns {{ $workbook['working_sheet_first_column'] }}–{{ $workbook['working_sheet_last_column'] }}.
                </p>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800 lg:max-w-md">
                Phase 9 is an audit/registry phase. Fields marked <strong>Missing</strong> or <strong>Partial</strong> are implementation requirements for the next parity phases; they are not silently discarded.
            </div>
        </div>
    </x-ui.card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @php
            $coverage = $summary['coverage'];
            $cards = [
                ['Audited Fields', $summary['audited_field_count'], 'text-slate-950'],
                ['Covered', $coverage['covered'] ?? 0, 'text-emerald-700'],
                ['Partial', $coverage['partial'] ?? 0, 'text-amber-700'],
                ['Missing', $coverage['missing'] ?? 0, 'text-red-700'],
                ['System Calculated', $coverage['derived'] ?? 0, 'text-[#164b73]'],
            ];
        @endphp

        @foreach($cards as [$label, $value, $class])
            <x-ui.card>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</p>
                <p class="mt-3 text-2xl font-bold {{ $class }}">{{ number_format($value) }}</p>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card :padding="false">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Worksheet Inventory</h2>
                    <p class="mt-1 text-xs text-slate-500">Every worksheet in the supplied workbook is classified by purpose and replacement path.</p>
                </div>
                <p class="text-xs font-semibold text-slate-500">{{ number_format($summary['sheet_count']) }} worksheets audited</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Worksheet</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Role</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Purpose / Replacement</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Coverage</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Target</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($sheets as $sheet)
                        @php($status = \App\Enums\SpreadsheetParityStatus::tryFrom($sheet['coverage']))
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="min-w-[250px] px-5 py-4 sm:px-6">
                                <p class="text-xs font-bold text-slate-900">{{ $sheet['name'] }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ number_format($sheet['rows']) }} rows × {{ number_format($sheet['columns']) }} columns</p>
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold text-slate-600">{{ str($sheet['role'])->replace('_', ' ')->title() }}</td>
                            <td class="min-w-[360px] px-5 py-4 text-xs leading-5 text-slate-600">{{ $sheet['purpose'] }}</td>
                            <td class="px-5 py-4">
                                <x-ui.badge :variant="$status?->badgeVariant() ?? 'default'">
                                    {{ $status?->label() ?? str($sheet['coverage'])->title() }}
                                </x-ui.badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-slate-700 sm:px-6">{{ $sheet['target_phase'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card :padding="false">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Field Mapping Matrix</h2>
                        <p class="mt-1 text-xs text-slate-500">Includes all 310 labeled columns from the operational working sheet plus independent target and carry-over source fields.</p>
                    </div>
                    <p class="text-xs font-semibold text-slate-500">{{ number_format($filteredCount) }} matching fields</p>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <input
                        type="search"
                        wire:model.live.debounce.250ms="search"
                        placeholder="Search header, column, module..."
                        class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100 xl:col-span-2"
                    >

                    <select wire:model.live="sheetFilter" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        <option value="">All source sheets</option>
                        @foreach($sheets as $sheet)
                            @if(collect($fields)->contains('sheet', $sheet['name']) || $sheetFilter === $sheet['name'])
                                <option value="{{ $sheet['name'] }}">{{ $sheet['name'] }}</option>
                            @endif
                        @endforeach
                    </select>

                    <select wire:model.live="coverageFilter" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        <option value="">All coverage</option>
                        @foreach($statuses as $status)
                            @if(in_array($status->value, ['covered', 'partial', 'missing', 'derived'], true))
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endif
                        @endforeach
                    </select>

                    <select wire:model.live="domainFilter" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        <option value="">All domains</option>
                        @foreach($domains as $domain)
                            <option value="{{ $domain }}">{{ str($domain)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </div>

                @if($search !== '' || $sheetFilter !== '' || $coverageFilter !== '' || $domainFilter !== '')
                    <div>
                        <button type="button" wire:click="clearFilters" class="text-xs font-bold text-[#164b73] hover:text-[#0f2a44]">Clear filters</button>
                    </div>
                @endif
            </div>
        </div>

        <div class="max-h-[760px] overflow-auto">
            <table class="min-w-[1350px] divide-y divide-slate-200">
                <thead class="sticky top-0 z-10 bg-slate-50 shadow-sm">
                    <tr>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Source</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Header</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Domain</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Coverage</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Current Destination</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Required Destination</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Phase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($fields as $field)
                        @php($status = \App\Enums\SpreadsheetParityStatus::tryFrom($field['coverage']))
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <p class="font-mono text-[11px] font-bold text-[#164b73]">{{ $field['column'] }}</p>
                                <p class="mt-1 max-w-[190px] truncate text-[10px] text-slate-400" title="{{ $field['sheet'] }}">{{ $field['sheet'] }}</p>
                            </td>
                            <td class="min-w-[310px] px-5 py-4">
                                @if($field['group'])
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $field['group'] }}</p>
                                @endif
                                <p class="mt-1 text-xs font-semibold leading-5 text-slate-800">{{ $field['header'] }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-slate-600">{{ str($field['domain'])->replace('_', ' ')->title() }}</td>
                            <td class="px-5 py-4">
                                <x-ui.badge :variant="$status?->badgeVariant() ?? 'default'">
                                    {{ $status?->label() ?? str($field['coverage'])->title() }}
                                </x-ui.badge>
                            </td>
                            <td class="min-w-[240px] px-5 py-4 text-xs leading-5 text-slate-600">{{ $field['current_destination'] }}</td>
                            <td class="min-w-[260px] px-5 py-4 text-xs font-semibold leading-5 text-slate-700">{{ $field['target_module'] }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs font-bold text-slate-600 sm:px-6">{{ $field['target_phase'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500 sm:px-6">No spreadsheet fields match the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
