# Settings UX audit: confirm dialogs, result notifications, unsaved-changes guard

Scope: read-only code audit (no DB access). Date: 2026-11-20.
State audited: git HEAD plus the uncommitted working-tree (WT) edits already on disk (dashboard `info` flash, global `data-confirm` handler in `partials/confirm-modal`, `data-confirm` on the Reimbursement forms, new Reimbursement flash wording, legal confirm-forms patch). Where behaviour differs, both are stated.

---------------------------------------------------------------------
## 1. Global mechanisms

### 1.1 Confirm dialog
- `window.showConfirm(message, title, variant, {okText, cancelText, hideCancel})`, `showAlert`, `confirmSubmit(form,...)`: `resources/views/partials/confirm-modal.blade.php` (included once at `resources/views/dashboard.blade.php:2762`). Uses `textContent` (safe). Enter = OK; Esc / backdrop / Cancel resolve false. A dismissal is silent: nothing is shown.
- `data-confirm`, `data-confirm-title`, `data-confirm-ok`, `data-confirm-variant` on `<form>` were NOT global at HEAD. Four partials each carried a private copy, so a form on any other page ignored the attributes and submitted with no dialog:
  - `resources/views/finance/payroll/components/tabs.blade.php:36-48` (default variant primary, title "Please confirm", ok "Confirm", cancel "Cancel"; sets `window.__ecConfirmForms`)
  - `resources/views/legal/workflow/_confirm.blade.php:1-14` (same as payroll, same flag)
  - `resources/views/legal/contracts/components/confirm-forms.blade.php:12-30` (default variant danger, title "Confirm", ok "OK"; WT also returns when `event.defaultPrevented || window.__ecConfirmForms`)
  - `resources/views/hr-general/recruitment/components/confirm-forms.blade.php:11-24` (variant hard-coded danger and ignores `data-confirm-variant`, ok "OK", cancel "Back", no `dataset.confirmed` guard, bubble phase)
- WT adds a global handler at `partials/confirm-modal.blade.php:149-199`: capture phase; variant = danger if title matches /delete|remove|void/ else primary; on cancel `showToast(data-cancel-message || 'Cancelled. Nothing was changed.', 'info', 3500)`; resubmits with `requestSubmit()`. Defects:
  1. Page scripts (payroll `tabs`, legal `_confirm`) run inside `@yield('content')` (dashboard:1829), before `confirm-modal` (dashboard:2762), so they set `window.__ecConfirmForms` first and the new global handler returns early. Payroll and Legal Workflow pages get NO "Cancelled" toast and keep their own wording.
  2. `recruitment/components/confirm-forms.blade.php` has no `confirmed` / `__ecConfirmForms` check. After the global handler resubmits (`confirmed=1`), the recruitment bubble listener sees `defaultPrevented === false` and asks AGAIN: double dialog on every page that includes it (inventory assets/items/settings, letters compose/my-requests/register/requests/settings, offering index/settings, recruitment jobs/settings, management/employee settings, every `icon-action` post action). Needs the same guard legal got.
  3. Ordering: `data-confirm` forms confirm BEFORE the form's own client validation (workflow editor submit handler `editor.blade.php:318-340`), so the user confirms and then gets "every step needs a name".
- Other confirm styles (not `data-confirm`): `confirmSubmit`, per-page `showConfirm` scripts with `dataset.confirmed` (cash-advance `show.blade.php:478,520`, `submit.blade.php:538`, `my-cash-advance.blade.php:235`, `report-*.blade.php`; ESS group delete `management/ess-settings/index.blade.php:215-227`; attendance settings form `hr-general/attendance/settings.blade.php:435-466`), `icon-action` `confirm` param (`recruitment/settings.blade.php:208`, `offering/settings.blade.php:211`, `inventory/settings.blade.php:112`). None toast on dismiss.

### 1.2 Toasts and flashes (`resources/views/dashboard.blade.php`)
- `showToast(message, type, duration)` line 1957; `showNotification(message, type)` line 1988 (calls showToast, 4000 ms). Both are global functions.
- The message is injected with `innerHTML` (`dashboard.blade.php:1967`, `<p class="toast-message">${message}</p>`). NOT escaped. `@json(session(...))` only makes the JS literal safe; the string is then parsed as HTML. Any flash or `showNotification` string containing user data is an HTML injection / stored XSS sink (see 5.2).
- Flash keys rendered: `error` (6000 ms, :1998), `success` (4000 ms, :2003), `warning` (5000 ms, :2008). `info` only in WT (:2013); at HEAD `with('info')` (for example `AuthController.php:1247`) is never shown on dashboard pages. `status` is never rendered. Validation errors (`$errors`, `withErrors`) are not rendered by the layout; each page must print them.
- Titles fixed: Success / Error / Warning / Information.
- "Cancelled" feedback: none at HEAD; in WT only through the global `data-confirm` handler and only where it actually runs (1.1 defect 1).

