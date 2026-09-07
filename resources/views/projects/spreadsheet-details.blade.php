@extends('layouts.app')

@section('title', 'Spreadsheet Details')
@section('page-context', 'Spreadsheet Details')

@section('content')
<div class="space-y-7">
    <x-ui.page-header
        title="Spreadsheet Details"
        eyebrow="Project Profile"
        description="Structured fields retained from the legacy FY2026 DILP database that do not belong in the core project, finance, workflow, or monitoring screens."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('projects.index') }}">Back to Projects</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-projects.profile-tabs :project="$project" />
    <livewire:projects.spreadsheet-parity-details :project-id="$project->id" />
</div>
@endsection
