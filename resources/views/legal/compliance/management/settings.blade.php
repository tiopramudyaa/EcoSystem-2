@extends('dashboard')
@section('title', 'Compliance Management')
@section('page-title', 'Compliance Management')
@section('page-subtitle', 'The options of Compliance Management — the statuses a report moves through, the follow-up deadline and the categories My Compliance groups its content by.')

@section('content')
@include('legal.compliance.management.components.catalog')
@endsection
