@extends('layouts.app')

@section('title', 'Project Registry')

@section('page-context', 'Project Registry')

@section('content')

    <div class="space-y-7">

        <x-ui.page-header title="Project Registry" eyebrow="Project Management"
            description="Central registry of DILP project records, proponents, classifications, and primary locations." />

        <livewire:projects.index />

    </div>

@endsection
