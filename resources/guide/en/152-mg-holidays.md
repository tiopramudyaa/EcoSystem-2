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

![The Holidays list with summary cards, filters, and a row for each holiday](images/guide/mgmt-holidays.png)

*Screenshot with sample data.*

1. Open `Management` › `Holidays`. Four cards at the top count the holidays listed and how many are national, collective leave and custom. Each row shows a small calendar tile, the date with its day of the week, the name, the type and the status. A holiday that is active and close shows a tag such as `Today`, `Tomorrow` or `in 12 days`, and one already gone shows `Past`.
2. Narrow the list with the year list (`All Years`), the search box `Search holiday name...`, and the type buttons `All`, `National Holiday`, `Collective Leave` and `Custom Holiday`. The count beside the buttons shows how many of the listed holidays you see.
3. To add a day, choose `Add Holiday`.

![The Add Holiday window with date, name, type and the Active switch](images/guide/mgmt-holiday-add.png)

*Screenshot with sample data.*

4. Fill `Date`, `Name` and `Type`. Keep the `Active` switch on so the day counts, or turn it off to keep the entry without applying it. Choose `Save`.
5. To change a day, choose the pencil button on its row (tooltip `Edit holiday`). To remove it for good, choose the trash button (tooltip `Delete holiday`) and confirm.

## Rules to know

- **Who may change holidays.** Only people given the `Management` › `Holidays` menu in Menu Access. The system checks this on every change, not only by hiding the menu.
- **Required fields.** `Date` must be a valid date, `Name` is required (up to 255 characters), and `Type` must be one of the three types.
- **No duplicates.** The same date with the same name cannot be saved twice, when adding or when editing. Two different names on one date are allowed.
- **Only active holidays count.** An inactive holiday stays in the list but is ignored by the calendar, the date pickers and the working-day calculation of ticket SLA.
- **Delete is permanent.** There is no undo. Turn `Active` off instead if you may need the day again.

## What happens next

An active holiday is applied to the system calendar, the date pickers and the working-day calculation of ticket SLA straight away. An inactive one stays in the list but is shown dimmed and is not applied.

## Common questions

**It says the holiday already exists.** A holiday with the same date and name is already in the list. Edit that one instead.

**A government holiday changed date.** Edit the entry and change its `Date`. There is no need to delete it first.

**How do I close the window without saving?** Choose the X or `Cancel`, press `Esc`, or click outside the window. Nothing is changed.

<!-- sources: resources/views/management/holidays/index.blade.php, app/Http/Controllers/HolidayManagementController.php -->
