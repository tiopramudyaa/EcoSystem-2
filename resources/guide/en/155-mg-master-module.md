---
key: mg-master-module
chapter: management
order: 15
title: Qualification (Master Module)
summary: Maintain the list of modules consultants can be qualified in, with their leads and members.
requires: [management.employee.qualification]
updated: 2026-11-02
---
`Management` › `Master Employee Settings` › `Qualification` opens the page called Master Module. It is the master list of modules (for example `ABAP`, `FI`, `MM`) that consultants are qualified in and that tickets are assigned by.

## Who uses this

Administrators, and any role given the `Management` › `Qualification` menu in Menu Access.

## Before you start

- Module leads are the people responsible for tickets of that module, so keep the lead list accurate.
- Deleting a module is not harmless: qualification records that use it lose their module reference.

## Step by step

![The Master Module list with groups, leads, members and status of every module](images/guide/mgmt-master-module.png)

*Screenshot with sample data.*

1. Open `Management` › `Qualification`. Each row shows the `Module Name`, `Description`, `Groups` it belongs to, the `Leads`, the `Members` and the `Status`.
2. Find a module with the search box (`Search module name...`) or the status list (`All Status`: `Active` or `Inactive`).
3. To add a module, choose `Add Module`. Fill `Module Name` (required) and an optional `Description`, keep `Active` ticked and choose `Save`.
4. To change a module, choose the pencil icon on its row.
5. To set the leads, choose `Manage` in the `Leads` column. Search an employee, choose `Add Lead`, and remove a lead from `Current Leads`. Choose `Close` when done.
6. To see who is qualified in the module, choose `View` in the `Members` column.
7. To remove a module, choose the bin icon and confirm `Delete Module?`.

## What happens next

The module is offered wherever consultants and tickets are tied to modules. Group membership is managed in `Management` › `Module Group`.

## Common questions

**The page title says Master Module, not Qualification.** It is the same page. The menu uses the first name and the page uses the second.

**Someone cannot be added as lead.** They are probably already a lead of this module, the list marks them `(already a lead)`.

<!-- sources: resources/views/management/employee/qualification/index.blade.php, app/Http/Controllers/ModuleController.php -->
