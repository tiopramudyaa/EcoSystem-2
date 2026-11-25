{{-- Satu folder di section Project Documents.
     $folder : DeliveryProjectDocumentFolder (atau objek placeholder untuk <template>)
     $docs   : koleksi Document di folder ini
     Folder kosong tampil tertutup; folder berisi dokumen tampil terbuka. --}}
@php
    $folderCount = $docs->count();
    $folderOpen  = $folderCount > 0;
@endphp
<div class="doc-folder border border-gray-200 rounded-lg overflow-hidden"
     data-folder-id="{{ $folder->id }}"
     data-folder-name="{{ $folder->name }}"
     data-folder-display="{{ $folder->display_name }}"
     data-folder-default="{{ $folder->is_default ? '1' : '0' }}">
    <div class="doc-folder-head flex items-center justify-between gap-3 px-4 py-3 bg-gray-50 cursor-pointer select-none hover:bg-gray-100 transition-colors"
         onclick="toggleDocFolder(this.closest('.doc-folder'))">
        <div class="flex items-center gap-2 min-w-0">
            <svg class="doc-folder-chevron w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200 {{ $folderOpen ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M10 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"/>
            </svg>
            <span class="doc-folder-title text-sm font-semibold text-gray-800 truncate">{{ $folder->display_name }}</span>
            <span class="doc-folder-count inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 rounded-full text-xs font-medium {{ $folderCount ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-500' }}">{{ $folderCount }}</span>
            @if($folder->number === \App\Models\DeliveryProjectDocumentFolder::DISCOVER_NUMBER && $folder->is_default)
                <span class="hidden sm:inline text-xs text-gray-400 truncate">Plan Cost supporting documents are stored here in OneDrive</span>
            @endif
        </div>
        <div class="flex items-center gap-1 flex-shrink-0" onclick="event.stopPropagation()">
            @if($canDocManage)
            <button type="button" title="Upload to this folder" onclick="openUploadDocumentModal(this.closest('.doc-folder').dataset.folderId)"
                    class="p-1.5 rounded-md text-gray-500 hover:text-blue-700 hover:bg-blue-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </button>
            @endif
            @if(!$folder->is_default)
                @if($canDocEdit)
                <button type="button" title="Rename folder" onclick="openDocFolderModal(this.closest('.doc-folder'))"
                        class="p-1.5 rounded-md text-gray-500 hover:text-amber-700 hover:bg-amber-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                @endif
                @if($canDocManage)
                <button type="button" title="Delete folder" onclick="confirmDeleteDocFolder(this.closest('.doc-folder'))"
                        class="p-1.5 rounded-md text-gray-500 hover:text-red-700 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                @endif
            @endif
        </div>
    </div>
    <div class="doc-folder-body {{ $folderOpen ? '' : 'hidden' }}">
        <p class="doc-folder-empty px-4 py-4 text-sm text-gray-400 {{ $folderCount ? 'hidden' : '' }}">No documents in this folder.</p>
        <div class="overflow-x-auto {{ $folderCount ? '' : 'hidden' }} doc-folder-table">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-white">
                    <tr>
                        <th class="px-4 py-2 w-10"></th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Document Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="doc-folder-tbody bg-white divide-y divide-gray-100">
                    @foreach($docs as $document)
                        @include('delivery.project.projects.partials.document-row', ['document' => $document])
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
