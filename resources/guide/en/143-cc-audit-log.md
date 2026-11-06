---
key: cc-audit-log
chapter: control-center
order: 22
title: Audit Log
summary: Trace who created, changed or deleted a record in any module, and see the values before and after.
requires: [control-center.audit-log]
updated: 2026-11-02
---
`Audit Log` records every create, update and delete across the modules of EcoSystem (tickets, customers, roles, delivery projects, settings and more). Use it to answer "who changed this, when, and what was it before".

## Who uses this

Administrators, and any role given the `Control Center` › `Audit Log` menu in Menu Access.

> [!IMPORTANT]
> Entries can show business data. Open `View` only when you need it, and do not share what you see.

## Before you start

- Know the module, the record (for example a ticket number or a customer name) or the person who made the change.
- Times are shown in WIB. The list is read-only: entries cannot be edited or deleted.

## Step by step

![The Audit Log page with summary cards and a table of changes, each with a View link](images/guide/cc-audit-log.png)

*Screenshot with sample data.*

1. Open `Control Center` › `Audit Log`.
2. Read the cards: `Total Records`, `Created`, `Updated` and `Deleted`.
3. Narrow the list with the column filters:
   - `Activity`: click the funnel icon and type a word from the description, the record or the actor.
   - `Module`: pick one module, such as `Ticket`, `Customer` or `Role`.
   - `Event`: pick `Created`, `Updated` or `Deleted`.
   - `Time (WIB)`: choose a `From` and `To` date, then `Apply`.
4. Each row shows the description, the record, the `Module`, the `Event`, the `Actor` and the time.
5. Choose `View` in the `Changes` column to see each field with its `Before` and `After` value. For a deleted record the window shows the values it had before deletion.
6. Use `Clear filters` to start over.

## What happens next

Nothing is changed. If a change was not expected, ask the actor first, then correct the record in its own module.

## Common questions

**A row has no `View` link.** That entry has no stored field values, for example a plain event.

**Does it show passwords or secrets?** No. Fields such as passwords and tokens are never recorded.

<!-- sources: routes/web.php, app/Http/Controllers/AuditLogController.php, resources/views/admin/audit-log.blade.php -->
