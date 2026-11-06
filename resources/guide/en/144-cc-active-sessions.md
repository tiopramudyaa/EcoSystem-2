---
key: cc-active-sessions
chapter: control-center
order: 23
title: Active Sessions
summary: See who is signed in right now and end a session (force logout) when needed.
requires: [control-center.sessions]
updated: 2026-11-02
---
`Active Sessions` lists the people currently signed in to EcoSystem, one row per browser session. An administrator can end a session immediately, for example when a device is lost or an account is misused.

## Who uses this

Administrators, and any role given the `Control Center` › `Active Sessions` menu in Menu Access.

## Before you start

- Ending a session signs the person out at once. Unsaved work in their open page is lost, so warn them if you can.
- The person can sign in again with their password. If the account itself must stop, ask an administrator to block it in the employee record.

## Step by step

![The Active Sessions page with a row per session and a Force Logout link](images/guide/cc-active-sessions.png)

*Screenshot with sample data.*

1. Open `Control Center` › `Active Sessions`.
2. Read the two cards: `Active Sessions` and `Unique Users`. One person with several browsers has several sessions.
3. Read each row: the person (name, employee code, email), `IP Address`, `Location`, `Browser / Device`, `Last Activity` and `Status`.
4. Find a session with the search box (name, email, IP or location) or by clicking `User` or `Last Activity` to sort.
5. To end one session, choose `Force Logout` on its row and confirm.
6. To end every other session, choose `Force Logout All Others` and confirm. Your own session stays.

## What happens next

The person is returned to the sign-in page on their next click.

## Common questions

**Why is there no `Force Logout` on my own row?** Your row shows `You`. You cannot end your own session here, use the normal logout.

**Why does a row show `Protected`?** A protected system account cannot be signed out from this page, and `Force Logout All Others` skips it.

<!-- sources: routes/web.php, app/Http/Controllers/AdminSessionController.php, resources/views/admin/sessions.blade.php -->
