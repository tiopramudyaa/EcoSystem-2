---
key: ticket-mandays
chapter: work-service
order: 14
title: Customer Mandays and Resolution Days
summary: The two estimates on a ticket, who approves each one and how they move from draft to approved.
requires: [ticket.propose-mandays-customer, ticket.propose-mandays-resolution, ticket.review-mandays, ticket.head-mandays, ticket.approve-resolution-days]
updated: 2026-11-27
---
A ticket that needs effort estimates carries two different proposals. They are easy to mix up, so keep them apart.

## Who uses this

Ticket leads and consultants who propose, the two approval levels and Helpdesk for Customer Mandays, and heads who approve Resolution Days. The buttons you see depend on your role and on whether it is your turn to approve.

## Before you start

- Open the ticket and find the `Mandays` section.
- The modules you can choose in a proposal come from the skills of the ticket lead and the team members, so check that the ticket has the right team first.

| | Customer Mandays | Resolution Days |
|---|---|---|
| What it is | Days of work the **customer** agrees to | The internal estimate to resolve the ticket |
| Prepared by | The person in charge of the ticket | The person in charge of the ticket |
| Approved by | Level 1 and Level 2 inside the company, then the customer | The head |

## Step by step

### Customer Mandays

![A ticket page with the Mandays section: Propose Mandays, Propose Resolution Days, Review Mandays Proposal and Review Resolution Days](images/guide/ticket-detail.png)

*Screenshot with sample data.*

1. Select `Propose Mandays` and fill in the details. Save it as a `Draft` first.
2. Check the numbers and select `Submit for Approval`. The badge shows `Level 1`, and the proposal shows `Waiting` followed by the name of Level 1, for example `Waiting Admin Approval`.
3. The Level 1 approver opens `Review Mandays Proposal` on the ticket, selects the version, then `Review This Version`. The badge next to it shows `Your turn` when it is their turn. They can adjust the mandays and select `Approve`, or write a reason in `Approval Notes` and select `Reject`.
4. After Level 1 approves, the proposal waits for Level 2 (by default Helpdesk). The Level 2 approver opens `Review Mandays Proposal`, can adjust the mandays, and chooses one of:
   - `Send to Customer`: approves Level 2 and sends the proposal to the customer by chat and email right away. The status becomes `Sent to Chat`. Before sending, you can tick supporting documents under `Documents for the Customer`, for example a CR Form, SoW or WO. The list comes from the ticket's `Deliverable Documents`, and the links of the ticked documents go with the proposal.
   - `Approve`: approves the proposal directly without waiting for the customer, for example when the customer already agreed by email. The status becomes `Approved`.
   - `Reject`, with a reason in `Approval Notes`.
5. After it is sent, the customer approves through the link they receive, and the proposal becomes `Approved`. While it is `Sent to Chat`, a Level 2 approver can still select `Approve` if the customer agreed another way.
6. If the customer rejects, the status becomes `Ready to Send` with the customer's reason. Helpdesk revises the proposal, ticks the documents to include under `Documents for the Customer`, and selects `Revise & Resend`.
7. If a level rejects, the status becomes `Rejected` and the reason is shown to the PIC. The PIC selects `New Version`, revises the proposal and submits it again.
8. Helpdesk, or a Level 2 approver while the proposal is `Ready to Send` or `Sent to Chat`, can select `Cancel Proposal`. A revision creates a new version.

Each version shows an `Approval Trail` with every level, who acted, when, and their note.

### Resolution Days

1. Select `Propose Resolution Days`, fill in the details and save the `Draft`.
2. Submit it. The status becomes `Pending Head`.
3. The head uses `Review Resolution Days` to approve it, or rejects it with a reason.

## Rules to know

### Customer Mandays

