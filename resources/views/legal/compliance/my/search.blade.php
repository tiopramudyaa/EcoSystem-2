@extends('legal.compliance.my.layout')
@section('my-content')
@php
    $total = $articles->count() + $rules->count() + $alerts->count() + $sessions->count();
    // The searched word is marked in what is shown (escaped first, then marked).
    $mark = fn ($text) => preg_replace('/(' . preg_quote(e($q), '/') . ')/i', '<mark class="rounded bg-yellow-100">$1</mark>', e($text));
@endphp
@if(mb_strlen($q) < config('compliance.portal.search_min_chars'))
    <div class="cp-card p-10 text-center text-sm text-gray-500">Type at least {{ config('compliance.portal.search_min_chars') }} characters.</div>
@elseif($total === 0)
    <div class="cp-card p-10 text-center text-sm text-gray-500">Nothing found for “{{ $q }}”. <a href="{{ route('compliance.knowledge') }}" class="cp-accent underline">Browse the Knowledge Base</a>.</div>
@else
    <p class="text-xs text-gray-500">{{ $total }} result{{ $total === 1 ? '' : 's' }} for “{{ $q }}”</p>
    <div class="space-y-3">
        @foreach($articles as $a)@include('legal.compliance.my.components.article-row', ['a' => $a])@endforeach
        @foreach($rules as $r)
            <a href="{{ route('compliance.rules') }}" class="cp-card cp-link p-4 block"><p class="cp-eyebrow">{{ $r->type === 'do' ? 'Do' : 'Don\'t' }} rule</p><p class="text-sm font-semibold text-gray-800 mt-0.5">{!! $mark($r->title) !!}</p><p class="text-xs text-gray-600 mt-0.5">{!! $mark(\Illuminate\Support\Str::limit($r->description, 140)) !!}</p></a>
        @endforeach
        @foreach($alerts as $a)
            <a href="{{ route('compliance.index') }}" class="cp-card cp-link p-4 block"><p class="cp-eyebrow">Critical rule</p><p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $a->icon }} {!! $mark($a->title) !!}</p><p class="text-xs text-gray-600 mt-0.5">{!! $mark(\Illuminate\Support\Str::limit($a->description, 140)) !!}</p></a>
        @endforeach
        @foreach($sessions as $s)
            <a href="{{ route('compliance.training') }}" class="cp-card cp-link p-4 block"><p class="cp-eyebrow">Training · {{ $s->session_date->format('d M Y') }}</p><p class="text-sm font-semibold text-gray-800 mt-0.5">{!! $mark($s->title) !!}</p><p class="text-xs text-gray-600 mt-0.5">{!! $mark(\Illuminate\Support\Str::limit((string) $s->description, 140)) !!}</p></a>
        @endforeach
    </div>
@endif
@endsection
