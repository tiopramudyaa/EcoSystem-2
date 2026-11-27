---
key: mg-flow-customer-mandays
chapter: management
order: 18
title: Customer Mandays Approval
summary: Choose who approves Level 1 and Level 2 of a Customer Mandays proposal before it goes to the customer.
requires: [management.flow.customer-mandays]
updated: 2026-11-27
---
`Management` › `Flow` › `Customer Mandays Approval` sets the two internal approval levels every Customer Mandays proposal goes through after the PIC submits it. The Level 2 approver is the one who sends the proposal to the customer, or approves it directly.

## Who uses this

Administrators, and any role given `Management` › `Flow` › `Customer Mandays Approval` in Menu Access. When the page is first installed only EC Administrator has it.

## Before you start

- Decide who should approve each level. A level can go to everyone holding a position (role), or only to people you name.
- Check that the position has at least one active person. Otherwise nobody could approve that level.
- By default Level 1 is `Admin Approval` (EC Administrator) and Level 2 is `Helpdesk Review` (Delivery Support Service Helpdesk).

## Step by step

1. Open `Management` › `Flow` › `Customer Mandays Approval`. The `Approval Workflow` card shows one row per level, numbered 1 and 2.
2. In `Step Name`, type the name people see while the proposal waits, for example `Admin Approval`. The ticket then shows `Waiting Admin Approval`.
3. In `Position`, choose the role that approves this level. The line under it shows how many active people hold that position.
4. To limit the level to certain people, open `Name` and tick them. Leave `Name` empty to let everyone holding the position approve. When names are chosen, the line under `Position` shows the roles those people hold.
5. Use the arrows next to the level number to swap the two levels.
6. Select `Save Workflow`. Both levels are saved together.

## Rules to know

- There are always exactly two levels. You cannot add, remove or switch off a level.
- Every level needs a step name (at most 100 characters) and either a position or at least one name. A position that nobody holds is refused when no names are chosen.
- When names are chosen, only those people can approve that level, even if others hold the position.
- One person per level is enough. Whoever acts first decides that level.
- Your changes also apply to proposals already waiting for approval. Each level they have not decided yet takes the new step name and approvers of the same level number. Levels already approved keep their approver, date and note. Rejected proposals, proposals ready to send, sent or approved, and older versions are not changed. The page tells you how many proposals are waiting right now, and the message after saving tells you how many now follow the new approvers.
- If the level a proposal waits at gets new approvers, only the people added get a bell notification. People removed are not told, and their earlier notification stays.
- Swapping the two levels also applies to waiting proposals by level number. A person who already approved Level 1 can be asked to approve Level 2 as well if the swap makes them its approver.
- The levels run in order: Level 2 cannot act until Level 1 has approved.
- An approver may change the mandays numbers before approving. The new numbers are saved with the approval.
- Rejecting needs a reason of at least 5 characters. The proposal is then closed as `Rejected`, any level after it is marked as skipped, and the PIC revises it as a new version.
- The PIC may approve their own proposal if they are an approver of that level.
- At Level 2 the approver acts like Helpdesk did before the two levels existed: `Send to Customer` approves Level 2 and sends the proposal to the customer, and `Approve` makes it `Approved` at once without waiting for the customer. Level 1 only has `Approve` and `Reject`, and its `Approve` moves the proposal to Level 2.
- Each approver gets a bell notification when a proposal reaches their level. The PIC gets one when a proposal is rejected.

## What happens next

New proposals, and the undecided levels of proposals already waiting, follow the saved levels. When it is their turn, approvers open `Review Mandays Proposal` on the ticket and find the `Approve` and `Reject` buttons there, plus `Send to Customer` at Level 2, even if their role has no review access. The full proposal flow on the ticket is described in the topic Customer Mandays and Resolution Days.

## Common questions

**I changed the approver. What happens to a proposal that was already approved at Level 1?** Level 1 keeps the person who approved it. Only Level 2, which is still waiting, moves to the new approver.

**Why can I not add a third level?** The flow is fixed at two levels on purpose.

<!-- sources: routes/web.php, app/Http/Controllers/CustomerMandaysFlowController.php, app/Services/CustomerMandaysApprovalService.php, resources/views/management/flow/customer-mandays.blade.php, database/migrations/2026_11_13_000001_create_customer_mandays_approval_tables.php -->
