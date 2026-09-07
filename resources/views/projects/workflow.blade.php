@extends('layouts.app')
@section('title', 'Project Workflow')
@section('page-context', 'Project Workflow')
@section('content')
    <div class="space-y-7">
        <x-ui.page-header title="Project Workflow" eyebrow="Project Profile"
            description="Process the DILP project through evaluation, endorsement, validation, and approval with a complete audit history.">
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">Back to Projects</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-projects.profile-tabs :project="$project" />

        <livewire:projects.workflow-details :project-id="$project->id" />
    </div>
@endsection
