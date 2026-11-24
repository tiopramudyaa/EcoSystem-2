@props([
    'icon' => 'fa-inbox',
    'title' => 'Nothing here yet',
    'text' => null,
    'id' => null,
    'hidden' => false,
])
{{--
    One empty state for every list, so "nothing here" looks the same on every page.
    <x-empty-state icon="fa-layer-group" title="No templates yet" text="Create the first one.">
        <a href="..." class="...">Create</a>     <- optional action goes in the slot
    </x-empty-state>
    `hidden` + `id` are for the "no search match" variant that a filter script shows or hides.
--}}
<div @if($id) id="{{ $id }}" @endif {{ $attributes->class(['py-16 text-center', 'hidden' => $hidden]) }} role="status">
    <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4" aria-hidden="true">
        <i class="fas {{ $icon }} text-gray-300 text-2xl"></i>
    </div>
    <p class="text-gray-500 font-medium">{{ $title }}</p>
    @if($text)
        <p class="text-sm text-gray-400 mt-1">{{ $text }}</p>
    @endif
    @if(!$slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
