---
key: cc-overview
chapter: control-center
order: 10
title: Control Center Overview
summary: The landing page of Control Center: live counts for logins, security, sessions, failed jobs and scheduled tasks, with a shortcut to each page.
requires: [control-center.overview]
updated: 2026-11-02
---
The `Control Center` page is the starting point for system administration. Each card gives a quick reading of one tool and a button that opens the full page.

## Who uses this

Administrators, and any role given the `Control Center` › `Overview` menu in Menu Access. A card opens only the page your role is allowed to use.

## Before you start

- This page is for reading and navigating. Nothing you change here affects data.
- The numbers are counted when the page opens. Reload the page to see newer figures.

## Step by step

![The Control Center page with one card per tool and a button to open each one](images/guide/cc-overview.png)

*Screenshot with sample data.*

1. Open `Control Center`.
2. Read the cards from left to right:
   - `Activity Log`: total records, successful sign-ins and failed attempts.
   - `Security Center`: open security events, critical or high events and locked accounts.
   - `Active Sessions`: active sessions, signed-in people and guests.
   - `Failed Jobs`: how many background jobs failed and when the latest one failed.
   - `Schedule Monitor`: how many scheduled tasks need attention (failing, late or never run).
   - `Notification Sounds`, `AI Settings` and `Backup & Export`: a short summary and the entry point.
3. A red or amber figure means something needs a look. Use the button on that card (for example `Open Failed Jobs`) to see the details and act.

## What happens next

You land on the full page of that tool. Use the browser back button or the `Control Center` menu to return here.

## Common questions

**A card I expect is missing or opens a "no access" message.** Your role does not have that page in Menu Access. Ask an administrator to grant it.

**The Schedule Monitor card says tasks need attention on a test server.** Scheduled tasks only run where the scheduler is running. See the Schedule Monitor section of the security topic.

<!-- sources: routes/web.php, resources/views/admin/index.blade.php -->
