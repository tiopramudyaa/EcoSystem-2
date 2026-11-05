{{-- One article in a list. Expects $a (ComplianceArticle with its category loaded); styles come from my/components/styles. --}}
@php $c = $a->category; @endphp
<a href="{{ route('compliance.article', $a->id) }}" class="cp-card cp-link p-4 flex gap-4 items-center">
    <span class="w-10 h-10 rounded-lg flex items-center justify-center text-lg shrink-0 cp-tint">{{ $c?->icon }}</span>
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-gray-900">{{ $a->title }}@if($a->is_featured) <span class="ml-2 text-[10px] font-semibold bg-green-50 text-green-700 rounded-full px-2 py-0.5 align-middle">Featured</span>@endif</p>
        @if($a->summary)<p class="text-xs text-gray-600 mt-1">{{ $a->summary }}</p>@endif
        <p class="text-[11px] text-gray-400 mt-1.5">{{ $c?->name }} · Updated {{ $a->updated_at->diffForHumans() }} · {{ number_format($a->views) }} views</p>
    </div>
    <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
</a>
