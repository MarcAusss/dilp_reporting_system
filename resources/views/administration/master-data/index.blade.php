@extends('layouts.app')

@section('title', 'Master Data')

@section('page-context', 'Master Data')

@section('content')

    <div class="space-y-7">

        <x-ui.page-header title="Master Data" eyebrow="Administration"
            description="Manage the controlled reference values used throughout the DILP Reporting and Monitoring System." />

        <livewire:master-data.index />

    </div>

@endsection
