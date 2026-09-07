@extends('layouts.app')

@section('title', 'Review Legacy Import')
@section('page-context', 'Legacy Data Import')

@section('content')
    <livewire:administration.import-batch-review :batch-id="$batch->id" />
@endsection