### 1.3 Unsaved-changes guards (two different ones)
1. `public/js/unsaved-guard.js` (loaded globally by dashboard): opt-in `<form data-unsaved-guard [data-autosave=key]>`; beforeunload prompt, local draft, `closeModalGuarded`. Only ONE form in the app uses it: `resources/views/delivery/project/projects/create.blade.php:20`. No settings page uses it.
2. `resources/views/hr-general/recruitment/components/unsaved-guard.blade.php`: automatic for every POST form with an editable field (opt-out `data-unsaved-ignore`, label from `data-unsaved-label`); beforeunload, link click, modal close, "saving another form" prompts, amber outline + notice. Included only by cash-advance settings (`cash-advance/settings.blade.php:5`), overtime settings, purchase-request settings, reimbursement settings (`reimbursement/settings.blade.php:21`), and via tabs: letters (`letters/components/tabs.blade.php:35`), offering (`offering/components/hub-tabs.blade.php:26`), recruitment (`recruitment/components/hub-tabs.blade.php:62`), contracts (`legal/contracts/components/tabs.blade.php:27`), `letters/my-requests.blade.php`.
   Defect: `saving = form` is set on the first `submit` (`unsaved-guard.blade.php:229-235`, capture listener registered before the confirm handler) and is never cleared. When the form has `data-confirm` and the user presses Cancel, the form stays "saving", so `beforeunload` (:177, `form !== saving`) no longer warns about those still-unsaved edits. Reimbursement Rules / Object flow / Office funds / Approval workflow are affected as soon as they carry `data-confirm`.

---------------------------------------------------------------------
## 2. Reimbursement Settings (routes `routes/hr-general.php:660-691`)

Page `resources/views/hr-general/reimbursement/settings.blade.php`: guard include :21, `step-editor` :30, `object-settings` :35. Wording below is WT; HEAD in brackets where changed.

| # | Control (view:line) | Route / controller | Confirm | Success flash | Error flashes | Cancel toast | Unsaved guard |
|---|---|---|---|---|---|---|---|
| 1 | "Save Settings", Rules form `settings.blade.php:40-41,285` | POST `reimbursement.settings.update`, `ReimbursementSettingController::update` :114-148 | HEAD none. WT "Save reimbursement rules?" / "Save rules" (primary) | "Reimbursement rules saved. They apply to documents submitted from now on." [HEAD "Reimbursement settings saved."] | :123 empty host list (static). Field errors via `@error` | WT yes | yes (label "Reimbursement Rules") |
| 2 | "Save flow and policy", Object flow `object-settings.blade.php:82-83,179` | POST `objects.flow`, `ReimbursementObjectSettingController::updateFlow` :28-64 | HEAD none. WT "Save flow and policy?" | "Flow and policy saved. New documents use them from now on." [HEAD "Object and fund settings saved."] | :50 no project approver. `validate()` errors (:30) are NOT displayed (no `@error`/`$errors` in `object-settings.blade.php`): a failed validation is silent | WT yes | yes ("Object flow") |
| 3 | "Save offices" `object-settings.blade.php:185-186,227` | POST `objects.offices`, `syncOffices` :68-110 | HEAD none. WT "Save offices?" | "Office funds and responsible persons saved." | :107 "Some offices were not saved." but valid rows ARE written (partial save reported as `error`). `validate()` (:70) not displayed | WT yes | yes ("Office funds") |
| 4 | "Update" allocation row `object-settings.blade.php:263-270` | POST `objects.pools.update`, `updatePool` :177-199 | HEAD none. WT "Update this allocation?" | "Allocation for X updated." | :188 below committed; `validate()` (:179) not displayed | WT yes | yes (each row is its own POST form: editing one row then saving another triggers the "saving another form" prompt) |
| 5 | "Add allocation" `#poolForm` `object-settings.blade.php:280-284,311` | POST `objects.pools.store`, `storePool` :114-173 | HEAD none. WT "Add this allocation?" | "Allocation for X saved." | :132, :141 (echoes typed ticket number), :154 | WT yes | yes |
| 6 | "Save Approval Workflow", shared editor `hr-general/partials/approval-workflow/editor.blade.php:67-68,113` (reimbursement `step-editor.blade.php`; also cash-advance, overtime, purchase-request) | POST `steps.sync`, `syncSteps` :164-243 | HEAD none. WT "Save approval workflow?" / "Save workflow" (static text) | "Approval workflow saved successfully." plus " The new step was also applied to N document(s) in progress." AND an in-form green banner (`editor.blade.php:86-90`): toast + banner duplicate | :169 no steps, :191 none active, step errors via `withErrors` rendered :94-98 | WT yes | yes |
| 7 | Row "Del" `editor.blade.php:288` (`data-remove`) | client only; DB rows deleted on next save (`syncSteps` :222-223) | none at click; save text does not mention removals | #6 flash never says how many steps were removed | n/a | n/a | yes |
| 8 | Checkbox "Also apply new steps to N documents in progress" `editor.blade.php:143-148` | same POST as #6 (`apply_to_open`) | static save text does not change when ticked; this is the only action that mutates in-flight documents | note only if `$applied > 0`; ticked with no new step: silently ignored (`$created->isNotEmpty()` :232) | none | n/a | n/a |
| 9 | Move up/down, Add step `editor.blade.php:276-286` | client only | n/a | n/a | client validation `showNotification(...,'error')` :335-336 | n/a | n/a |

