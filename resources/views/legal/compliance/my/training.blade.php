@extends('legal.compliance.my.layout')
@section('my-content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach([['Total sessions', $total, 'text-gray-900'], ['Completed', $done, 'text-green-700'], ['Remaining', $total - $done, 'text-gray-900'], ['Progress', ($total ? round($done / $total * 100) : 0) . '%', 'cp-accent']] as [$text, $n, $cls])
        <div class="cp-card p-4 text-center"><p class="text-2xl font-bold {{ $cls }}">{{ $n }}</p><p class="text-[11px] text-gray-500 mt-1">{{ $text }}</p></div>
    @endforeach
</div>
@forelse($periods as $period => $list)
    <div><h3 class="cp-section-title mb-3">{{ $period }}</h3><div class="space-y-3">@foreach($list as $s)@include('legal.compliance.my.components.session-row', ['s' => $s])@endforeach</div></div>
@empty<div class="cp-card p-10 text-center text-sm text-gray-500">No training session has been scheduled yet.</div>@endforelse
@include('legal.compliance.my.components.session-modal')
@endsection
