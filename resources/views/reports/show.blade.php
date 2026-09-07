@extends('layouts.app')

@section('title', $report['title'] . ' Report')
@section('page-context', 'Reports')

@section('content')
@php
    $query = array_filter($report['filters'], fn ($value) => $value !== null && $value !== '');
@endphp

<div class="space-y-7">
    <x-ui.page-header
        :title="$report['title'] . ' Report'"
        eyebrow="DILP Reports"
        :description="$report['description']"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('reports.index') }}">All Reports</x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('reports.print', ['report' => $report['type']->value, ...$query]) }}" target="_blank">Print View</x-ui.button>
            <x-ui.button href="{{ route('reports.excel', ['report' => $report['type']->value, ...$query]) }}">Export Excel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <form method="GET" action="{{ route('reports.show', $report['type']->value) }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Fiscal Year</label>
                <select name="fiscal_year" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    @foreach ($filterOptions['years'] as $year)
                        <option value="{{ $year }}" @selected((int) $report['filters']['fiscal_year'] === (int) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Quarter</label>
                <select name="quarter" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    <option value="">All Quarters</option>
                    @foreach ($filterOptions['quarters'] as $number => $label)
                        <option value="{{ $number }}" @selected((int) ($report['filters']['quarter'] ?? 0) === (int) $number)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Office</label>
                <select name="office_id" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    <option value="">All Offices</option>
                    @foreach ($filterOptions['offices'] as $office)
                        <option value="{{ $office->id }}" @selected((int) ($report['filters']['office_id'] ?? 0) === $office->id)>{{ $office->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Fund Source</label>
                <select name="fund_source_id" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    <option value="">All Fund Sources</option>
                    @foreach ($filterOptions['fundSources'] as $fund)
                        <option value="{{ $fund->id }}" @selected((int) ($report['filters']['fund_source_id'] ?? 0) === $fund->id)>{{ $fund->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Province</label>
                <select name="province_id" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                    <option value="">All Provinces</option>
                    @foreach ($filterOptions['provinces'] as $province)
                        <option value="{{ $province->id }}" @selected((int) ($report['filters']['province_id'] ?? 0) === $province->id)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <x-ui.button type="submit" class="flex-1">Apply</x-ui.button>
                <x-ui.button variant="secondary" href="{{ route('reports.show', ['report' => $report['type']->value, 'fiscal_year' => $report['filters']['fiscal_year']]) }}">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($report['summary'] as $metric)
            <x-ui.card>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ $metric['label'] }}</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">
                    @if (($metric['type'] ?? null) === 'money')
                        ₱{{ number_format((float) $metric['value'], 2) }}
                    @elseif (($metric['type'] ?? null) === 'percent')
                        {{ number_format((float) $metric['value'], 2) }}%
                    @else
                        {{ number_format((float) $metric['value']) }}
                    @endif
                </p>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900">{{ $report['title'] }} Data</h2>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($report['rows']->count()) }} row(s) • Generated {{ $report['generatedAt']->format('M d, Y h:i A') }}</p>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($report['filterLabels'] as $label => $value)
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-600">{{ $label }}: {{ $value }}</span>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach ($report['columns'] as $column)
                            <th class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($report['rows'] as $row)
                        <tr class="align-top hover:bg-slate-50/70">
                            @foreach ($report['columns'] as $column)
                                @php($value = $row[$column['key']] ?? null)
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-700">
                                    @if (($column['type'] ?? null) === 'money')
                                        <span class="font-mono">₱{{ number_format((float) $value, 2) }}</span>
                                    @elseif (($column['type'] ?? null) === 'percent')
                                        <span class="font-semibold">{{ number_format((float) $value, 2) }}%</span>
                                    @elseif (($column['type'] ?? null) === 'integer')
                                        {{ number_format((int) $value) }}
                                    @else
                                        {{ $value ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($report['columns']) }}" class="px-5 py-12 text-center text-sm text-slate-500">No records match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