Missing for Reimbursement Settings:
1. Commit the WT confirm + cancel-toast + wording (uncommitted).
2. No validation-error display for forms 2-5.
3. #8 needs its own warning with the in-flight count; unticked text should say they are unaffected.
4. #7 removed steps should be listed in the confirm and the success note.
5. #6 duplicate success (toast + banner): drop one.
6. #3 partial save should be `warning` ("N saved, M not saved: ...").
7. Guard `saving` defect (1.3) so Cancel keeps beforeunload protection.
8. No generic failure path: DB/exception errors become a 500 page, not a toast.
9. Not verified: what `menu:` middleware flashes when a user lacks the slug of a section.

---------------------------------------------------------------------
## 3. All settings pages: consistency table

"S" = confirm before save, "D" = before delete. "Guard" = automatic shared guard (1.3 item 2).

| Page (view) | Controller | S | D | Guard | Flash wording | Verdict |
|---|---|---|---|---|---|---|
| Reimbursement rules / object / workflow | ReimbursementSetting*, ReimbursementObjectSetting* | WT yes, HEAD no | no delete (rows removed via workflow save) | yes | "...saved. <effect>." (WT) | OK once WT committed; gaps in section 2 |
| Cash Advance `hr-general/cash-advance/settings.blade.php:63` + workflow | CashAdvanceSettingController :146, :222-333 | no | step delete via editor, no confirm | yes (:5) | "Cash advance settings saved." / "Approval step added/updated/deleted." | DEVIATES: financial rule save unconfirmed |
| Overtime `overtime/settings.blade.php` | OvertimeSettingController :68, :102-174 | no | no | yes | "Overtime settings saved." | DEVIATES |
| Purchase Request `purchase-request/settings.blade.php` | PurchaseRequestSettingController :123, :177-264 | no | no | yes | "Purchase request settings saved." | DEVIATES |
| Purchase Order `purchase-order/components/settings.blade.php:6` | PO settings update | no | n/a | none | n/a | DEVIATES (no confirm, no guard) |
| Attendance `attendance/settings.blade.php:15,435-466` | AttendanceSettingController :76 | yes, custom ("Save Attendance Settings", ok "Save", cancel "Review Again", no toast) | sources delete `#deleteSourceForm` (:367, :407 JS; not read in detail) | none | "Attendance settings saved." | DEVIATES: custom wording, no cancel toast, no guard |
| Payroll `finance/payroll/settings.blade.php` | PayrollSettingsController :212, :236 | rules save no; switch on/off yes (:53) | n/a | none | descriptive (good) | PARTIAL |
| BPJS `finance/bpjs/settings.blade.php:31` | BpjsSettingsController :75-79 | no (creates versioned statutory rate) | n/a | none | "New BPJS setting saved. Earlier versions were left unchanged." + warning | DEVIATES |
| PPh21 `finance/pph21/settings.blade.php:53,83,118,149,204` | Pph21SettingsController :118,:126 | no (5 POST forms) | n/a | none | "Tax year N created from M..." | DEVIATES |
| Legal Basis `finance/legal-basis/settings.blade.php:28,81,130` | LegalBasisController :72-98 | no dialog; checkbox `confirm_differences` (:59,:112) | n/a | none | "Legal basis settings saved. They take effect immediately." | DEVIATES (checkbox, not dialog) |
| Timesheet ext. `external/timesheet/settings.blade.php` | ExternalWorkflowSettingsController | no (modals `data-unsaved-ignore`) | yes (:45, :72) | none | "Step saved." / "Step deleted." / "Options saved." | PARTIAL |
| Inventory `inventory/settings.blade.php` | InventorySettingController :167-170 | no | yes (icon-action :112) | none | dynamic | PARTIAL |
| Letters `letters/settings.blade.php` (13 forms) | Letter*Controller | no | yes (3 data-confirm, hidden delete forms :644-653) | yes (tabs) | mixed | PARTIAL |
| Offboarding `offboarding/settings.blade.php:47` | OffboardingSettingsController :42,:65,:78 | no (`data-unsaved-ignore`) | yes (:88) | none | "Settings saved." (generic) | PARTIAL |
| Offering `offering/settings.blade.php:25,153,220` | RecruitmentOfferController | no | yes (:211) | yes | n/a | PARTIAL |
| Recruitment `recruitment/settings.blade.php:40,158,218` | RecruitmentSettingController :52-153 | no (calendar source changes unconfirmed; option rows autosave by design, :130) | yes (:208) | yes | "Recruitment settings saved." / "Option added." | PARTIAL |
| Contracts `legal/contracts/settings.blade.php`, `components/settings-options.blade.php:150` | ContractSettingController :72-127 | no | yes (:150) | yes | "label saved." / "deleted." | PARTIAL |
| Legal workflow `legal/workflow-settings.blade.php` | WorkflowSettingsController :53-125 | no | step delete yes (:46) | none | good effect wording | PARTIAL; toggles :91,:134 unconfirmed |
| Compliance `legal/compliance/management/settings` | Legal\Compliance\SettingsController :42 | view has no form | n/a | none | "Follow-up deadline set to N days..." | OK wording |
| Employee settings panel `management/employee/components/settings-panel.blade.php:168` | EmployeeSettingsController :84-186 | no; `data-unsaved-label` set but guard not included, label is dead | option delete via confirm-forms | none | "x added to ... It is on the form now." | DEVIATES |
| ESS settings `management/ess-settings/index.blade.php:69,110,144` | EssSettingsController :219-351 | no (AJAX save, `showToast` from JSON) | group delete yes (:215-227) | none | "ESS Settings updated successfully." / "Group "X" created..." | PARTIAL |
| AI `admin/ai-settings.blade.php:91` | AiSettingsController :95 | no | n/a | none | "AI model settings saved. New chats use them immediately." | DEVIATES (low risk) |
| Seasonal theme `admin/seasonal-theme-settings.blade.php:79` | SeasonalThemeSettingsController :110,:153 | no | n/a | none | i18n `ui.seasonal.admin.saved` | DEVIATES (low risk, localised) |
| 2FA enforcement `admin/two-factor-enforcement-settings.blade.php:33` | TwoFactorEnforcementSettingsController :68 | no | n/a | none | "Two-factor authentication is no longer mandatory for any role." | DEVIATES (high risk) |
| `settings/index.blade.php` | SettingsController | `onsubmit`-style, 6 matches, not reviewed in depth | | none | JSON `getMessage()` :109,:192,:243 | not assessed |

