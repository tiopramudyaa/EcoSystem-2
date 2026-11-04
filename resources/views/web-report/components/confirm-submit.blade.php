{{-- "Send this report?" — a confirmation dialog shown after the form passes its own checks and before anything is sent. Used by the public Web Report
     form and by My Compliance → Report a Concern, so both ask the same way. A report cannot be edited or withdrawn once sent, hence the question.
     Params: $form (id of the <form>) · $sender (optional: the signed-in name, for the "Sent by" line).
     Reads the form's fields by name: reported_name, reporter_name (public form), anonymous (My Compliance checkbox), evidence (file).
     Closes only through its two buttons — no click outside, no Esc — so a report is never sent or abandoned by accident. --}}
<style>
    .rc-back { position: fixed; inset: 0; z-index: 10000; display: none; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .5) }
    .rc-back.is-open { display: flex }
    .rc-box { width: 100%; max-width: 440px; background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0, 0, 0, .3); overflow: hidden; font-family: inherit }
    .rc-head { display: flex; gap: 12px; align-items: flex-start; padding: 20px 22px 4px }
    .rc-ic { flex: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #FEF3C7; color: #B45309 }
    .rc-head h3 { font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 2px }
    .rc-head p { font-size: 12.5px; color: #64748B; margin-top: 4px; line-height: 1.55 }
    .rc-sum { margin: 14px 22px 0; border: 1px solid #E2E8F0; border-radius: 10px; background: #F8FAFC; font-size: 12.5px }
    .rc-sum div { display: flex; gap: 10px; justify-content: space-between; padding: 8px 12px }
    .rc-sum div + div { border-top: 1px solid #E2E8F0 }
    .rc-sum dt { color: #64748B; flex: none }
    .rc-sum dd { color: #0f172a; font-weight: 600; text-align: right; word-break: break-word }
    .rc-foot { display: flex; gap: 10px; justify-content: flex-end; padding: 18px 22px 20px }
    .rc-btn { border-radius: 9px; padding: 10px 16px; font: 600 13px/1 inherit; cursor: pointer; border: 1px solid #CBD5E1; background: #fff; color: #334155 }
    .rc-btn:hover { background: #F1F5F9 }
    .rc-btn.rc-go { border-color: transparent; background: var(--brand, var(--primary-color, #A00000)); color: #fff }
    .rc-btn.rc-go:hover { opacity: .9; background: var(--brand, var(--primary-color, #A00000)) }
    .rc-btn:disabled { opacity: .6; cursor: wait }
    .rc-btn:focus-visible { outline: 2px solid var(--brand, var(--primary-color, #A00000)); outline-offset: 2px }
</style>

<div class="rc-back" id="rcModal" aria-hidden="true">
    <div class="rc-box" role="dialog" aria-modal="true" aria-labelledby="rcTitle" tabindex="-1">
        <div class="rc-head">
            <span class="rc-ic" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg></span>
            <div><h3 id="rcTitle">{{ __('web-report.confirm_title') }}</h3><p>{{ __('web-report.confirm_text') }}</p></div>
        </div>
        <dl class="rc-sum">
            <div><dt>{{ __('web-report.confirm_about') }}</dt><dd id="rcAbout"></dd></div>
            <div><dt>{{ __('web-report.confirm_by') }}</dt><dd id="rcBy"></dd></div>
            <div><dt>{{ __('web-report.confirm_file') }}</dt><dd id="rcFile"></dd></div>
        </dl>
        <div class="rc-foot">
            <button type="button" class="rc-btn" id="rcBack">{{ __('web-report.confirm_back') }}</button>
            <button type="button" class="rc-btn rc-go" id="rcGo">{{ __('web-report.confirm_send') }}</button>
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById(@json($form));
    if (!form) return;
    const modal = document.getElementById('rcModal'), go = document.getElementById('rcGo'), back = document.getElementById('rcBack');
    const sender = @json($sender ?? null), anon = @json(__('web-report.confirm_anonymous')), none = @json(__('web-report.confirm_none'));
    let confirmed = false, opener = null;

    const val = name => (form.elements[name]?.value || '').trim();
    const open = () => {
        const nameBox = form.elements['reporter_name'], anonBox = form.elements['anonymous'];
        const by = anonBox ? (anonBox.checked ? anon : (sender || anon)) : (val('reporter_name') || anon);
        document.getElementById('rcAbout').textContent = val('reported_name');
        document.getElementById('rcBy').textContent = by;
        document.getElementById('rcFile').textContent = form.elements['evidence']?.files?.[0]?.name || none;
        opener = document.activeElement;
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false');
        go.disabled = false; go.focus();
    };
    const close = () => { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); opener?.focus?.(); };

    form.addEventListener('submit', e => {
        if (confirmed) return;                                  // already confirmed: let it go
        e.preventDefault();
        if (!form.reportValidity()) return;                     // the browser shows what is missing; nothing to confirm yet
        open();
    });
    back.addEventListener('click', close);
    go.addEventListener('click', () => { confirmed = true; go.disabled = true; back.disabled = true; form.submit(); });

    // keep the keyboard inside the dialog while it is open
    modal.addEventListener('keydown', e => {
        if (e.key !== 'Tab') return;
        const items = [back, go].filter(b => !b.disabled), first = items[0], last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
})();
</script>
