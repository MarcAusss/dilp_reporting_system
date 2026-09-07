@extends('layouts.app')

@section('title', 'Project Financial Details')

@section('page-context', 'Project Financial Details')

@section('content')

    <div class="space-y-7">

        <x-ui.page-header title="Project Financial Details" eyebrow="Project Registry"
            description="Maintain the DOLE assistance and project equity breakdown for this DILP project.">
            <x-slot:actions>

                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path d="m15 18-6-6 6-6" />
                    </svg>

                    Back to Projects
                </x-ui.button>

            </x-slot:actions>
        </x-ui.page-header>

        <x-projects.profile-tabs :project="$project" />

        <livewire:projects.financial-details :project-id="$project->id" />

    </div>

@endsection