Deviating pages, confirm-before-save: Cash Advance, Overtime, Purchase Request, Purchase Order, Attendance (custom), Payroll (rules), BPJS, PPh21, Legal Basis, Offboarding, Offering, Recruitment (calendar), Contracts, Employee settings, ESS (AJAX), AI, Seasonal, 2FA enforcement, plus the save side of Timesheet / Letters / Inventory and the workflow editors of the other three modules.
Deviating pages, unsaved guard: Attendance, Payroll, BPJS, PPh21, Legal Basis, Purchase Order, Timesheet, Inventory, Offboarding, Legal workflow, Employee, ESS, and the three Admin pages.
Flash wording: "saved." vs "saved successfully." (workflow) vs "updated successfully." (ESS) vs bare "Settings saved." (Offboarding); an effect sentence only in Payroll / Legal / Workflow / WT-Reimbursement; Seasonal localised, the rest English.

---------------------------------------------------------------------
## 4. Proposed single standard

### 4.1 Wording
Save (any settings form)
- Title `Save <thing>?` (e.g. "Save reimbursement rules?"). Body: one sentence saying WHEN it applies and what is untouched ("These rules apply to documents submitted from now on. Documents already submitted keep their own rules."). OK `Save` (or `Save <thing>`), Cancel `Cancel`, variant primary.

