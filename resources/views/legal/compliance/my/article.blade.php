@extends('legal.compliance.my.layout')
@section('my-content')
@php $cat = $article->category; @endphp
<div class="text-xs"><a href="{{ route('compliance.knowledge') }}" class="cp-accent hover:underline">← Knowledge Base</a>
    <span class="text-gray-300 mx-2">/</span><a href="{{ route('compliance.knowledge', ['cat' => $cat->slug]) }}" class="cp-accent hover:underline">{{ $cat->name }}</a></div>
<article class="cp-card overflow-hidden">
    <header class="px-6 sm:px-8 py-6 border-b border-gray-100">
        <span class="inline-block text-[11px] font-semibold rounded-full px-2.5 py-1 cp-tint">{{ $cat->icon }} {{ $cat->name }}</span>
        <h2 class="text-2xl font-bold text-gray-900 mt-3">{{ $article->title }}</h2>
        <p class="text-xs text-gray-400 mt-2">Updated {{ $article->updated_at->format('d M Y') }} · {{ number_format($article->views) }} views</p>
    </header>
    <div class="px-6 sm:px-8 py-6 text-sm text-gray-700 leading-7 [&_h3]:font-semibold [&_h3]:text-gray-900 [&_h3]:text-base [&_h3]:mt-6 [&_h3]:mb-2 [&_h4]:font-semibold [&_p]:mb-3 [&_ul]:list-disc [&_ol]:list-decimal [&_ul]:pl-6 [&_ol]:pl-6 [&_li]:mb-1 [&_a]:text-indigo-600 [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-gray-200 [&_blockquote]:pl-4 [&_blockquote]:text-gray-500 [&_table]:w-full [&_td]:border [&_th]:border [&_td]:border-gray-200 [&_th]:border-gray-200 [&_td]:p-2 [&_th]:p-2 [&_th]:bg-gray-50 [&_th]:text-left">
        {!! \App\Support\Compliance\HtmlSanitizer::clean($article->content) !!}
        @if($article->tags)<div class="mt-8 pt-4 border-t border-gray-100 flex flex-wrap gap-1.5 items-center"><span class="cp-eyebrow mr-1">Tags</span>@foreach(explode(',', $article->tags) as $tag)<span class="bg-gray-100 text-gray-600 text-[11px] rounded-full px-2.5 py-0.5">{{ trim($tag) }}</span>@endforeach</div>@endif
    </div>
</article>
@if($related->isNotEmpty())<div><h3 class="cp-section-title mb-3">Related articles</h3><div class="space-y-3">@foreach($related as $a)@include('legal.compliance.my.components.article-row', ['a' => $a->setRelation('category', $cat)])@endforeach</div></div>@endif
@endsection
