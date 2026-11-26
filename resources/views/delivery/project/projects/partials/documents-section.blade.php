{{-- Isi section Project Documents: dokumen dikelompokkan per folder.
     Folder standar: 01. Discover, 02. Prepare, 03. Explore, 04. Realize, 05. Deploy,
     06. Run, 00. Other. Folder tambahan user bernomor 11, 12, 13, ...
     Setiap folder = subfolder di folder OneDrive project. --}}
@php
    $docFolders       = $project->documentFolders;
    $docsByFolder     = $project->documents->groupBy('folder_id');
    $canDocEdit       = $can('delivery-project.documents.edit');
    $canDocManage     = $can('delivery-project.documents.manage');
    $nextFolderNumber = \App\Models\DeliveryProjectDocumentFolder::nextCustomNumber($project->id);
    $hasAnyDocument   = $project->documents->isNotEmpty();
@endphp
<div class="bg-white shadow-md rounded-lg">
    <div class="p-6 border-b border-gray-200 flex justify-between items-center flex-wrap gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-700">Project Documents</h2>
            @if(!$project->onedrive_folder_id)
                <p class="text-xs text-amber-600 mt-0.5">Please create an OneDrive folder before uploading documents.</p>
            @else
                <p class="text-xs text-gray-500 mt-0.5">Each folder below is a sub-folder of the project folder in OneDrive.</p>
            @endif
        </div>
        @if($canDocManage)
        <div class="flex items-center gap-2">
            <button type="button" onclick="openDocFolderModal(null)"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-all duration-200">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
                Add Folder
            </button>
            <button type="button" onclick="openUploadDocumentModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Upload Document
            </button>
        </div>
        @endif
    </div>
    <div class="p-6">
        {{-- Toolbar: pilih semua dokumen + buka/tutup semua folder --}}
        <div class="flex items-center justify-between mb-3 text-sm">
            <label id="docSelectAllWrap" class="inline-flex items-center gap-2 text-gray-600 {{ $hasAnyDocument ? '' : 'invisible' }}">
                <input type="checkbox" id="selectAllDocuments" class="row-checkbox" onchange="toggleSelectAll('document')">
                Select all documents
            </label>
            <div class="flex items-center gap-3 text-xs">
                <button type="button" onclick="setAllDocFolders(true)" class="text-gray-500 hover:text-gray-800">Expand all</button>
                <span class="text-gray-300">|</span>
                <button type="button" onclick="setAllDocFolders(false)" class="text-gray-500 hover:text-gray-800">Collapse all</button>
            </div>
        </div>

        <div id="docFolderList" class="space-y-3" data-next-number="{{ $nextFolderNumber }}">
            @foreach($docFolders as $folder)
                @include('delivery.project.projects.partials.document-folder', [
                    'folder' => $folder,
                    'docs'   => $docsByFolder->get($folder->id, collect()),
                ])
            @endforeach
        </div>

        {{-- Cetakan folder baru (Add Folder) - diisi JS lewat placeholder __ID__ dst. --}}
        <template id="docFolderTemplate">
            @include('delivery.project.projects.partials.document-folder', [
                'folder' => (object) ['id' => '__ID__', 'number' => -1, 'name' => '__NAME__', 'display_name' => '__DISPLAY__', 'is_default' => false],
                'docs'   => collect(),
            ])
        </template>
    </div>
</div>
