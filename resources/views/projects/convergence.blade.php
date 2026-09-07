@extends('layouts.app')
@section('title', 'Project Convergence')
@section('page-context', 'Project Convergence')
@section('content')
    <div class="space-y-7">
        <x-ui.page-header title="Project Convergence" eyebrow="Project Profile"
            description="Track referrals, partner programs, and other convergence assistance linked to this DILP project.">
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('projects.index') }}">Back to Projects</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
        <x-projects.profile-tabs :project="$project" />
        <livewire:projects.convergence-details :project-id="$project->id" />
    </div>
@endsection
