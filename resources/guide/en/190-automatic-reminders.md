---
key: automatic-reminders
chapter: reference
order: 140
title: Automatic reminders
summary: The reminders EcoSystem sends by itself: open tickets, today's timesheet, contract end dates and invoice dates.
requires: []
updated: 2026-11-02
---
EcoSystem sends a few reminders on its own, so that nothing waits unnoticed. This topic lists who receives each one, when, and what makes it stop.

## Who uses this

Everyone who may receive one: module leads, consultants, and Heads and administrators of projects. You do not need to do anything to turn them on.

## Before you start

- Reminders are sent by scheduled tasks on the server. If a task is not running there, its reminder does not arrive. Administrators can check this in `Control Center` › `Schedule Monitor`.

## Rules to know

### Open tickets

- A ticket that is still `Open` reminds its module lead through a Microsoft Teams card. The same ticket is reminded again after the interval, which is 60 minutes by default.
- It stops when the ticket leaves `Open`. Hidden tickets are not reminded.
- It only works when the Teams connection (Power Automate) is configured. Otherwise nothing is sent.

### Today's timesheet

- Every day at 15:00 a bell notification goes to the consultants who have no timesheet entry dated today. A draft already counts as filled.
- It is not sent on weekends or public holidays, and at most once per person per day.

### Project dates

- Every day at 07:00 the Head of Project and the Project Administrator get a bell notification for:
  - a project whose contract end date is within the next 30 days, or already passed, and which is not closed. The message says an addendum may be needed;
  - a payment term whose estimated date has arrived while the `Submit Invoice Date` is still empty.
- The reminders refresh at once when a contract date or a payment term is changed, so they disappear when the condition is solved.

### On submit and on status change

- Submitting a timesheet notifies the right approvers, see the timesheet topic.
- A ticket status change notifies the customer, see the ticket status topic.

## What happens next

Reminders appear under the bell icon (or as a Teams card for open tickets). Act on them and they stop by themselves: change the ticket status, fill in the timesheet, set the invoice date or extend the contract.

## Common questions

**I get no timesheet reminder.** You may have already logged today (a draft counts), it may be a weekend or holiday, or your position is not a consultant.

**The project reminder will not go away.** Fill in the `Submit Invoice Date` of the payment term, or change the contract end date of the project, or close the project.

<!-- sources: routes/console.php, app/Console/Commands/SendOpenTicketReminders.php, app/Console/Commands/SendTimesheetReminders.php, app/Services/ProjectReminderService.php -->
