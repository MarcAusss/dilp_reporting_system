@extends('layouts.app')
@section('title', 'Project Beneficiaries')
@section('page-context', 'Project Beneficiaries')
@section('content')
    <div class="space-y-7">
        <x-ui.page-header title="Project Beneficiaries" eyebrow="Project Profile"
            description="Maintain beneficiary identities and priority-sector classifications for this DILP project.">
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">Back to Projects</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
        <x-projects.profile-tabs :project="$project" />
        <livewire:projects.beneficiary-details :project-id="$project->id" />
    </div>
@endsection
