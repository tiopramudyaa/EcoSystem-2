{{--
    Dismissible "how this screen works" hint. Remembered per person in this browser (localStorage), so it shows once and stays gone.

    @include('partials.page-hint', ['key' => 'roles', 'title' => 'How roles work', 'text' => 'A role decides …'])
--}}
<div data-page-hint="{{ $key }}" class="hidden mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900" role="note">
    <i class="fas fa-lightbulb mt-0.5 text-blue-500" aria-hidden="true"></i>
    <div class="flex-1 min-w-0">
        <p class="font-semibold">{{ $title }}</p>
        <p class="mt-0.5 text-blue-800">{{ $text }}</p>
    </div>
    <button type="button" data-hint-dismiss aria-label="Dismiss this hint" title="Dismiss"
            class="w-6 h-6 shrink-0 flex items-center justify-center rounded text-blue-600 hover:bg-blue-100">
        <i class="fas fa-times text-xs" aria-hidden="true"></i>
    </button>
</div>
<script>
(function () {
    var el = document.querySelector('[data-page-hint="{{ $key }}"]');
    if (!el) return;
    var k = 'hint-dismissed:{{ $key }}', gone = false;
    try { gone = window.localStorage.getItem(k) === '1'; } catch (e) { /* storage blocked: show the hint each time */ }
    if (!gone) el.classList.remove('hidden');
    el.querySelector('[data-hint-dismiss]').addEventListener('click', function () {
        el.classList.add('hidden');
        try { window.localStorage.setItem(k, '1'); } catch (e) { /* ignore */ }
    });
})();
</script>
