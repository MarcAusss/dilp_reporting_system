@extends('layouts.app')

@section('title', 'Audit Logs')
@section('page-context', 'Audit Logs')

@section('content')
    <livewire:administration.audit-log-index />
@endsection
