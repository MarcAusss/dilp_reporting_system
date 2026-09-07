@extends('layouts.app')
@section('title', 'Project Budget Items')
@section('page-context', 'Project Budget Items')
@section('content')
    <div class="space-y-7">
        <x-ui.page-header title="Project Budget Items" eyebrow="Project Profile"
            description="Maintain detailed line items and reconcile them against the saved project financial breakdown.">
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">Back to Projects</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
        <x-projects.profile-tabs :project="$project" />
        <livewire:projects.budget-details :project-id="$project->id" />
    </div>
@endsection
