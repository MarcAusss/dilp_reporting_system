<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Services\Reports\DilpReportService;
use App\Services\Reports\ExcelXmlExporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(DilpReportService $reports): View
    {
        return view('reports.index', [
            'reportTypes' => ReportType::cases(),
            'filterOptions' => $reports->filterOptions(),
        ]);
    }

    public function show(
        Request $request,
        ReportType $report,
        DilpReportService $reports,
    ): View {
        return view('reports.show', [
            'report' => $reports->build($report, $request->query()),
            'filterOptions' => $reports->filterOptions(),
        ]);
    }

    public function print(
        Request $request,
        ReportType $report,
        DilpReportService $reports,
    ): View {
        return view('reports.print', [
            'report' => $reports->build($report, $request->query()),
        ]);
    }

    public function excel(
        Request $request,
        ReportType $report,
        DilpReportService $reports,
        ExcelXmlExporter $exporter,
    ): StreamedResponse {
        return $exporter->download(
            $reports->build($report, $request->query())
        );
    }
}
