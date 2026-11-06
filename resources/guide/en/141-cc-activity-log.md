---
key: cc-activity-log
chapter: control-center
order: 20
title: Activity Log
summary: Review every sign-in, failed attempt and sign-out, with who, from where and on what device.
requires: [control-center.activity-log]
updated: 2026-11-02
---
`Activity Log` keeps the history of access to EcoSystem. Each row is one event: a successful sign-in, a failed attempt or a sign-out. Use it to answer "who signed in, when and from where".

## Who uses this

Administrators, and any role given the `Control Center` › `Activity Log` menu in Menu Access.

> [!IMPORTANT]
> This page shows where and when people work. Use it only for administration and security, and do not share what you see.

## Before you start

- Know what you are looking for: a person, an IP address, a time window, or only the failed attempts.
- Times are shown in WIB. The list is read-only: records cannot be edited or deleted here.

## Step by step

![The Activity Log page with summary cards and a table of sign-in records](images/guide/cc-activity-log.png)

*Screenshot with sample data.*

1. Open `Control Center` › `Activity Log`.
2. Read the four cards on top: `Total Records`, `Successful Logins`, `Failed Attempts` and `Logouts`. They count all records, not only what the filters show.
3. Narrow the table with the filters in the column headers:
   - `User`: click the funnel icon and type a name, IP address, browser or location.
   - `Status`: pick `Success`, `Failed` or `Logout`.
   - `Time (WIB)`: click the funnel icon, choose a `From` and `To` date, then `Apply`.
4. Click a column title (`User`, `Status`, `IP Address`, `Location`, `Time (WIB)`) to sort. Change `25 / page` to show more rows.
5. When a filter is active the `Clear filters` button appears. Use it to start over.

## What happens next

Nothing is changed. If a row looks suspicious (a failed attempt from an unknown place, or a sign-in at an odd hour), continue in `Security Center` or ask the person concerned.

## Common questions

**A person says they signed in but I cannot find them.** Search by their name or clear the date filter. Sign-ins by customers appear with their own user type.

**What is the difference with `Login Log`?** `Activity Log` lists every event. `Login Log` shows only the latest status of each employee.

<!-- sources: routes/web.php, app/Http/Controllers/ActivityLogController.php, resources/views/admin/activity-log.blade.php -->
