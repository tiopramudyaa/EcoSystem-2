---
key: ticket-status
chapter: work-service
order: 13
title: Ticket status and closing
summary: What each ticket status means, who can change it, and what happens to the customer and the SLA clock when it changes.
requires: [tickets.inbox]
updated: 2026-11-02
---
Every ticket has one status. Changing it tells the customer where the ticket stands and also drives the SLA clock, so it is worth getting right.

## Who uses this

Helpdesk and administrators change the status. Ticket leads, members and heads read it. The status control is on the ticket page and appears only for roles that were given permission to edit ticket fields in Menu Access.

## Before you start

- Read the ticket first. Choose the status that matches who has to act next.
- Changing the status does not ask for confirmation of the next steps, so check the customer-facing effect below before you close or cancel.

## Step by step

1. Open the ticket from `Ticket`.
2. Change the status in the ticket details. The choices are:
   - `Open`: new, nobody has started yet.
   - `In Process`: your team is working on it.
   - `Waiting on Customer`: the team needs an answer from the customer.
   - `Waiting on 3rd Party`: the team waits for a vendor or SAP.
   - `Waiting to Confirmation`: the solution was given and the customer must confirm.
   - `Hold`: work is paused on purpose.
   - `Cancelled`: the ticket is withdrawn.
   - `Closed`: the ticket is finished.
3. Check the line the system adds to the chat when you choose `Closed` or `Cancelled`.

## Rules to know

- Any status can be changed to any other status. There is no fixed order, and a closed ticket can be reopened by choosing another status.
- Choosing `Closed` stamps the ticket's end date. Reopening a closed ticket clears that end date.
- When the ticket has a customer, they receive a notification in the customer portal each time the status changes. For `Closed` and `Cancelled` they also get an email, if an email address is known, and the chat records a line that says the status changed to Closed, by whom and when.
- The SLA clock follows the status. `Waiting on Customer`, `Waiting to Confirmation`, `Hold` and `Waiting on 3rd Party` pause the clock, because the ball is with the customer or the third party. `Open` and `In Process` run it. `Closed` and `Cancelled` end it: a cancelled ticket always counts as met. The details are in the SLA Report topic.
- A hidden ticket keeps its status but is left out of the lists and reports. See `Hidden Tickets`.

## What happens next

The ticket moves to the matching tab and filter. The SLA report and the open-ticket reminder read the new status straight away.

## Common questions

**I closed a ticket by mistake.** Change the status back, for example to `In Process`. The end date is cleared, but the customer has already received the closing email.

**Why did the SLA time stop growing?** The ticket is in a waiting status or on hold. The clock restarts when the status returns to `Open` or `In Process`, or when the customer replies.

<!-- sources: app/Http/Controllers/TicketController.php, app/Services/SlaService.php -->
