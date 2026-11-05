---
key: web-report-management
chapter: administration
order: 62
title: Web Report Management
summary: Read and follow up the confidential reports sent through the public Web Report form.
requires: [control-center.web-reports]
updated: 2026-11-04
---
Web Report is a public form that anyone can use to report an issue, with no account. It is opened with the `Report` button on the sign-in page. `Web Report Management` is where the people who handle those reports read them, change their status and answer the reporter.

## Who uses this

The few people who handle reports, usually compliance or a senior administrator. Give this access sparingly. With view access you can read reports. With the `Edit` permission you can also change the status and write notes. A report is never deleted. It is closed.

> [!IMPORTANT]
> Reports are strictly confidential. Opening a report and downloading its evidence are recorded in its access history. Open a report only to handle it.

## Before you start

- Reports sent by signed-in employees from [[my-compliance]] are not listed here. They are handled in [[compliance-management]].
- A reporter may give no name and no contact. Do not try to find out who they are.
- Evidence comes from an unknown person. Scan a file before you open it.

## Step by step

### Read the inbox

1. Open `Control Center` › `Web Report Management`.
2. Check the tiles: one for each status (`New`, `In review`, `Investigating`, `Closed`) and one for reports past the follow-up date. Click a tile to filter by it.
3. Use the funnel icons in the table header to filter by `Code`, `Received` date, `Follow-up due`, `Status` or `Evidence`. Click `Reset Filter` to clear them. Change `Rows per page` at the bottom.
4. Open the list with open reports first, then closed ones. Click `Open` on a report.

Reporters stay anonymous, so reports are found by their code, which looks like `WR-XXXX-XXXX`.

### Follow up a report

1. Read the report on the left: the code, the reporter if one was given, the contact, the date received, who or what it is about, the chronology and the evidence.
2. To read the evidence, click the file name. The download is recorded.
3. On the right, choose a `Status`. The note under it shows when a follow-up is due.
4. Write a `Message the reporter will see`. It appears on the status page next to the tracking code. Never put names or evidence in it.
5. Write an `Internal note` for the team if needed. The reporter never sees it.
6. Click `Save`. The change is added to the `Access history`.

### Close a report

1. Open the report and set the `Status` to `Closed`.
2. Write a last message to the reporter, then click `Save`. The closing date is recorded.

## What happens next

The reporter checks the status on the public report status page with the tracking code. They see the status, the date a follow-up is expected and your message. A report that is not answered within the follow-up days counts in the `Past the 14-day follow-up` tile.

## Common questions

**Why are the fields greyed out?** You can read reports but you do not have the `Edit` permission. Ask an administrator.

**Why can I not find a report from an employee?** Employees report from `My Compliance`. Those reports are in `Compliance Management` › `Reports`, with codes that start with `CL-`.

**Can I add or change the statuses?** Not here. The public form always uses the same four statuses. The statuses for employee reports are set in `Compliance Management` › `Settings`.

## Related topics

- [[compliance-management]]
- [[my-compliance]]
- [[control-center]]

<!-- sources: routes/web-report.php, app/Http/Controllers/WebReport/*, app/Services/WebReport/*, resources/views/admin/web-report/*, resources/views/web-report/*, config/web-report.php -->
