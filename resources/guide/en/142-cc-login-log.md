---
key: cc-login-log
chapter: control-center
order: 21
title: Login Log
summary: See the latest sign-in or sign-out of each employee, with device, IP address and location.
requires: [control-center.login-log]
updated: 2026-11-02
---
`Login Log` answers "who is signed in or was last seen, and from where". It shows one row per employee: their most recent sign-in or sign-out.

## Who uses this

Administrators, and any role given the `Control Center` › `Login Log` menu in Menu Access.

> [!IMPORTANT]
> This page shows where and when people work. Use it only for administration and security, and do not share what you see.

## Before you start

- Failed attempts are not listed here. To investigate failed attempts, use `Activity Log`.
- Times are shown in WIB. The page is read-only.

## Step by step

![The Login Log page with one row per employee and their latest login status](images/guide/cc-login-log.png)

*Screenshot with sample data.*

1. Open `Control Center` › `Login Log`.
2. Read each row: `Employee`, `Status` (`Login` or `Logout`), `Device`, `IP Address`, `Location`, `Time (WIB)` and `Last Login` in plain words, such as "Last login 5 hours ago".
3. To find one person, click the funnel icon on `Employee` and type the name. To see only people who are signed in, or only those signed out, use the `Status` filter.
4. Click `Employee`, `Status`, `Time (WIB)` or `Last Login` to sort. Change `25 / page` to show more rows. `Clear filters` resets everything.

## What happens next

Nothing is changed. To end someone's session, go to `Active Sessions`.

## Common questions

**An employee shows `Login` but is not at work.** `Login` means the last recorded event was a sign-in. People who close the browser without signing out stay at `Login` until the session ends.

**Where are customers?** This page lists employees. Customer sign-ins are in `Activity Log`.

<!-- sources: routes/web.php, app/Http/Controllers/LoginLogController.php, resources/views/admin/login-log.blade.php -->
