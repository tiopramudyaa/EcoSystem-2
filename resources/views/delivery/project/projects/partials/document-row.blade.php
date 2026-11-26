{{-- Satu baris dokumen di tabel folder Project Documents.
     Versi JS-nya: renderDocumentRow() di show.blade.php - jaga keduanya sama. --}}
<tr class="hover:bg-gray-50 document-row" data-document-id="{{ $document->id }}">
    <td class="px-4 py-3 w-10">
        <input type="checkbox" class="row-checkbox document-checkbox"
               data-id="{{ $document->id }}"
               data-name="{{ $document->document_name }}"
               data-type="{{ $document->document_type }}"
               data-folder-id="{{ $document->folder_id }}"
               onchange="handleRowSelection('document')">
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="doc-name text-sm font-medium text-gray-900">{{ $document->document_name }}</span>
        </div>
    </td>
    <td class="px-4 py-3">
        <span class="doc-type inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $document->document_type }}</span>
    </td>
    <td class="px-4 py-3 doc-action">
        @if($document->link_document)
            <a href="{{ $document->link_document }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Open File
            </a>
        @else
            <span class="text-xs text-gray-400">-</span>
        @endif
    </td>
</tr>
