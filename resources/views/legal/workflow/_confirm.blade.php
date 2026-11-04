{{-- Konfirmasi memakai dialog sistem (showConfirm) — bukan confirm() bawaan browser. Form: data-confirm="pesan" [data-confirm-title] [data-confirm-ok] [data-confirm-variant=primary|danger]. Aman dipasang dua kali (dijaga window.__ecConfirmForms, sama dengan Payroll). --}}
<script>
(function () {
    if (window.__ecConfirmForms) return; window.__ecConfirmForms = true;
    document.addEventListener('submit', async function (e) {
        var f = e.target;
        if (!f || !f.matches || !f.matches('form[data-confirm]') || f.dataset.confirmed === '1') return;
        e.preventDefault();
        var ok = await window.showConfirm(f.dataset.confirm, f.dataset.confirmTitle || 'Please confirm', f.dataset.confirmVariant || 'primary',
            { okText: f.dataset.confirmOk || 'Confirm', cancelText: 'Cancel' });
        if (ok) { f.dataset.confirmed = '1'; f.submit(); }
    }, true);
})();
</script>
