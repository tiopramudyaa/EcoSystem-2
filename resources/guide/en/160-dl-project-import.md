---
key: dl-project-import
chapter: delivery
order: 10
title: Project: Import Excel
summary: Add many delivery projects at once from an Excel file, and see which rows were imported, skipped as duplicates or refused.
requires: [delivery-project.add-new]
updated: 2026-11-25
---
`Import Excel` creates delivery projects from an Excel file, one project per row. Each row is checked like the `New Delivery Project Form`, and rows that already exist are reported and skipped.

## Who uses this

Project administrators and anyone whose role has `Add New Project` in Menu Access. Without it the `Import Excel` button is not shown.

## Before you start

- The customers must exist in `Master` › `Business Partner` as type Customer, and the project owners and internal account executives in Master Employee.
- The project types must be active in `Management` › `Project Type`.

## Step by step

1. Open `Delivery` › `Project` and choose `Import Excel` next to `Add New`. The same button is at the top of the `New Delivery Project Form`.
2. In the window `Import Projects from Excel`, choose `Download Template`. The file has three sheets: `Projects` to fill, `Lists` with the values for the dropdowns (project types, customers, employees and the fixed choices) and `Guide` with the rule of every column.
3. In `Projects`, fill one project per row from row 3. The columns are grouped like the form: `Project Form`, `Delivery Information` and `Location Information`. Columns marked `*` are required.
4. Back in the window, choose the file (.xlsx, .xls or .csv, at most 10 MB) and choose `Import`.
5. The result shows three numbers: `Imported`, `Duplicates skipped` and `Rows with errors`. Click a number to list only those rows. Each row shows its row number in Excel and the reason.
6. Fix the rows with errors in the file and import the same file again. Rows already imported are now reported as duplicates and skipped, so nothing is created twice.
7. Choose `Close`. When projects were imported, the project list opens with the new projects.

## Rules to know

- One file holds at most 1,000 projects. The header row is found by the column titles, so the columns may be in another order. The `No` column and the group titles above the headers are ignored.
- Required in every row: `Customer/Client`, `Project Owner`, `Project Type`, `Contract Start Date`, `Contract End Date`, `IO/Number Order`, `Project Name` and `Description`.
- `Customer/Client` is the customer code or the customer name. `(Induk)` after the name is allowed. When a parent customer and its branches share the name, the name means the parent; to choose a branch, fill its customer code. When the name belongs to customers that are not parent and branch, the row is refused; fill the customer code instead.
- `Project Owner`, and `Account Executive Name` when the type is `Internal`, must be the full name of an employee. An external account executive is free text. `Account Executive Type` is required when a name is filled.
- `Project Type` must be an active type written the same way, for example `Implementation`, not `Implementasi`.
- `High Level Risk` is Low, Moderate or High. `Delivery Method` is Onsite, Hybrid or WFH. `Type of Address` is Head Office or Plant. Upper or lower case does not matter.
- Dates may be Excel dates or text such as 2026-01-31, 31/01/2026 or 31-Jan-26. The end date must be on or after the start date.
- Revenue and Plan Cost are numbers of 0 or more. `Gross Profit (Rp)` and `% Gross Profit` in the file are not read: they are calculated again as Revenue minus Plan Cost, like the form. Warranty Period and Total Mandays are whole numbers. An email must be a valid address.
- Latitude and Longitude are filled together. When both are empty, the coordinates are taken from `Paste from Google Maps` (coordinates or a Google Maps link).
- An IO Number may be reused only within the same customer, like the form.
- Duplicates are reported and skipped, not imported: a row exactly the same as an earlier row in the file, and a row whose Customer, IO Number and Project Name already exist in EcoSystem or in an earlier imported row. Upper or lower case and extra spaces do not count as a difference.
- A row with an error is skipped; the other rows are still imported. Every import is recorded in `Audit Log`.

## What happens next

An imported project starts like one made with `Add New`: category Open, status On Track and the standard document folders. Add the team members, payment terms and planning on the project detail page.

## Common questions

**A customer "matches more than one Business Partner".** Two customers that are not parent and branch have that name. Fill the customer code; the codes are in the message and in the `Lists` sheet.

**The project went to the parent customer, not the branch.** A name shared by a parent and its branches means the parent. Fill the branch customer code to choose the branch.

**The Project Type is refused.** It must match an active Project Type exactly. Use the dropdown in the template.

**I imported the same file twice.** Nothing is created twice: the second time every row is reported as a duplicate.

<!-- sources: routes/web.php, app/Http/Controllers/DeliveryProjectImportController.php, app/Services/DeliveryProjectImporter.php, resources/views/delivery/project/projects/partials/import-projects-modal.blade.php, resources/views/delivery/project/projects/index.blade.php, resources/views/delivery/project/projects/create.blade.php -->
