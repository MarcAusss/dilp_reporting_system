<?php

use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\ReportController;
use App\Models\Project;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware([
    'auth',
    'can:dashboard.view',
])->group(function () {
    Route::view(
        '/dashboard',
        'dashboard'
    )->name('dashboard');
});

Route::middleware([
    'auth',
    'can:projects.view',
])->group(function () {
    Route::view(
        '/projects',
        'projects.index'
    )->name('projects.index');

    Route::get(
        '/projects/{project}',
        function (Project $project) {
            $project->load([
                'proponent',
                'office',
                'fundSource',
                'projectType',
                'projectPurpose',
                'implementationMode',
                'primaryLocation.province',
                'primaryLocation.municipality',
                'primaryLocation.barangay',
                'financial',
                'beneficiaries.sectors',
                'livelihoods.livelihood',
                'budgetItems',
                'convergences.program',
                'workflowState',
                'implementation',
                'documents',
                'monitoringVisits',
                'monitoringFindings',
                'complianceReports',
            ]);

            return view(
                'projects.show',
                compact('project')
            );
        }
    )->name('projects.show');
});

Route::middleware([
    'auth',
    'can:project-financials.view',
])->group(function () {
    Route::get(
        '/projects/{project}/financials',
        function (Project $project) {
            return view(
                'projects.financials',
                compact('project')
            );
        }
    )->name('projects.financials');
});

Route::middleware([
    'auth',
    'can:project-beneficiaries.view',
])->group(function () {
    Route::get(
        '/projects/{project}/beneficiaries',
        fn (Project $project) => view(
            'projects.beneficiaries',
            compact('project')
        )
    )->name('projects.beneficiaries');
});

Route::middleware([
    'auth',
    'can:project-livelihoods.view',
])->group(function () {
    Route::get(
        '/projects/{project}/livelihoods',
        fn (Project $project) => view(
            'projects.livelihoods',
            compact('project')
        )
    )->name('projects.livelihoods');
});

Route::middleware([
    'auth',
    'can:project-budget-items.view',
])->group(function () {
    Route::get(
        '/projects/{project}/budget-items',
        fn (Project $project) => view(
            'projects.budget-items',
            compact('project')
        )
    )->name('projects.budget-items');
});

Route::middleware([
    'auth',
    'can:project-convergence.view',
])->group(function () {
    Route::get(
        '/projects/{project}/convergence',
        fn (Project $project) => view(
            'projects.convergence',
            compact('project')
        )
    )->name('projects.convergence');
});

Route::middleware([
    'auth',
    'can:project-workflow.view',
])->group(function () {
    Route::get(
        '/projects/{project}/workflow',
        fn (Project $project) => view(
            'projects.workflow',
            compact('project')
        )
    )->name('projects.workflow');
});

Route::middleware([
    'auth',
    'can:work-queues.view',
])->group(function () {
    Route::view(
        '/work-queues',
        'work-queues.index'
    )->name('work-queues.index');
});

Route::middleware([
    'auth',
    'can:project-processing.view',
])->group(function () {
    Route::get(
        '/projects/{project}/processing',
        fn (Project $project) => view(
            'projects.processing',
            compact('project')
        )
    )->name('projects.processing');
});


Route::middleware([
    'auth',
    'can:project-spreadsheet-details.view',
])->group(function () {
    Route::get(
        '/projects/{project}/spreadsheet-details',
        fn (Project $project) => view(
            'projects.spreadsheet-details',
            compact('project')
        )
    )->name('projects.spreadsheet-details');
});

Route::middleware([
    'auth',
    'can:project-monitoring.view',
])->group(function () {
    Route::view(
        '/monitoring',
        'monitoring.index'
    )->name('monitoring.index');
});

Route::middleware([
    'auth',
    'can:project-documents.view',
])->group(function () {
    Route::get(
        '/projects/{project}/documents',
        fn (Project $project) => view(
            'projects.documents',
            compact('project')
        )
    )->name('projects.documents');

    Route::get(
        '/projects/{project}/documents/{document}/download',
        [ProjectDocumentController::class, 'download']
    )->name('projects.documents.download');
});

Route::middleware([
    'auth',
    'can:project-monitoring.view',
])->group(function () {
    Route::get(
        '/projects/{project}/monitoring',
        fn (Project $project) => view(
            'projects.monitoring',
            compact('project')
        )
    )->name('projects.monitoring');
});


Route::middleware([
    'auth',
    'can:reports.view',
])->prefix('reports')->group(function () {
    Route::get(
        '/',
        [ReportController::class, 'index']
    )->name('reports.index');

    Route::get(
        '/{report}',
        [ReportController::class, 'show']
    )->name('reports.show');

    Route::get(
        '/{report}/print',
        [ReportController::class, 'print']
    )->name('reports.print');
});

Route::middleware([
    'auth',
    'can:reports.export',
])->get(
    '/reports/{report}/excel',
    [ReportController::class, 'excel']
)->name('reports.excel');



Route::middleware(['auth', 'can:fund-targets.view'])->group(function () {
    Route::view('/funds', 'funds.index')->name('funds.index');
});

Route::middleware(['auth', 'can:beneficiaries.view'])->group(function () {
    Route::view('/beneficiaries', 'beneficiaries.index')->name('beneficiaries.index');
});

Route::middleware(['auth', 'can:users.view'])->group(function () {
    Route::view('/administration/users', 'administration.users.index')->name('users.index');
});

Route::middleware([
    'auth',
    'can:data-imports.view',
])->prefix('administration/imports')->group(function () {
    Route::view(
        '/',
        'administration.imports.index'
    )->name('data-imports.index');

    Route::get(
        '/{batch}',
        fn (\App\Models\DataImportBatch $batch) => view(
            'administration.imports.review',
            compact('batch')
        )
    )->name('data-imports.review');

    Route::get(
        '/{batch}/download',
        [\App\Http\Controllers\Administration\ImportFileController::class, 'download']
    )->name('data-imports.download');
});

Route::middleware([
    'auth',
    'can:data-quality.view',
])->group(function () {
    Route::view(
        '/administration/data-quality',
        'administration.data-quality.index'
    )->name('data-quality.index');
});

Route::middleware([
    'auth',
    'can:audit-logs.view',
])->group(function () {
    Route::view(
        '/administration/audit-logs',
        'administration.audit-logs.index'
    )->name('audit-logs.index');
});


Route::middleware([
    'auth',
    'can:spreadsheet-parity.view',
])->group(function () {
    Route::view(
        '/administration/spreadsheet-parity',
        'administration.spreadsheet-parity.index'
    )->name('spreadsheet-parity.index');

    Route::get(
        '/administration/spreadsheet-parity/export',
        \App\Http\Controllers\Administration\SpreadsheetParityExportController::class
    )->name('spreadsheet-parity.export');
});

Route::middleware([
    'auth',
    'can:master-data.view',
])
    ->prefix('administration')
    ->group(function () {

        Route::view(
            '/master-data',
            'administration.master-data.index'
        )->name('master-data.index');

        Route::view(
            '/locations',
            'administration.locations.index'
        )->name('locations.index');
    });
