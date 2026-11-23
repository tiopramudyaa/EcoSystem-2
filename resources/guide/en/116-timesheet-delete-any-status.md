---
key: timesheet-delete-any-status
chapter: my-workspace
order: 116
title: Delete Timesheet in Any Status
summary: Delete a submitted, approved or rejected timesheet of any employee from the Timesheets page.
requires: [timesheet.delete.any-status]
updated: 2026-11-21
---
Normally only a `Draft` timesheet can be deleted. With this function you can also delete an entry that is already `Submitted`, `Approved` or `Rejected`, for example an approved entry that was logged on the wrong project and now shows up in `MD Recap`.

## Who uses this

People who hold `Delete Timesheet in Any Status` in `Menu Access`, under `Timesheets`. When the function is created only the EC Administrator holds it.

## Before you start

- Make sure the entry really has to go. Deleting changes the mandays in the reports.
- Know whose entry it is and its date, so you pick the right row.

## Step by step

1. Open `Calendar` › `Timesheets`. You see the entries of every employee whose types you may see.
2. Find the entry. Use the filters or the type tabs to narrow the list.
3. Right-click the row and choose `Delete`. You can also choose `Detail` and select `Delete` at the top of the panel.
4. The `Delete Timesheet` dialog shows the status, the employee and the date. Check them.
5. Select `Delete`. The entry disappears from the list and a success message appears.

`Delete` is not shown on a `Draft` row: a draft is deleted the usual way, with its checkbox and the `Delete` button above the list.

## Rules to know

- Deleting a `Submitted`, `Approved` or `Rejected` entry needs this function. Without it the request is refused, even if it is sent directly.
- The function is given to the EC Administrator only when it is created. Another role gets it only when an administrator ticks it in `Menu Access`.
- The holder can delete any employee's entry, in any status and in any period, also a period that is already closed.
- A deleted entry no longer counts anywhere: `Timesheets`, `MD Recap`, its exports and the other reports leave it out, and its mandays no longer count against the employee's quota on the ticket.
- If the figures of that period were already sent to a customer, they will no longer match what EcoSystem shows.
- The deletion is recorded in the `Audit Log` with your name. The entry is kept in the database and a system administrator can restore it.

## What happens next

The employee no longer sees the entry. If the work still has to be logged, the employee creates a new entry and sends it for approval again. That new entry follows the normal period and quota rules.

## Common questions

**Can I undo a deletion?** Not from the page. Ask a system administrator to restore it from the database.

**Why do I not see `Delete` on an approved entry?** Your role does not hold `Delete Timesheet in Any Status`, or the menu has not refreshed yet. Reload the page after the function is ticked.

<!-- sources: app/Http/Controllers/TimesheetController.php (destroy), resources/views/calendar/partials/timesheet-detail-panel.blade.php, resources/views/calendar/timesheets.blade.php, database/migrations/2026_11_21_000001_add_timesheet_delete_any_status_permission.php -->
