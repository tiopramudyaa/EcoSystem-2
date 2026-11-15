---
key: sla-report
chapter: sla
order: 11
title: SLA Report
summary: Track the response and resolution of every ticket against its SLA, read the pause log of a ticket and export the report.
requires: [sla.report, ticket.sla-log]
updated: 2026-11-02
---
`SLA` › `SLA Report` (the SLA Tracker) shows for each ticket whether it was answered and solved within the agreed time. It refreshes by itself every 60 seconds.

## Who uses this

Helpdesk, support managers and heads, and any role given the `SLA` › `SLA Report` menu in Menu Access. The per-ticket `SLA Log` is also available from a ticket's chat room to roles that were given the SLA Log button in Menu Access.

## Before you start

- Only tickets of the types `Incident`, `Change Request` and `Consult` have a full SLA. Other types are not listed.
- The targets come from `SLA` › `SLA Config`. A ticket without a policy shows no target.

## Step by step

![The SLA Tracker with filters and the first columns of the report](images/guide/sla-report.png)

*Screenshot with sample data.*

1. Open `SLA` › `SLA Report`. Narrow the list with `All Customers`, `All Dates` (choose `From` and `To`), `All Statuses`, `All Type of Service` and `All Types`, or search a ticket with `Cari tiket...`. `Refresh` reloads, and the page shows up to 200 records.
2. The first block `INFORMASI TIKET` shows the year, customer, ticket number, module, priority, scale, issue, customer and functional PIC, type of service (MO, CR or SR), type, status and the date the ticket was received.

![The SLA Response and SLA Resolution blocks of the report](images/guide/sla-report-columns.png)

*Screenshot with sample data.*

3. `SLA RESPONSE` shows the target, when the SLA clock started, when a response was due, when the ticket was responded, how long it took and whether the target was `Achieved`.
4. `SLA RESOLUTION` shows the target in hours, the start and due time, the first resolved date, the date the resolution document or mail was sent, the resolution duration, the time target and its status, the closed date and notes. Duration is counted stop-and-go: time spent waiting for the customer or a third party is not counted.
5. Choose `Export SLA` to download the report as a spreadsheet.

![The SLA Log window of a ticket with response, resolution, waiting time and ball holder](images/guide/sla-report-log.png)

*Screenshot with sample data.*

6. In the last column choose `Log / PDF` to open the `SLA Log` of the ticket. It shows the response and resolution against their targets, the total pause time (`Waiting`) and the `Ball Holder`: who the ticket is waiting for. The list below it records each event with its time. Choose `Download PDF` to keep a copy.

## Rules to know

- The SLA clock only runs while the ticket is with the helpdesk. `Waiting on Customer`, `Waiting to Confirmation`, `Hold` and `Waiting on 3rd Party` pause it, and the Ball Holder shows who the ticket waits for.
- When the customer replies, the ball returns to the helpdesk and the clock restarts, unless a meeting hold is active (see the ticket meeting topic).
- Hours are counted as business hours (Monday to Friday, excluding Indonesian public holidays, and excluding the policy's break) unless the policy is `24/7 Calendar Hours`.
- `Closed` and `Cancelled` finish the SLA. A cancelled ticket always counts as met.
- The response target is checked on the first reply. The resolution time is the net time: total elapsed time minus all waiting time.

## What happens next

The report only reads data. Fix a wrong target in `SLA Config`, and change the ticket status in the ticket itself.

## Common questions

**A ticket is missing from the report.** Its type is not `Incident`, `Change Request` or `Consult`, or a filter hides it.

**Why does the resolution time look shorter than the real time?** The clock stops while the ball is with the customer or a third party. Open `Log / PDF` to see each pause.

<!-- sources: routes/web.php, resources/views/admin/sla/report.blade.php, app/Http/Controllers/SlaController.php, app/Services/SlaService.php -->
