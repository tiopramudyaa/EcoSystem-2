---
key: cc-failed-jobs
chapter: control-center
order: 24
title: Failed Jobs
summary: Find background jobs that failed (emails, chat sync, notifications), read the error and retry or remove them.
requires: [control-center.failed-jobs]
updated: 2026-11-02
---
EcoSystem sends emails, syncs chats and does other slow work in the background. When one of these jobs fails, it waits in `Failed Jobs` so nothing is lost silently. An administrator can read the error and decide to retry or remove it.

## Who uses this

Administrators, and any role given the `Control Center` › `Failed Jobs` menu in Menu Access.

## Before you start

- Read the error first. A job that failed because a mail server was down can be retried safely once it is back. A job that fails for the same reason again needs a fix, not another retry.
- `Clear All` removes every failed job for good, and `Delete` removes one. Neither can be undone.

## Step by step

![The Failed Jobs page with a table of failed jobs, each with Retry and Delete](images/guide/cc-failed-jobs.png)

*Screenshot with sample data.*

1. Open `Control Center` › `Failed Jobs`.
2. Read the cards: `Total Failed`, `Oldest Failure` and `Newest Failure`.
3. Read each row: the `Job` name with its id, the `Queue`, the `Error` (shortened) and `Failed At`.
4. Find jobs with the search box (job name, queue or error text) or the queue list. Click `Queue` or `Failed At` to sort.
5. To try one job again, choose `Retry` on its row. To try all of them, choose `Retry All`.
6. To discard a job you do not need, choose `Delete` and confirm. To discard all, choose `Clear All` and confirm.

## What happens next

A retried job returns to the queue and runs again. If it fails again it comes back to this page with a new time. A growing list of the same error usually means an outside service (mail or Microsoft Graph) needs attention.

## Common questions

**The card on `Control Center` shows failed jobs but I retried them.** The card counts what is still here. Reload both pages.

**Should I clear everything now and then?** Only after you understand the errors. Cleared jobs cannot be recovered.

<!-- sources: routes/web.php, app/Http/Controllers/AdminJobController.php, resources/views/admin/failed-jobs.blade.php -->
