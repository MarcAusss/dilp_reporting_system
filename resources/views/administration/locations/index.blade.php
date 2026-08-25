@extends('layouts.app')

@section('title', 'Location Hierarchy')

@section('page-context', 'Location Hierarchy')

@section('content')

    <div class="space-y-7">

        <x-ui.page-header title="Location Hierarchy" eyebrow="Administration"
            description="Manage the province, municipality or city, and barangay hierarchy used throughout DILP project records." />

        <livewire:locations.index />

    </div>

@endsection
