---
key: reimbursement-management
chapter: finance
order: 40
title: Reimbursement Management
summary: Review, approve and pay employee reimbursements, and set the rules and approval steps for them.
requires: [general.reimbursement, general.settings.reimbursement, general.approval-workflow.reimbursement]
updated: 2026-11-20
---
Reimbursement Management is where HR, Finance and approvers handle the reimbursement requests that employees submit. It has three tabs: `Dashboard`, `Reimbursement Management` and `Settings`.

## Who uses this

Approvers, HR and Finance. You only see the tabs and buttons your access allows. If something is missing, ask an administrator.

## Before you start

- Employees submit their own requests in `My Workspace` › `Reimbursement` (see [[my-reimbursement]]).
- The approval steps must exist in `Settings` › `Approval Workflow`. See [[approval-workflows]].

## Step by step

### Find documents

1. Open `Reimbursement Management` › `Reimbursement Management`.
2. Read the cards. Click `Waiting for You` or `Awaiting Payment` to filter the list; click again to clear.
3. Choose a `Month`, or use the filter icons in the table header. Click `Reset Filter` to start again.

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
> If the reimbursement is already in a payroll period, it is paid with salary. Record a payment here only if it was also paid separately.

### Create, import and export

- `New RB` creates a document on behalf of an employee.
- `Import RB` loads many documents from a file template.
- `Monthly Export` downloads the filtered list (current month without a date range). A document can be printed with `Print Form`.

### Delete a document

Open the document and click `Delete`, then give a reason. Deleted documents stay visible under the `Deleted` status filter. Deleting needs special access.

### Set the rules (Settings)

1. Open the `Settings` tab.
2. In `Approval Workflow`, add or arrange the steps and click `Save Workflow`.
3. In `Reimbursement Rules`, set the limits and required fields, then click `Save Settings`. A rule set to flag only highlights the document; block refuses it. A value of 0 means no limit.

### Set up Objects and funds (Settings)

Each item is charged to an Object: `Office`, `Project` or `Support` ticket. The Object decides who approves and which fund is checked.

1. Open the `Settings` tab and find `Objects and Funds`.
2. Tick `Use Objects and funds for new reimbursements`. Leave it off to keep the previous behaviour.
3. Under `Project approvals`, choose who approves (`Project Admin`, `Project Manager`), the order, and a fallback role and person for projects that have none.
4. Under `Support approvals`, choose whether the `Ticket Lead` and the `Support Manager` approve, and the fallback.
5. Under `Who can charge costs to a project or ticket`, choose `Team (lead and members)`, `Lead only` or `Everyone` for projects and for support tickets.
6. Under `When an amount exceeds the remaining fund`, choose `Warn` or `Block` for each Object. Click `Save flow and policy` and confirm the dialog.
7. In `Office funds and responsible person`, give every office a responsible person (role, then person). Turn `Fund active` on and enter a monthly amount only for offices that have a fund. Click `Save offices`.
8. In `Project and ticket fund allocations`, add an allocation for a project or ticket, with an optional category such as Transport. For a project, `Amount comes from` can be `Project plan (read only)`: pick a cost line and the amount follows the project's Plan Cost. Otherwise choose `Typed amount`. Click `Add allocation`. Use `Update` to change a typed amount or switch it off.
9. Under `Exceptions for one project or ticket`, set a different rule for a single project or ticket and click `Save exception`.

Every save asks for confirmation first; cancelling changes nothing.

### Void an approved document

1. Open an approved document that is not paid. If it was paid, click `Reverse payment` first.
2. Under `Void`, write the reason (at least 10 characters) and click `Void document`.
3. The document shows `Void` and the fund it used goes back.

## Rules to know

- The Object concept applies only to documents submitted while `Use Objects and funds for new reimbursements` is on. Documents already submitted keep their flow.
- Approvers are assigned automatically at submission and do not change afterwards. Project items go to the `Project Admin` and `Project Manager`, support items to the `Ticket Lead` (and the `Support Manager` if on), office items to the office's responsible person. These steps come first, then the steps in `Approval Workflow`.
- Who can charge: with `Team`, the project lead, delivery manager and members, or the ticket lead and members, may charge to it. With `Lead only`, just the lead. Office is not restricted. The rule applies to the document owner, also when HR creates the document for them. A project or ticket the requester may not use is not offered in the search. An exception replaces the default for that one project or ticket.
- The same person is asked only once. A requester who is also an approver skips that step, and the skip is recorded.
- Submitting is refused, with the reason, when a project is closed, an approver is inactive, a project or ticket has no approver and no fallback, or an office has no responsible person.
- A fund decreases only when the document is fully approved, not at submission.
- A document that uses Objects shows a `Budget check` panel, so you see what is left if you approve. Red means below zero (`Over the fund`); under `Block` the last approval is refused. The requester does not see it.
- Remaining fund = allocation minus approved documents, checked again just before the last approval.
- An Object without a fund (office with `Fund active` off, project or ticket without an allocation) is not checked but still goes through approval.
- Office funds are monthly. Project and ticket allocations have no end date. An allocation with no category covers every category without its own.
- With `Warn`, an amount above the remaining fund can still be submitted with a reason (at least 10 characters); reviewers see it and the document is marked `Over fund allocation`. With `Block`, it is refused.
- An allocation cannot be set lower than what approved documents already used.
- A category is only a label, such as Transport. The requester sees `Fund category` only when the project or ticket has an allocation with a category. The `Setup checklist` shows what is still missing.
- Only a user who can record payments can void, and only an approved, unpaid document. Voiding needs a reason and cannot be undone here.
- An allocation that follows the project plan uses the released amount of the chosen cost line (or its budget) and updates by itself. It never changes the plan. If the line is removed, the last amount stays. Tickets have no plan budget, so type the amount.
- `Approval Workflow` holds only the steps for every reimbursement, such as Accounting, Cashier and the final approver. Do not add project, ticket or office approvers there.
- A step added later and applied to documents in progress is placed after the steps they already have.
- Accounting and Cashier appear on the printed document when a workflow step has `Signs as` set to Accounting or Cashier. Add those steps in `Approval Workflow`; until then the two columns stay empty.
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
