@extends('layouts.app')

@section('title', 'Financial & Implementation Processing')
@section('page-context', 'Project Processing')

@section('content')
    <div class="space-y-7">
        <x-ui.page-header
            title="Financial & Implementation Processing"
            eyebrow="Project Profile"
            description="Track procurement, obligation, disbursement, insurance, implementation progress, replacement requests, and fund utilization for an approved DILP project."
        >
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">
                    Back to Projects
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-projects.profile-tabs :project="$project" />

        <livewire:projects.processing-details :project-id="$project->id" />
    </div>
@endsection
