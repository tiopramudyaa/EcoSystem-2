---
key: ticket-assignment
chapter: work-service
order: 12
title: Assigning tickets and managing the team
summary: Take a ticket, assign a ticket lead, add team members and link the ticket to one Delivery Support.
requires: [ticket.assign-pic, ticket.assign-delivery-support, ui.ticket.manage-members, ticket.take]
updated: 2026-11-02
---
Every ticket needs an owner. This topic shows how a ticket gets its ticket lead and team, and how it is tied to a Delivery Support.

## Who uses this

Helpdesk and heads who assign tickets, and consultants who take tickets themselves. Each button appears only if your role allows it.

## Before you start

- Open `Work & Service` › `Ticket`.
- Only people who are allowed to act as ticket lead or ticket member are offered in the lists. This is set by your administrator, and blocked accounts are never offered.

## Step by step

### Find tickets without an owner

1. Open the `Unassigned` tab (it appears as `Unassigned Ticket` for some roles).
2. Open a ticket that suits you or the team.

### Take a ticket yourself

1. Open the ticket.
2. Select `Take This Ticket`.

If the ticket is unclear or outside your skills, leave an `Internal Note` for Helpdesk instead of leaving it untouched.

### Assign a ticket lead

![A ticket page with the buttons Change Ticket Lead and Assign to Delivery Support in the right-hand panel](images/guide/ticket-detail.png)

*Screenshot with sample data.*

1. Open the ticket and select `Assign Ticket Lead`. When the ticket already has a lead, the button reads `Change Ticket Lead`.
2. Choose the person and confirm.

### Manage the team

1. Open `Team Members` on the ticket.
2. Add the colleagues who should work on it, or remove those who no longer should.
3. Save the change.

### Link the ticket to a Delivery Support

1. Select `Assign to Delivery Support`.
2. Choose the right Delivery Support of the customer.

A ticket belongs to **one** Delivery Support. If it was linked wrongly, remove the link first, then link the correct one.

> [!WARNING]
> `Hide Ticket` and `Delete Ticket` are only for some roles and affect everyone who sees the ticket. Use them only when you are sure you are allowed to.

## Rules to know

- A person who is inactive, blocked or flagged for deletion cannot be a ticket lead or a member. EcoSystem refuses even if the request is sent directly.
- Once a ticket has a module (it can have more than one), the ticket lead and the members must be chosen from the people qualified in that module. Otherwise EcoSystem says the person must be a member of the ticket's module.
- Adding, changing and removing members needs the `Manage Members` function in `Menu Access`. A Delivery Support User with it can only do this on a ticket where they are the ticket lead. The ticket lead and the module lead of the ticket can always manage its members. Anyone else is refused, even if the request is sent directly. The ticket lead cannot also be added as a member, and a person cannot be added twice.
- `Take This Ticket` makes you the ticket lead at once. It only ever assigns you, never someone else. It needs the `Take Ticket` function in `Menu Access`, and you must also hold the DSM qualification.
- External employees cannot change tickets.
- `Assign to Delivery Support` (including creating a new delivery support from the ticket) works only for roles that were given that function in Menu Access. By default these are the administrator, Delivery Support Head, Helpdesk and Delivery RPMO Head. Anyone else is refused, even if the request is sent directly. Giving the function to another role in `Control Center` › `Menu Access` is enough, and nothing else needs to change.
- `Delete Ticket` works only for roles that were given the `Delete Ticket` function in Menu Access. By default these are the administrator, Delivery Support Manager II and Delivery Support Section Head. Anyone else is refused, even if the request is sent directly.
- Deleting a ticket takes it out of every list and report, and its page no longer opens. Nothing is erased from the database: the messages, SLA record, Resolution Days and timesheets stay stored. EcoSystem has no screen to bring a deleted ticket back, so a deletion made by mistake needs the technical team. The deletion is recorded in the `Audit Log` as `Deleted Ticket`.
- To get a ticket out of the way and keep the option to show it again, use `Hide Ticket` instead. See `Hidden Tickets`.

## What happens next

The ticket appears in the `My Tickets` tab of its ticket lead and team members, and in `My Tasks`. Mandays proposals use the skills of the ticket lead and members, so keep the team accurate.

## Common questions

**A person is missing from the list.** They may not be allowed to act as ticket lead or member, or their account is blocked. Ask your administrator.

**I cannot change the Delivery Support.** Your role may not include `Assign to Delivery Support`. Ask Helpdesk or your head, or ask an administrator to give your role that function in Menu Access.

<!-- sources: routes/web.php, app/Http/Controllers/TicketController.php, resources/views/ticket/index.blade.php, resources/views/ticket/show.blade.php -->