Delete / remove
- Title `Delete <thing>?`. Body "<Name> will be deleted. <Consequence>. This cannot be undone." OK `Delete`, variant danger.

Apply to open documents
- Title `Apply to documents in progress?`. Body "The new step will be added to N document(s) still in progress. Approved and rejected documents are never changed. This cannot be undone." OK `Apply to N documents`, variant danger. Appended to the save confirm only when the checkbox is ticked.

Toasts
- Saved (success): `<Thing> saved. <Effect>.` e.g. "Reimbursement rules saved. They apply to documents submitted from now on."
- Deleted (success): `<Thing> deleted. <What is kept>.`
- Cancelled (info): `Cancelled. <Thing> was not changed.` (default "Cancelled. Nothing was changed.")
- Failed (error): `<Thing> was not saved: <plain reason>.` Never raw exception text; unexpected errors: "Could not save <thing>. Nothing was changed. Try again, or contact IT if it keeps happening." plus a log entry.
- Partial (warning): `N saved, M not saved: <reason>.`

### 4.2 Minimal code changes
1. Keep ONE global `data-confirm` handler (`partials/confirm-modal.blade.php`, already in WT) and remove the four copies (payroll `tabs.blade.php:34-48`, `legal/workflow/_confirm.blade.php`, `legal/contracts/components/confirm-forms.blade.php`, `recruitment/components/confirm-forms.blade.php`). Minimum safe step: add `|| window.__ecConfirmForms` and `confirmed` guards to the recruitment copy, and load the global handler BEFORE `@yield('content')` (small `public/js/confirm-forms.js` with `defer` in head, or a `<script>` above the content) so Payroll and Legal Workflow also get cancel toasts and shared defaults.
2. Default confirm for guarded forms: in that handler, a POST form with `data-unsaved-label` (and no `data-confirm`, no `data-no-confirm`) gets title `Save ${label}?`, body "Your changes will be saved and apply from now on.", ok "Save". Every guarded settings page (Cash Advance, Overtime, PR, Letters, Offering, Recruitment, Contracts) then follows the standard with zero per-page edits; pages override with `data-confirm`. Add `data-unsaved-label` to the unguarded pages that need it.
3. Guard flag fix in `unsaved-guard.blade.php`: clear `saving` when the submit is cancelled (confirm handler dispatches `confirm:cancelled`; the guard sets `saving = null`), or set `saving` only after the confirm resolved.
4. Escape toast text: build the message node with `textContent` (or `escapeHtml`) at `dashboard.blade.php:1967`; allow HTML only through an explicit `{html:true}` option.
5. Layout: render a one-line toast for `$errors->any()` ("Please fix N field(s) and try again.") after `dashboard.blade.php:2017` so pages without `@error` (Reimbursement Object forms) still report failure.
6. Cancel feedback for non-`data-confirm` dialogs (attendance :435-466, ESS :215-227, cash-advance dialogs, `confirmSubmit`, `closeModalGuarded`): add `opts.cancelToast` to `showConfirm` (toast in `onCancel`, ~`confirm-modal.blade.php:100`) with a default message, and pass it from `confirmSubmit`.
7. Shared workflow editor (`editor.blade.php`): build the confirm body in JS from the form state (removed rows, ticked apply-to-open with count). Fixes all four modules at once.
8. Apply the default (#2) or an explicit `data-confirm` to: Cash Advance, Overtime, PR, PO, Attendance (replace custom), BPJS, PPh21, Legal Basis, 2FA enforcement, ESS save, AI, Seasonal.

---------------------------------------------------------------------
## 5. Safety findings

### 5.1 Destructive / financial / security settings changed without confirmation (HEAD; WT only fixes Reimbursement)
- 2FA enforcement can be set to "nobody" in one click: `admin/two-factor-enforcement-settings.blade.php:33`, `TwoFactorEnforcementSettingsController.php:36-68` (audit log entry exists, no dialog).
- BPJS rates (new effective version): `finance/bpjs/settings.blade.php:31`, `BpjsSettingsController.php:75`.
- PPh21 tables: `finance/pph21/settings.blade.php:83,118,149,204`.
- Legal Basis rates source / switches (change what payroll reads): `finance/legal-basis/settings.blade.php:28,81,130`, `LegalBasisController.php:72-98` (checkbox gate only).
- Payroll rules save (`finance/payroll/settings.blade.php` first form; the switch-off IS confirmed, :53).
- Cash Advance, Overtime, Purchase Request, Purchase Order settings saves (limits, thresholds, approvers).
- Approval-workflow editor: step removal (`editor.blade.php:288`) and "apply to open documents" (`:143-148`) change in-flight approvals with one Save and no statement of effect, in four modules; `SyncsApprovalWorkflow.php` (~:140) deletes rows silently.
- Legal workflow toggles `legal/workflow-settings.blade.php:91,134`.
- Recruitment calendar source switch (`RecruitmentSettingController.php:46`) and autosaving option rows (`recruitment/settings.blade.php:158`, `offering/settings.blade.php:153`) write on change by design.
- ESS settings AJAX save (`ess-settings/index.blade.php:144`, `saveEssSettings()`) changes sidebar visibility for everyone with no confirm and no guard.

### 5.2 Flash messages with unescaped user text or raw exception text
- Toast sink is `innerHTML` (`dashboard.blade.php:1967`), so any flash containing user-controlled text is an XSS vector. Examples: `EssSettingsController.php:299` (group label), `EmployeeSettingsController.php:84,101,139` (option labels), `ContractSettingController.php:95,127`, `ReimbursementObjectSettingController.php:141,154` (typed ticket number / labels), `RecruitmentSettingController.php:62,148`, `Pph21SettingsController.php:118`, letter flashes with names and emails (`LetterComposeController.php:140`, `LetterRequestController.php:145,161`).
- Raw exception text reaches users (69 `getMessage()` sites in controllers feed `with()` / `withErrors` / JSON). Settings-related: `Finance/PayrollSettingsController.php:264,293`, `Finance/BpjsSettingsController.php:102` (JSON 422), `Finance/LegalBasisController.php:112,147,158`, `OffboardingSettingsController.php:62,75`, `ExternalWorkflowSettingsController.php:59,79,111`, `Legal/ContractSettingController.php:69,92,124`, `SettingsController.php:109,192,243` (JSON `error`), mail failures `{$error}` in `LetterComposeController.php:140` and `LetterRequestController.php:145,161`. Some are deliberate domain exceptions with user-written messages; the mailer / IO ones and `SettingsController` are not. Reimbursement Settings controllers do not echo exception text.

### 5.3 CSRF
A scan of every Blade file with a POST `<form>` found none lacking `@csrf` / `csrf_field` / `_token` (file-level check only; per-form check in multi-form files and the CSRF header on AJAX posts such as ESS `saveEssSettings` and sidebar favorites were not verified).

### 5.4 Deletes by GET
No `Route::get/any` with destroy/delete/remove in path or action was found in `routes/*`. Settings delete forms are POST with `@csrf` (some hidden forms, e.g. `attendance/settings.blade.php:367`, `letters/settings.blade.php:644-653`, `branches/index.blade.php:160`). The only GET alias, `routes/hr-general.php:691`, redirects.

### 5.5 Other
- Double confirm on recruitment-partial pages after the global handler (1.1 defect 2); guard `saving` defect (1.3); silent validation failures on Reimbursement Object forms (section 2 #2).
- Localisation inconsistency: Seasonal flashes use i18n, all others plain English.

---------------------------------------------------------------------
## 6. Files to change for Reimbursement Settings (minimal)
1. `resources/views/partials/confirm-modal.blade.php` (global handler install order, defaults, cancel toast; mostly in WT).
2. `resources/views/hr-general/recruitment/components/confirm-forms.blade.php` (add guard to avoid double dialog).
3. `resources/views/hr-general/recruitment/components/unsaved-guard.blade.php` (reset `saving` on cancel).
4. `resources/views/dashboard.blade.php` (escape toast at :1967; `$errors` toast).
5. `resources/views/hr-general/partials/approval-workflow/editor.blade.php` (dynamic confirm text with removed/apply-to-open counts; drop duplicate banner :86-90).
6. `resources/views/hr-general/reimbursement/components/object-settings.blade.php` (show `$errors` for forms 2-5).
7. `app/Http/Controllers/HR_General/ReimbursementObjectSettingController.php` (`syncOffices` partial save -> warning).
8. `app/Http/Controllers/HR_General/ReimbursementSettingController.php` (`syncSteps`: removed-step count in the note; info flash when apply_to_open is ticked but nothing applied).
