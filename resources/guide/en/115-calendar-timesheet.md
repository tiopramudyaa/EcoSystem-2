---
key: calendar-timesheet
chapter: my-workspace
order: 115
title: Timesheets
summary: Log your working hours on a project, a ticket or office work, send them for approval and ask for late access.
requires: [calendar.timesheets]
updated: 2026-11-11
---
`Calendar` › `Timesheets` is where you log working hours and send them for approval.

## Who uses this

Employees who have `Timesheets` under `Calendar`. Heads and approvers use the same page to review their team.

## Before you start

- Know the project or ticket, the date, the start and end time and what you did.
- The `Create Timesheet` button appears only if your role allows it. If it is missing, ask your administrator.

## Step by step

### Log your working hours

![The Timesheets page with the period, summary cards, the Create Timesheet button and the list of entries with their status](images/guide/timesheets.png)

*Screenshot with sample data.*

1. Open `Calendar` › `Timesheets`. The chip next to the buttons shows the reporting period, for example `October 2026`, and whether it is `Open`.
2. Select `Create Timesheet`. The form is titled `Log Working Hours`.

![The Log Working Hours form with type, dates, time, ticket, MD consumed, activity and notes](images/guide/timesheets-log-modal.png)

*Screenshot with sample data.*

3. Choose the `Type`: `Project`, `Non Project`, `Support` or `Office`. Then check the `Submit Date`. The `Reporting Period` is filled in for you.
4. Choose the start and end `Time`. The form shows the `Duration`. The end time must be later than the start time.
5. Choose the `Ticket`. The `Customer`, `Quota MD` and `Remaining` boxes fill in from it.
6. Set the `Activity Date`, which is when the work actually happened.
7. Enter `MD Consumed` and tick `On Site` if you worked at the customer's site.
8. Describe the work in `Activity`. `Notes` is optional.
9. Select `Save Timesheet`. The entry starts as `Draft`, and you can still edit or delete it.

Steps 5 to 7 are for `Support`. For `Project`, choose the `Project` and `Activity`, then `Presence` and `Location`, and describe the work in `Activity Detail`. `Non Project` is the same without `Project` and `Activity`. With `On-site` presence, `Location` offers the project's location.

### Send for approval

1. Select the draft entries in the list.
2. Select `Submit Timesheets` and confirm.
3. The status becomes `Submitted`, and you can no longer edit it.

The cards above the list count your entries per status and show `Total Quota MD` and `Total MD Consumed`. Right-click a row and choose `Detail` for the full entry. The `Project` tab follows the entry form's column order.

### Ask for late access

1. If a period is closed, select `Request Late Access`.
2. Choose the period and write the reason.
3. Send the request. It is reviewed by your Head first and then by RPMO.

> [!NOTE]
> If you are allowed to approve timesheets, you also see `Approve` and `Reject`, including for several entries at once. Always write a clear reason when you reject.

## Rules to know

These rules are checked when you save or submit. If one is broken, nothing is saved.

### For every entry

- Creating a timesheet needs the `Create Timesheet` function in `Menu Access`. Without it the request is refused, even if it is sent directly.
- The date cannot be in the future.
- You can only create entries for yourself (Heads, RPMO Head and EC Administrator excepted).
- Moving an existing entry to another date checks the period limits again for the new date.
- The end time must be later than the start time, and the activity description is required.
- If your role belongs to the Project or Support domain, you can only log time in the current period or the previous period, and only while that period is open. A period is open when RPMO has opened it and the Head of your domain has opened your domain. If it is closed, ask for late access. Roles that belong to no domain are not held to these period limits.
- You can edit your own entries while they are `Draft` or `Rejected`. Only the EC Administrator, the RPMO Head and the Heads can also change other people's entries. A `Submitted` or `Approved` entry can be neither edited nor deleted. Only a `Draft` can be deleted.

### Support entries (with a ticket)

- Choose the ticket you worked on and fill the `Activity Date`.
- The `Ticket` list shows tickets where you are the lead, a member or a Co-PM (plus the tickets of Delivery Support you manage), but only if Head has approved days for you and quota remains. A draft you edit keeps its own ticket selectable.
- `MD Consumed` cannot be more than your remaining quota on that ticket. The quota is the days Head approved for you, including additional days. It stays valid while the proposal is revised. Your drafts, submitted and approved entries all count against it. If you need more, ask your Head to raise the quota.
- You can save a draft before days are approved, but cannot submit it: EcoSystem says Head has approved no days for you on the ticket. For a Change Request the Customer Mandays must be approved first.
- If your personnel subarea is `Project`, one support entry can use at most 1 MD. Split bigger work into several entries.

### Project entries

- You can only log time on a project you are a team member of, and the project must not be closed.
- The date is always today and the location is captured when you create the entry. You cannot backdate it, and editing later keeps the date and location. The list shows the GPS distance from the project's mapped location.
- The time cannot overlap another project entry of yours on the same day. A rejected entry frees its slot again.
- Choose an activity assigned to you and running that day. On a non-working day, or with no assigned activity, tick the option to log without an activity.
- `Presence` and `Location` are required. When `Presence` is `On-site`, `Location` is filled with the project's location from its `Location Information`. You can type another place.

### Non Project entries

- The Project rules apply: the date is always today, the device location is required, and the time cannot overlap another Project or Non Project entry of yours that day.
- There is no project and no activity. `Presence`, `Location` and `Activity Detail` are required.
- The Head of Project approves them by default.

### Office entries

- Choose an `Activity Type`: `Development`, `Meeting`, `Documentation`, `Testing`, `Support`, `Training` or `Other`.

### Submitting and approving

- You can submit only your own `Draft` entries. Submitting notifies everyone whose role may approve that type (see the next point).
- Approving and rejecting is set per type in `Menu Access`, under `Timesheets`: `Approve or Reject Project Timesheet`, and the same for Non Project, Support and Office. One function covers both, and you only see and act on the types you hold. By default the Head of Project holds Project and Non Project, the Head of Support holds Support, the RPMO Head holds Office and the EC Administrator all four. Only `Submitted` entries can be approved or rejected, one by one or in bulk. Approving a support entry is refused if `MD Consumed` no longer fits the quota.
- A rejection needs a written reason. The employee sees it, can correct the entry (it goes back to `Draft`) and submit it again.
- An approved entry is final: it cannot be reopened, edited or deleted.

## What happens next

A rejected entry shows the reason: correct it (back to `Draft`) and submit again. A late access request shows `Pending Head`, then `Pending RPMO`. Once both approve, you can fill in that period until its deadline.

## Common questions

**I cannot create a timesheet for a past period.** The period is closed. Use `Request Late Access`, even for last month.

**Can I change a submitted timesheet?** No, and you cannot delete it. Ask the approver to reject it, then correct it and submit again.

**Why are `Quota MD` and `Remaining` empty?** They fill in once you choose a ticket.

<!-- sources: routes/web.php, app/Http/Controllers/CalendarController.php, app/Http/Controllers/TimesheetController.php, resources/views/calendar/timesheets.blade.php -->
