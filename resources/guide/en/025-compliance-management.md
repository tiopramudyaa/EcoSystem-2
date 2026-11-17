---
key: compliance-management
chapter: business-legal
order: 25
title: Compliance Management
summary: Follow up the concerns employees report, and manage what My Compliance shows and the choices it offers.
requires: [legal.compliance]
updated: 2026-11-04
---
`Compliance Management` has two jobs. It is where you read and follow up the concerns employees send from [[my-compliance]], and where you manage what My Compliance shows: articles, rules, critical rules and training sessions. Its `Settings` tab sets the statuses a report moves through and the choices in the dropdowns.

## Who uses this

The Compliance team and administrators. The page has four tabs: `Dashboard`, `Reports`, `Content` and `Settings`. It opens on `Reports`. What you can do depends on your access: with view access you read and export, with `Create` you add items, with `Edit` you change items, follow up reports and switch items on or off, and with `Delete` you remove items.

> [!IMPORTANT]
> Reports are confidential. Opening a report, downloading its evidence and exporting it are recorded in the report's access history. Use them only to handle the report.

## Before you start

- Reports sent from the public Web Report form are handled elsewhere, in [[web-report-management]]. This page lists only reports sent from `My Compliance`.
- Decide the steps a report will go through (for example `New`, `In review`, `Investigating`, `Closed`) and set them first in `Settings`.

## Step by step

### Follow up a report

1. Open `Business & Legal` › `Compliance Management`. The `Reports` tab shows a tile for each status and the number of reports past their follow-up date.
2. Use the funnel icons in the table header to filter by `Received` date, `Reported by` (`Employee` or `Anonymous`), a word in `About`, `Status` or `Follow-up due`. Click `Reset Filter` to clear them.
3. Click `Follow up` on a report. The left side shows who reported, the contact, the subject, the story and the evidence. The right side holds `Handling`.
4. Choose a `Status`. Write a `Message the reporter will see` and, if needed, an `Internal note`. Do not put names or evidence in the message.
5. Click `Save`. The change is added to the `Access history`.

A report that reaches a status that closes reports stops counting toward the follow-up date.

### Export a recap

1. On the `Reports` tab click `Export recap`.
2. Choose the `Period`: all time, today, yesterday, this or last week, this or last month, this year, a chosen day, a chosen month, a chosen year or a date range.
3. Choose a `Status` and the `File`: Excel (a recap sheet and the list) or CSV (the list only).
4. Click `Download`. The story, contact details and internal notes are never exported.

### Manage the content

1. Open the `Content` tab and choose a list on the left: `Articles`, `Do & Don't`, `Critical rules` or `Training sessions`.
2. Click `Add` to create an item, or `Edit` on a row. Write an article in the editor with its toolbar: headings, bold, italic, underline, strikethrough, lists, quote and links. Dates use a calendar and times a time field.
3. Use the switch in the `Visible` column to show or hide an item in My Compliance at once, without opening it. `Featured` works the same way for articles.
4. For `Do & Don't` and `Critical rules`, change the order with the up and down buttons, or drag a row by its handle. The order is saved at once and is the order people see.

### Set the choices (Settings)

1. Open the `Settings` tab. The left list has `Handling statuses`, and under `Content dropdowns`: `Categories`, `Rule severity`, `Critical rule colours`, `Training status` and `Training time of day`.
2. In `Handling statuses`, set the `Follow-up deadline (days)`. It applies to reports sent from then on.
3. Add a choice with `Add`, give it a name and a colour, and set whether it can be chosen. A status can also be set to close the report. Move rows to change their order.
4. To stop offering a choice, switch it off instead of deleting it. Items that already use it keep it.

> [!WARNING]
> `New` is where every report starts and cannot be deleted. At least one status must close a report, and one choice of each dropdown must stay on. A choice that is already used cannot be deleted.

## What happens next

Changes in `Content` and `Settings` appear in `My Compliance` and in the dropdowns straight away. The reporter sees the message you wrote and the new status under `My reports`, or on the public status page for an anonymous report.

## Common questions

**Why can I not change a report?** Your access lets you read but not edit. Ask an administrator for the `Edit` permission.

**Why does a status I added not appear in the dropdown?** It is switched off. Turn on `In use` for it in `Settings`.

**Can I delete a report?** No. Close it instead, so the record and its history stay.

## Related topics

- [[my-compliance]]
- [[web-report-management]]
- [[roles-and-menu-access]]

<!-- sources: routes/compliance.php, app/Http/Controllers/Legal/Compliance/*, app/Services/Compliance/*, resources/views/legal/compliance/management/*, app/Models/Compliance/* -->
