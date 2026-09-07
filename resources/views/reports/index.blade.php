@extends('layouts.app')

@section('title', 'Reports')
@section('page-context', 'Reports')

@section('content')
<div class="space-y-7">
    <x-ui.page-header
        title="Reports"
        eyebrow="DILP Reporting"
        description="Generate standardized project, financial, status, quarterly progress, and compliance reports from the centralized DILP database."
    />

    <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm leading-6 text-blue-900">
        Reports use the same project records maintained by the operational modules. Select a report below, then apply fiscal year, quarter, office, fund source, or province filters as needed.
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($reportTypes as $type)
            <a href="{{ route('reports.show', ['report' => $type->value, 'fiscal_year' => $filterOptions['years']->first()]) }}"
                class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] transition hover:-translate-y-0.5 hover:border-[#9bbbd3] hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-[#164b73] group-hover:bg-blue-50">
                        {{ str($type->label())->substr(0, 2)->upper() }}
                    </div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-[#164b73]">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </div>
                <h2 class="mt-5 text-sm font-bold text-slate-900">{{ $type->label() }}</h2>
                <p class="mt-2 text-xs leading-5 text-slate-500">{{ $type->description() }}</p>
            </a>
        @endforeach
    </div>

    <x-ui.card>
        <div class="grid gap-5 lg:grid-cols-[1fr_1.5fr] lg:items-center">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#1e5a8a]">Export & Printing</p>
                <h2 class="mt-1 text-base font-bold text-slate-900">Government-reporting output</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Every report has a clean print view and an Excel-compatible workbook export. Filters are carried into the print/export output.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-bold text-slate-800">Table first</p><p class="mt-1 text-[11px] leading-5 text-slate-500">Dense official data layouts designed for review and printing.</p></div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-bold text-slate-800">Print ready</p><p class="mt-1 text-[11px] leading-5 text-slate-500">Landscape print format with report title, filters, and generated timestamp.</p></div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-bold text-slate-800">Excel export</p><p class="mt-1 text-[11px] leading-5 text-slate-500">Downloads an Excel-compatible .xls workbook without requiring another package.</p></div>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
