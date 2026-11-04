---
key: sales-compliance
chapter: business-legal
order: 30
title: Sales Compliance
summary: Send each sales deal through a compliance review before it is signed, and decide on deals sent to you.
requires: [legal.sales-compliance, legal.sales-compliance.approve, legal.sales-compliance.settings, "@approver"]
updated: 2026-11-03
---
Sales Compliance is where every sales deal is checked before it is signed. The sales person describes the deal, states the agreed price against the MRP, attaches the price documents and answers the anti-bribery and conflict-of-interest declarations. The people named on the approval steps then approve or reject it.

## Who uses this

- **Sales** fill in and submit their own requests. They see only the requests they created.
- **Compliance reviewers** are the positions or people named on an approval step. They decide on the requests that reach them and need no menu permission for it. They also find those requests in [[my-approvals]].
- **Administrators** set the approval steps and questions in `Settings`.

## Before you start

- Have the agreed-price document and the MRP document ready (PDF, JPG, PNG or WEBP, up to 10 MB each).
- A person cannot decide a request they submitted themselves. If you start a request on behalf of a sales person, someone else must decide it.

## Step by step

### Send a deal for review

1. Open `Business & Legal` › `Sales Compliance` and click `New Request`.
2. Fill in `Project name`, `Customer`, `Customer PIC` and the meeting and hospitality details. Pick the customer from the list or type a new name.
3. Enter `Final (agreed) price` and `MRP price`. The page shows the difference. If the two differ, write a `Price justification`.
4. Answer every question marked with a red star and click `Save draft`.
5. In `Documents`, upload the agreed-price document and the MRP document. A new upload of either replaces the old one.
6. Click `Submit for review`. The status changes to `In Review` and the first approver is notified.

> [!NOTE]
> While a request is `In Review` you cannot edit it. Use `Cancel request` to withdraw it.

### Decide on a request

1. Open the request from [[my-approvals]] or from the `Requests` tab.
2. Read the deal, the price against the MRP, the documents and the declarations.
3. Write a note, then click `Approve` or `Reject`. A note is required when you reject.
4. After the last step approves, the request becomes `Approved`. A rejection ends the review at once.

### Correct a rejected request

1. Open the rejected request and read the note.
2. Click `Reopen to correct`, change what is needed and submit again. The earlier round stays visible under `Approval steps`.

### Change who approves (Settings)

1. Open the `Settings` tab.
2. Under `Approval steps`, add a step, choose a position or named people, and set the order. A request already in review keeps the steps it started with.
3. Under `Questions`, edit the wording or switch a question off. Answers already given stay readable.

## What happens next

The sales person is notified of every decision. An approved request is the record that the deal passed compliance review.

## Common questions

**Why can I not approve my own request?** The review exists to give a second pair of eyes, so the person who submits a request never decides it.

**Why do I see only some requests?** Sales see the requests they created. People who decide on requests, and administrators, see all of them.

## Related topics

- [[my-approvals]]
- [[vendor-onboarding]]
- [[business-partners]]

<!-- sources: routes/legal-compliance.php, app/Http/Controllers/Legal/SalesComplianceController.php, app/Http/Controllers/Legal/WorkflowSettingsController.php, app/Services/Legal/SalesComplianceService.php, app/Services/Legal/StepWorkflowService.php, resources/views/legal/sales-compliance/index.blade.php, resources/views/legal/sales-compliance/form.blade.php, resources/views/legal/sales-compliance/show.blade.php, resources/views/legal/workflow-settings.blade.php -->
