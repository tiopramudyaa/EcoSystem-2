{{-- Add / Rename Folder Modal --}}
<div id="docFolderModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop fixed inset-0 bg-black/50" onclick="closeModal('docFolderModal')"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl w-full max-w-md">
            <div class="primary-gradient px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 id="docFolderModalTitle" class="text-lg font-semibold text-white">Add Folder</h3>
                    <button type="button" onclick="closeModal('docFolderModal')" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <form id="docFolderForm" onsubmit="submitDocFolder(event)">
                <input type="hidden" id="docFolderId" value="">
                <div class="px-6 py-5 space-y-3">
                    <div>
                        <label for="docFolderName" class="block text-sm font-medium text-gray-700 mb-1">Folder Name <span class="text-red-500">*</span></label>
                        <div class="flex items-stretch">
                            <span id="docFolderNumber" class="inline-flex items-center px-3 border border-r-0 border-gray-300 rounded-l-md bg-gray-50 text-sm font-semibold text-gray-600">11.</span>
                            <input type="text" id="docFolderName" maxlength="100" required autocomplete="off"
                                   class="block w-full py-2.5 px-3 border border-gray-300 rounded-r-md shadow-sm primary-focus text-sm"
                                   placeholder="e.g. Change Request">
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">The number is given automatically. Type only the name.</p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" onclick="closeModal('docFolderModal')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" id="docFolderSaveBtn"
                            class="inline-flex items-center gap-2 px-4 py-2 primary-gradient text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
