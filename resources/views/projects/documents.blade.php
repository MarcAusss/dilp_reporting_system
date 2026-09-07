@extends('layouts.app')

@section('title', 'Project Documents')
@section('page-context', 'Project Documents')

@section('content')
    <div class="space-y-7">
        <x-ui.page-header
            title="Project Documents"
            eyebrow="Project Profile"
            description="Maintain DILP documentary requirements and securely store supporting files for project processing, monitoring, and reporting."
        >
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">
                    Back to Projects
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-projects.profile-tabs :project="$project" />
        <livewire:projects.document-details :project-id="$project->id" />
    </div>
@endsection
