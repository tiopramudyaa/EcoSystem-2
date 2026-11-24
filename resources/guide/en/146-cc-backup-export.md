---
key: cc-backup-export
chapter: control-center
order: 25
title: Backup & Export
summary: Create a database backup, export data to CSV or ZIP and import data in bulk, in the right order.
requires: [control-center.backup]
updated: 2026-11-02
---
`Backup & Export` is the administrator's toolbox for taking data out of EcoSystem and bringing data in: a full database backup, CSV or ZIP exports, and bulk imports.

## Who uses this

Administrators, and any role given the `Control Center` › `Backup & Export` menu in Menu Access.

> [!IMPORTANT]
> Backups and exports hold real customer and employee data. Store the files safely, never email them around, and delete copies you no longer need.

## Before you start

- Imports change data and are hard to undo. Download the template first, fill it in, and test with a few rows.
- Import in the order shown on the page: master data first, then details that depend on it (see step 8).

## Step by step

![The Backup & Export page with a Database Backup card and a Ticket Data export and import card](images/guide/cc-backup-export.png)

*Screenshot with sample data.*

### Database backup

1. Open `Control Center` › `Backup & Export`.
2. In `Database Backup`, choose `Create Backup Now`. A full copy is made on the server and listed under `Existing Backups`.
3. Choose `Refresh` to update the list and use the download link on a file. If the server cannot make backups you see the message `mysqldump not found on this server`: ask the technical team.

### Export tickets

4. In `Ticket Data`, pick the `Year` and `Month` (or `All Months`) and choose `Download CSV`. The file holds ticket number, subject, status, priority, scale, type, customer, PIC, mandays and SLA fields.
5. In `Ticket Migration (Full ZIP)` use `Download ZIP` to export tickets with their chat history and attachments. It can take several minutes for a large period.

### Import data

6. Choose `Download Template (.xlsx)` on the card you need, fill in the file and drop it in `Drop CSV here or browse`, then choose the import button. For tickets, rows with a new ticket number create tickets and rows with an existing number update them. Rows without a ticket number are skipped.
7. To restore a ZIP made by `Ticket Migration`, use `Import ZIP`. Existing tickets are updated and existing messages are not duplicated.
8. For the other imports, work through the steps on the page in order: `Master Data` (`Employee Data`, `Customer Data`), then `Employee Qualification`, `Delivery Support`, `Customer Contact Person`, then `Ticket Members Import`, then `Resolution Days` and `Timesheet`.
9. If some rows fail, read `Import Errors` at the bottom of the page. Fix those rows and import again.

## What happens next

Exports download to your computer. Imports create or update records at once, and they appear in `Audit Log`.

## Common questions

**An import says the customer or employee was not found.** Import the master data first, or fix the spelling in your file.

**Can I import tickets from the old system?** Yes. `Import Tiket dari Sistem Lama (API)` pulls tickets and chat history from a list of ticket numbers. Use it only with help from the technical team.

<!-- sources: routes/web.php, app/Http/Controllers/AdminBackupController.php, resources/views/admin/backup.blade.php -->
