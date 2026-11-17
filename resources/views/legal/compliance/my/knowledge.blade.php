@extends('legal.compliance.my.layout')
@section('my-content')
<div class="lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-6 items-start space-y-4 lg:space-y-0">
    <div class="cp-card overflow-hidden">
        <p class="px-4 py-3 bg-gray-50 border-b border-gray-100 cp-eyebrow">Categories</p>
        <a href="{{ route('compliance.knowledge') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs border-b border-gray-100 {{ !$active ? 'cp-tint-strong font-semibold' : 'text-gray-600 hover:bg-gray-50' }}"><i class="fas fa-layer-group w-4 text-center"></i> All articles</a>
        @foreach($categories as $c)
            <a href="{{ route('compliance.knowledge', ['cat' => $c->slug]) }}" class="flex justify-between items-center gap-2 px-4 py-2.5 text-xs border-b border-gray-100 last:border-0 {{ $active?->id === $c->id ? 'cp-tint-strong font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                <span class="truncate">{{ $c->icon }} {{ $c->name }}</span><span class="text-gray-400 font-normal">{{ $c->articles_count }}</span></a>
        @endforeach
    </div>
    <div class="space-y-3 min-w-0">
        @if($active)<div class="rounded-lg p-4 cp-tint"><p class="text-sm font-semibold text-gray-800">{{ $active->icon }} {{ $active->name }}</p><p class="text-xs text-gray-600 mt-0.5">{{ $active->description }}</p></div>@endif
        @forelse($articles as $a)@include('legal.compliance.my.components.article-row', ['a' => $a])@empty
            <div class="cp-card p-10 text-center text-sm text-gray-500">No articles found.</div>
        @endforelse
    </div>
</div>
@endsection
