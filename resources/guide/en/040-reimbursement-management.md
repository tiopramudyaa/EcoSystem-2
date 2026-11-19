---
key: reimbursement-management
chapter: finance
order: 40
title: Reimbursement Management
summary: Review, approve and pay employee reimbursements, and set the rules and approval steps for them.
requires: [general.reimbursement, general.settings.reimbursement, general.approval-workflow.reimbursement]
updated: 2026-11-19
---
Reimbursement Management is where HR, Finance and approvers handle the reimbursement requests that employees submit. It has three tabs: `Dashboard`, `Reimbursement Management` and `Settings`.

## Who uses this

Approvers, HR and Finance. You only see the tabs your role allows, and each button appears only when you have the matching access (create, export, import, approve, delete). If something is missing, ask an administrator.

## Before you start

- Employees submit their own requests in `My Workspace` › `Reimbursement` (see [[my-reimbursement]]).
- The approval steps must exist in `Settings` › `Approval Workflow`. See [[approval-workflows]].

## Step by step

### Find documents

1. Open `Reimbursement Management` › `Reimbursement Management`.
2. Read the cards: `Waiting for You`, `Pending Total`, `Approved`, `Awaiting Payment` and `Rejected`. Click `Waiting for You` or `Awaiting Payment` to filter the list; click it again to clear.
3. Choose a `Month`, or use the filter icons in the table header to search by document, requester, approver or status.
4. Rows that need attention are highlighted. Click `Reset Filter` to start again.

### Approve or reject

1. Open the document.
2. Check the items, receipts and supporting link under `Reimbursement Item Details`.
3. If it is your turn, click `Approve & Sign`, or click `Reject`, write the reason (at least 5 characters) and click `Confirm Rejection`.
4. The `Approval Trail` shows who acted and when.

### Record the payment

1. Open an approved document that shows `Awaiting payment`.
2. Under `Record payment`, enter `Paid on` and a `Transfer reference`. Add a note if needed.
3. Click `Mark as paid`. The document shows `Paid` with the date.

> [!WARNING]
> If the reimbursement is already part of a payroll period, the page warns you. It is then paid with salary. Record a payment here only if it was also paid separately, or it would be paid twice.

### Create, import and export

- `New RB` creates a document on behalf of an employee.
- `Import RB` loads many documents from a file template.
- `Monthly Export` downloads the filtered list. Without a date range it exports the current month. A single document can be exported to Excel or printed with `Print Form`.

### Delete a document

Open the document and click `Delete`, then give a reason. Deleted documents stay visible under the `Deleted` status filter. Deleting needs special access.

### Set the rules (Settings)

1. Open the `Settings` tab.
2. In `Approval Workflow`, add or arrange the steps and click `Save Workflow`.
3. In `Reimbursement Rules`, set limits such as the request window, receipt age, items and amounts per document, and whether a supporting link or receipt number is required. Click `Save Settings`.

A rule set to flag only highlights the document. A rule set to block refuses it. A value of 0 means no limit.

### Set up Objects and funds (Settings)

Each reimbursement item is charged to an Object: an `Office`, a `Project`, or a `Support` ticket. The Object decides who approves the document and which fund is checked.

1. Open the `Settings` tab and find `Objects and Funds`.
2. Tick `Use Objects and funds for new reimbursements`. Leave it off to keep the previous behaviour.
3. Under `Project approvals`, choose who approves (`Project Admin`, `Project Manager`), the order, and a fallback role and person for projects that have none.
4. Under `Support approvals`, choose whether the `Ticket Lead` and the `Support Manager` approve, and the fallback.
5. Under `When an amount exceeds the remaining fund`, choose `Warn` or `Block` for each Object. Click `Save flow and policy`.
6. In `Office funds and responsible person`, give every office a responsible person (role, then person). Turn `Fund active` on and enter a monthly amount only for offices that have a fund. Click `Save offices`.
7. In `Project and ticket fund allocations`, add an allocation for a project or ticket, with an optional category such as Transport, and click `Add allocation`. Use `Update` to change the amount or switch it off.

### Void an approved document

1. Open an approved document that is not paid. If it was paid, click `Reverse payment` first.
2. Under `Void`, write the reason (at least 10 characters) and click `Void document`.
3. The document shows `Void`, the fund it used goes back, and the requester is told.

## Rules to know

- The Object concept applies only to documents submitted while `Use Objects and funds for new reimbursements` is on. Documents already submitted keep their flow.
- Approvers are assigned automatically when the document is submitted and do not change afterwards. Project items go to the `Project Admin` and `Project Manager`, support items to the `Ticket Lead` (and the `Support Manager` if turned on), and office items to the office's responsible person. These steps come first, followed by the steps in `Approval Workflow`.
- The same person is asked only once, even if they appear on several items. A requester who is also an approver skips that step, and the skip is recorded.
- Submitting is refused, with the reason, when a project is closed, a required approver is no longer active, a project or ticket has no approver and no fallback is set, or an office has no responsible person.
- A fund decreases only when the document is fully approved, not when it is submitted. Documents still waiting do not reduce it.
- Remaining fund = allocation minus approved documents. Just before the last approval the remaining fund is checked again, because another document may have been approved first.
- An Object without a fund is not checked: an office with `Fund active` off, or a project or ticket without an allocation. It still goes through approval.
- Office funds are monthly and follow the date of the document. Project and ticket allocations have no end date. An allocation with no category covers every category that has no allocation of its own.
- With `Warn`, an amount above the remaining fund can still be submitted if the requester writes a reason (at least 10 characters). The reviewers see the reason and the document is marked `Over fund allocation`. With `Block`, it is refused and the message names the remaining fund.
- An allocation cannot be set lower than what approved documents already used.
- Only a user who can record payments can void, and only an approved document that is not paid. Voiding needs a reason and cannot be undone from this screen. A voided document cannot be paid.
- Project allocations are separate from the project cost plan and never change it.
## What happens next

Approved documents wait for payment. If [[payroll]] includes approved reimbursements, they are paid with salary.

## Common questions

**Why can't I approve a document?** Only the person whose step is current can act. The page shows the current step.

**Do rule changes affect existing documents?** Rule changes apply to new requests. Documents already in progress keep their steps.

## Related topics

- [[my-reimbursement]]
- [[approval-workflows]]
- [[payroll]]
- [[my-approvals]]

<!-- sources: routes/hr-general.php, app/Http/Controllers/HR_General/ReimbursementController.php, app/Http/Controllers/HR_General/ReimbursementSettingController.php, resources/views/hr-general/reimbursement/*.blade.php, app/Models/Reimbursement/ReimbursementRequest.php -->
