<?php

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
