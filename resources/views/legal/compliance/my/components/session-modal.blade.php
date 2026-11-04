{{-- The details dialog of a training session (opened by openSession() from a session-row). Included by the pages that list sessions: index and training. --}}
<div id="sessionModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 items-center justify-center p-4" style="display:none" onclick="if(event.target===this)closeSession()">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between gap-3"><div><p id="smDate" class="cp-eyebrow cp-accent"></p><h3 id="smTitle" class="text-base font-semibold text-gray-900 mt-1"></h3></div>
            <button type="button" onclick="closeSession()" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i class="fas fa-times"></i></button></div>
        <div class="p-5 space-y-4 text-sm"><p id="smDesc" class="text-gray-600 bg-gray-50 rounded-lg p-3 whitespace-pre-line"></p>
            <dl id="smMeta" class="grid grid-cols-2 gap-2 text-xs"></dl><div id="smLinks" class="space-y-2"></div></div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const modal = document.getElementById('sessionModal'), esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    window.closeSession = () => { modal.style.display = 'none'; modal.classList.add('hidden'); };
    window.openSession = function (d) {
        smDate.textContent = d.date + (d.time ? ' · ' + d.time : ''); smTitle.textContent = d.title; smDesc.textContent = d.description || ''; smDesc.style.display = d.description ? '' : 'none';
        smMeta.innerHTML = [['Status', d.status], ['Duration', d.duration + ' min'], ['Facilitator', d.facilitator], ['Location', d.location], ['Notes', d.notes]]
            .filter(r => r[1]).map(r => '<div class="bg-gray-50 rounded-lg p-2.5"><dt class="cp-eyebrow">' + r[0] + '</dt><dd class="font-medium text-gray-800 mt-0.5">' + esc(r[1]) + '</dd></div>').join('');
        const safe = u => /^https?:\/\//i.test(u || '');
        const links = [['Join the meeting', d.meeting_url, 'video'], ['Watch the recording', d.recording_url, 'circle-play'], ['Open the material', d.material_url, 'file-lines']].filter(l => safe(l[1]));
        smLinks.innerHTML = links.length ? links.map(l => '<a href="' + esc(l[1]) + '" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 px-4 py-3 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50"><i class="fas fa-' + l[2] + ' cp-accent"></i>' + l[0] + '</a>').join('')
            : '<p class="text-xs text-gray-400 border border-dashed border-gray-200 rounded-lg p-3">No link has been added for this session yet.</p>';
        modal.style.display = 'flex'; modal.classList.remove('hidden');
    };
})();
</script>
@endpush
