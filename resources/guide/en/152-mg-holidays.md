---
key: mg-holidays
chapter: management
order: 12
title: Holidays
summary: Maintain national holidays, collective leave and custom holidays used by the system calendar and date pickers.
requires: [management.holidays]
updated: 2026-11-02
---
`Management` › `Holidays` keeps the list of non-working days that EcoSystem uses in its calendar and date pickers. Keep it current each year.

## Who uses this

Administrators, and any role given the `Management` › `Holidays` menu in Menu Access.

## Before you start

- Know the exact date, the name and the type: `National Holiday`, `Collective Leave` or `Custom Holiday` (for example a company anniversary).
- Two holidays with the same date and the same name are not allowed.

## Step by step

![The Holidays list with date, day, name, type, status and Edit and Delete buttons](images/guide/mgmt-holidays.png)

*Screenshot with sample data.*

1. Open `Management` › `Holidays`. Each row shows the date, the day of the week, the name, the type and the status.
2. Narrow the list with the year list (`All Years`) and the search box `Search holiday name...`.
3. To add a day, choose `Add Holiday`.

![The Add Holiday window with date, name, type and the Active tick](images/guide/mgmt-holiday-add.png)

*Screenshot with sample data.*

4. Fill `Date`, `Name` and `Type`. Keep `Active (applied to calendar & date pickers)` ticked so the day counts, or untick it to keep the entry without applying it. Choose `Save`.
5. To change a day, choose `Edit`. To remove it for good, choose `Delete` and confirm.

## What happens next

An active holiday is applied to the system calendar and date pickers straight away. An inactive one stays in the list but is shown dimmed and is not applied.

## Common questions

**It says the holiday already exists.** A holiday with the same date and name is already in the list. Edit that one instead.

**A government holiday changed date.** Edit the entry and change its `Date`. There is no need to delete it first.

<!-- sources: resources/views/management/holidays/index.blade.php, app/Http/Controllers/HolidayManagementController.php -->
