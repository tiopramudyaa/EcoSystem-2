---
key: mg-module-group
chapter: management
order: 14
title: Module Group
summary: Group related modules together (for example Logistic or Finance) so they can be used as one unit.
requires: [management.module-groups]
updated: 2026-11-02
---
`Management` › `Module Group` collects related modules into named groups, such as `Logistic` for `MM`, `SD` and `PM`. The modules themselves are kept in `Management` › `Qualification` (Master Module).

## Who uses this

Administrators and project administrators, and any role given the `Management` › `Module Group` menu in Menu Access.

## Before you start

- Create the modules first in the Master Module page, then group them here.
- A module can appear in the groups you choose. Decide the grouping before you start so the names stay consistent.

## Step by step

![The Module Group list with the modules of each group shown as small tags](images/guide/module-group.png)

*Screenshot with sample data.*

1. Open `Management` › `Module Group`. Each row shows the `Group Name`, the `Description`, the `Modules` in it as tags and the `Status`.
2. Find a group with the search box (`Search group name...`) or the status list (`All Status`).
3. To add a group, choose `Add Group`. Fill `Group Name` (required), an optional `Description`, pick the `Modules`, keep `Active` ticked and choose `Save`.
4. To change a group, choose `Edit`. To remove it, choose `Delete` and confirm.

## What happens next

The group is available straight away wherever modules are grouped. Deleting a group does not delete its modules.

## Common questions

**A module does not appear in the list to pick.** It is not yet in `Management` › `Qualification`. Add it there first.

**Can a module belong to two groups?** Yes. Add it to each group.

<!-- sources: resources/views/management/module-groups/index.blade.php, app/Http/Controllers/ModuleGroupController.php -->
