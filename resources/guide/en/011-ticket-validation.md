---
key: ticket-validation
chapter: work-service
order: 11
title: Ticket Validation
summary: Review new customer requests, check the AI analysis, classify them, and approve them into tickets or reject them with a reason.
requires: [tickets.staging]
updated: 2026-10-28
---
Ticket Validation is the front door for customer requests. A request that arrives by email or from the customer portal waits here until a person decides whether it becomes a real ticket.

## Who uses this

Administrators and the roles that review incoming requests, such as Helpdesk, Delivery Support Head, Delivery RPMO Head and Delivery Support Manager. You see the page only if your role has the `Ticket Validation` menu. The number next to the menu shows how many requests are waiting.

## Before you start

- A request may contain customer credential information. Use it only for the work and never copy it into chat, notes, email or other documents.
- Approving creates a ticket and notifies people. Rejecting tells the customer. Read the request before you decide.
- Approving needs the AI analysis to be finished. This is a hard rule, so plan a moment for it on each request.

## Step by step

### Find a request

![The Ticket Validation list with the status cards and one pending request](images/guide/ticket-validation-list.png)

*Screenshot with sample data.*

1. Open `Ticket Validation`. The cards show `Pending`, `Approved` and `Rejected`.
2. The list shows the sender, subject, `Priority`, `Channel`, `Status` and `Submit Date`. Use the status filter (`Pending Validation`, `All Status`, `Approved`, `Rejected`) and `Refresh` to update the list.
3. Select a request to open `Incoming Ticket Details`.

### Read and check the AI analysis

![The Incoming Ticket Details window with the classification fields and the Approve and Reject buttons](images/guide/ticket-validation.png)

*Screenshot with sample data.*

1. At the top you see `From`, `Date`, `Subject`, the `Description` and any `Attachments`.
2. The `AI Ticket Analyzer` runs when you open a request that has not been analysed yet, and prepares one analysis per request. Empty classification fields are filled in from it. It suggests a module and who could take it: the `Module Lead` or a certified consultant for that module, and a `Lead` based on the customer's earlier consultants. Steps for the first response are included. Treat it as a suggestion.
3. Use `Ask a question` under the analysis to ask follow-up questions, for example what the first step should be.
4. If the analysis failed, select `Re-analyze`. You can also fill in the classification yourself, but you cannot approve until an analysis has finished.
5. If the window says the analysis is hidden from you, it refers to the customer's stored credential notes. You need `Customer Credential` view access to see it. Ask your administrator.

### Classify and approve

1. Under `Ticket Classification` choose the `Type` and `Priority`. `Scale` is optional.
2. Choose the `Delivery Support` the ticket belongs to. It is required when the customer has delivery support items, and it must belong to that customer.
3. If the request came in through a parent customer's email domain, a `For customer` field lets you choose the real end customer.
4. Under `Additional Info` check `Name`, `No HP`, `Module(s)` and `Client`, and correct them when needed.
5. Select `Approve`. The request becomes a ticket, the customer and the team are notified, and a shared AI research chat for the ticket is prepared from the analysis.

### Reject a request

1. Select `Reject`.
2. Write the `Reason for Rejection` (up to 1000 characters). Be specific, because the customer is told.
3. Confirm. The request moves to the rejected list.

### Look at rejected requests

![The Rejected Tickets list with the rejection reasons](images/guide/ticket-validation-rejected.png)

*Screenshot with sample data.*

Select `View Rejected` (the badge shows how many). You can search by customer or description, read the `Rejection Reason`, and open `Detail`. Use `Back to Ticket Validation` to return.

## Rules to know

- `Type` and `Priority` are required to approve. The types are `Incident`, `Change Request`, `Service Request`, `EWA`, `RISE` and `Consult`. The priorities are `Very High`, `High`, `Medium` and `Low`. `Scale` is optional (`Simple`, `Medium` or `Complex`).
- Approving needs a finished AI analysis. If it failed, use `Re-analyze`. If an analysis is already running, wait for it.
- `Delivery Support` is required when the customer has any delivery support, and it must belong to that same customer.
- The new ticket starts as `Open` and gets the next ticket number: the year and month (for example `2611`) followed by a running four-digit number.
- The SLA clock of an `Incident`, `Change Request` or `Consult` request starts when the customer sent it, not when you approve it. Time spent waiting in `Ticket Validation` therefore counts against the response target, so work through pending requests quickly.
- Rejecting needs a written reason of at most 1000 characters. The customer sees a portal notification that starts with the reason, and the rejection is synced to the customer portal.
- Approving also notifies the customer, the team and the Teams chat of the ticket.
- Once a request is approved or rejected it cannot be processed again, and its AI analysis and `Ask a question` are closed. A question can be at most 2000 characters.
- If the AI analysis mentions the customer's stored credential notes, only roles with `Customer Credential` view access can read it. Others classify the request by hand.

## What happens next

An approved request appears in `Ticket` with its lead and team, and the customer is told. A rejected request stays in the rejected list with your reason, and the customer receives a notice that includes it.

## Common questions

**`Approve` says the AI analysis must finish first.** Wait for the analysis to complete, or select `Re-analyze` if it failed. There is no way around this step.

**I cannot choose a Delivery Support.** The customer has none registered, so the field is optional, or you picked one that belongs to another customer.

**The list is empty but the number on the menu is not.** Select `Refresh` and check the status filter.

**I rejected a request by mistake.** A request that already has a decision cannot be processed again. Ask the customer to submit it again, or create the ticket from the `Ticket` page if your role allows it.

<!-- sources: routes/web.php, app/Http/Controllers/StagingTicketController.php, resources/views/staging/index.blade.php, resources/views/staging/rejected.blade.php -->