- A proposal needs a total above 0 mandays. EcoSystem refuses to save an empty one.
- A submitted proposal goes through two approval levels in order before it can be sent to the customer. Who approves each level is set in `Management` › `Flow` › `Customer Mandays Approval`. By default Level 1 is EC Administrator and Level 2 is Helpdesk.
- Only the approvers of the level that is waiting can approve or reject it. Level 2 cannot act before Level 1. One approver per level is enough.
- An approver may change the mandays before approving. The total must stay above 0.
- Rejecting needs a reason of at least 5 characters. The version is closed as `Rejected` and the next level is skipped. The PIC creates a new version to revise it.
- The PIC may approve their own proposal when they are an approver of the waiting level.
- If the flow is changed while a proposal waits, its undecided levels follow the new approvers. Levels already approved stay as they were.
- Submitting is refused when a level has no approver set. Ask an administrator to set up the flow.
- You cannot edit a proposal while it is waiting for approval, waiting for Helpdesk to resend it, or after it was sent to the customer. Wait for the answer, or ask Helpdesk to cancel it.
- At Level 2 the approver acts like Helpdesk did before the two levels existed: `Send to Customer` approves Level 2 and sends in one step, and `Approve` makes the proposal `Approved` at once. Level 1 approvers do not get `Send to Customer`. The Level 2 approver needs no `Menu Access` function for either button: it follows the approvers set in the flow.
- A proposal becomes `Approved` when the customer approves it, or when a Level 2 approver approves it directly. Directly means at Level 2, or later on the latest version while it is `Sent to Chat`, but not right after the customer rejected it. Approving this way sets the ticket mandays to the proposal total, the same as the customer's approval.
- Helpdesk users who are not Level 2 approvers cannot approve a proposal directly.
- Supporting documents are optional. Only documents of this ticket in `Deliverable Documents` that have a file can be chosen, at most 20 per send. Upload a document there first if it is not in the list.
- The ticked documents are listed with their links in the proposal email and chat message, and the customer portal receives the same list. In `Deliverable Documents` they become `Sent`, the same as sending them from there.
- A resend replaces the document list of that version with the new selection. After sending, `Review Mandays Proposal` shows them under `Documents Sent`.
- The customer can reject the proposal from the link they receive and may write a reason. It then returns to Helpdesk as `Ready to Send`, who revises it and sends it again. A resend does not go through Level 1 and Level 2 again.
- On a `Change Request` ticket only the ticket lead can propose Customer Mandays. Heads are not held to this.
- Cancelling a proposal needs the cancel permission. Level 2 approvers can also cancel the latest version while it is `Ready to Send` or `Sent to Chat`, without that permission. Cancelling one that is already approved needs a separate permission, and the customer is not told automatically, so Helpdesk follows up by email.
- Proposing Customer Mandays needs the `Propose Mandays Customer` function and proposing Resolution Days needs the `Propose Resolution Days` function in `Menu Access`, on top of being the ticket lead or a member. Saving a review needs `Save Draft` or `Send to Customer`, because sending saves your edits first. Approving a level does not need a function in `Menu Access`: it follows the approvers set in the flow. Without the function the request is refused, even if it is sent directly.
- When a Customer Mandays proposal is submitted, the Level 1 approvers get a bell notification. When Level 1 approves, the Level 2 approvers are notified. When a level rejects, the PIC is notified. When Resolution Days is submitted, everyone whose role has the head or Resolution Days approval permission is notified.
- Sub-actions in the review screen (edit, save draft, send to customer, cancel) each have their own permission in `Role Access`. A role that can open the review but lacks these sees the proposal without the action buttons.
- Approvers can also decide in the EcoSystem Lite mobile app under the same rules. A proposal that changed after they opened it must be reloaded first.

### Resolution Days

- Only the ticket lead or a member of the ticket can prepare and submit them. You can keep editing the proposal until it is approved.
- On a `Change Request` ticket you cannot submit Resolution Days, and the head cannot approve them, until the Customer Mandays are approved.
- The head (Head of Support or the administrator) fills in the approved days for each person. This is required: approval cannot go through without it.
- If the approved numbers would leave someone with negative remaining MD, EcoSystem warns first and asks the head to confirm before approving.
- The head can also reject, with a reason. The proposal is then corrected and submitted again.

### Effect on timesheets

- The approved days are each person's quota on the ticket. A consultant cannot log more MD than the remaining quota, and cannot submit a timesheet on a ticket where no days were approved for them. If the proposal is edited and goes back to `Draft`, the days already approved stay as the quota until Head approves again. See the timesheet topic for the details.

## What happens next

Approved days are used for the workload shown in `My Tasks` and in `Consultant Workload`, and for the quota in the `MD Validation` report. A rejected Resolution Days proposal is corrected and sent again.

## Common questions

**Which one do I need?** Use Customer Mandays for what the customer pays or agrees to, and Resolution Days for the internal plan.

**I cannot see the review buttons.** Reviewing is only for Helpdesk and heads. Ask your administrator if you think you should have them.

**I am an approver but see no `Approve` button.** The button only appears while the proposal waits at your level. If it waits at another level, it is not your turn. Reload the ticket after the proposal is submitted.

**The document I want to send is not under `Documents for the Customer`.** Only documents with a file in this ticket's `Deliverable Documents` appear. Upload it there, then open `Review Mandays Proposal` again.

**The customer agreed by email but the proposal still shows `Sent to Chat`.** Ask a Level 2 approver to open `Review Mandays Proposal` and select `Approve`. Who counts as Level 2 is set in the flow.

<!-- sources: routes/web.php, routes/api.php, routes/lite.php, app/Http/Controllers/MandaysController.php, app/Http/Controllers/Lite/LiteCustomerMandaysController.php, app/Services/CustomerMandaysApprovalService.php, app/Services/LiteCustomerMandaysPresenter.php, app/Http/Controllers/TicketDeliverableController.php, resources/views/ticket/show.blade.php, resources/views/ticket/task.blade.php -->
