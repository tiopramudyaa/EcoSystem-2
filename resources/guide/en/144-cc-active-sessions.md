---
key: cc-active-sessions
chapter: control-center
order: 23
title: Active Sessions
summary: See who is signed in right now, end a session (force logout), and lock the system during deployments.
requires: [control-center.sessions]
updated: 2026-11-10
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
5. To end one session, choose `Force Logout` on its row. A `Session Detail` window opens with the person, IP, location and browser. Choose `Force Logout` in it, then confirm. `Cancel` closes the window without ending anything.
6. To end every other session, choose `Force Logout All Others` and confirm. Your own session stays.

### Lock the system (for deployments)

Use this when nobody should sign in or change data, for example during a deployment.

1. Sign in as the protected administrator account and open `Control Center` › `Active Sessions`.
2. In the `System Lock` card at the top, optionally type a message for the login page, then choose `Lock System` and confirm.
3. Everyone except the protected administrator is signed out at once, and cannot sign in until you unlock.
4. When you are done, choose `Unlock System` and confirm. The card turns red while the system is locked.

## Rules to know

- While the system is locked, only the protected administrator account can sign in or keep using the system. Everyone else is turned away, on the web app and on Lite.
- Only the protected administrator can lock or unlock the system. Other people who can open this page see the lock state but not the buttons. This stops an account from locking itself out.
- Locking signs out every other signed-in person immediately. A person who is somehow still connected is signed out on their next click. Sign-in with a saved "remember me" login or a Lite token is refused too.
- A locked-out person who tries to sign in sees the message you typed, or a default maintenance message. A wrong password still shows the normal error.
- The lock stays on until someone unlocks it. It does not expire by itself and survives a server restart.
- Scheduled jobs, the queue and integrations that use an API key keep running while the system is locked. The lock only stops people.
- Every lock and unlock is recorded in the `Audit Log` under the module `Session`.

- Only sessions with activity in the last 120 minutes are listed. Older sessions have expired and are not shown.
- Only sessions of people who have signed in are listed. A visitor sitting on the sign-in page, or a failed sign-in, does not appear.
- You cannot end your own session here, and a protected system account cannot be ended at all. `Force Logout All Others` skips both.
- `Force Logout All Others` only ends sessions that are still active, so the number in the confirmation message matches the rows you saw.
- Every `Force Logout` and `Force Logout All Others` is recorded in the `Audit Log` under the module `Session`, with who did it and when.
- The page only covers browser sessions. It does not list or end sessions of other apps.
- If the list cannot load, the page says `Failed to load sessions`. Reload the page to try again.

## What happens next

The person is returned to the sign-in page on their next click.

## Common questions

**Why is there no `Force Logout` on my own row?** Your row shows `You`. You cannot end your own session here, use the normal logout.

**Why does a row show `Protected`?** A protected system account cannot be signed out from this page, and `Force Logout All Others` skips it.

<!-- sources: routes/web.php, app/Http/Controllers/AdminSessionController.php, resources/views/admin/sessions.blade.php -->
