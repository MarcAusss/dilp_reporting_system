@extends('layouts.app')
@section('title', 'Project Livelihoods')
@section('page-context', 'Project Livelihoods')
@section('content')
    <div class="space-y-7">
        <x-ui.page-header title="Project Livelihoods" eyebrow="Project Profile"
            description="Record the livelihood classifications, primary livelihood, and beneficiary targets for this project.">
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">Back to Projects</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
        <x-projects.profile-tabs :project="$project" />
        <livewire:projects.livelihood-details :project-id="$project->id" />
    </div>
@endsection
