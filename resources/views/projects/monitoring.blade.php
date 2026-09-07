@extends('layouts.app')

@section('title', 'Monitoring & Compliance')
@section('page-context', 'Monitoring & Compliance')

@section('content')
    <div class="space-y-7">
        <x-ui.page-header
            title="Monitoring & Compliance"
            eyebrow="Project Profile"
            description="Record monitoring visits, accomplishment snapshots, findings, corrective actions, follow-ups, and reporting compliance for approved DILP projects."
        >
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">
                    Back to Projects
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-projects.profile-tabs :project="$project" />
        <livewire:projects.monitoring-details :project-id="$project->id" />
    </div>
@endsection
