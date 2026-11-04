@extends('dashboard')
@section('title', 'Compliance Management')
@section('page-title', 'Compliance Management')
@section('page-subtitle', 'What My Compliance shows staff — articles, Do & Don\'t, critical rules and training sessions.')

@section('content')
@include('legal.compliance.management.components.catalog')
@endsection
