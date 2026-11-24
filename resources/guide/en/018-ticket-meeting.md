---
key: ticket-meeting
chapter: work-service
order: 18
title: Ticket meeting
summary: Schedule a meeting with the customer from a ticket, send the invitation, and understand how the SLA clock is held during it.
requires: [ticket.meeting]
updated: 2026-11-02
---
The `Meeting` button on a ticket schedules a call with the customer and records it on the ticket. While the meeting runs, the SLA clock is on hold.

## Who uses this

Helpdesk and anyone given the `Meeting` permission in Menu Access.

## Before you start

- Decide the start and end time and have the meeting link ready (Teams, Meet or Zoom). The link is optional.
- The customer needs an email address on the ticket, otherwise the invitation is kept only as a note on the ticket.

## Step by step

1. Open the ticket and choose `Meeting`. When a meeting is already running the button reads `Meeting Active`, and choosing it lets you schedule a new one.
2. Set `Start` and `End` (date and time) and, if you have it, the `Meeting Link`.
3. Write the invitation in the notes box, for example the agenda.
4. Tick `Save as template` and name it if you will repeat this invitation. A template can only be used on the same ticket.
5. Choose the confirm button. The time and link are emailed to the customer, the schedule is posted in the ticket's Teams chat when there is one, and the ticket moves to the top of the list.

## Rules to know

- The end time must be after the start time.
- The meeting puts the SLA clock on hold from the start of the meeting. The ticket's ball goes to the customer and the pause is recorded as a meeting pause.
- A chat message from the customer does not end the hold. It lasts until the end time of the meeting, then ends by itself: there is no End Meeting button.
- Scheduling another meeting while one is active moves the end time of the hold to the new meeting.
- The hold appears in the SLA Log of the ticket as waiting time.

## What happens next

The customer gets the invitation, the conversation records it as a meeting message, and the SLA clock resumes at the end time.

## Common questions

**The customer did not get the email.** Check the customer email on the ticket. If there is none, the invitation was saved only as a message on the ticket.

**Can I end the meeting early?** There is no manual end. Schedule the meeting with the right end time.

<!-- sources: app/Http/Controllers/SlaController.php, app/Services/SlaService.php, resources/views/ticket/show.blade.php -->
