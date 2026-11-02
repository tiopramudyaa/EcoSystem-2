---
key: mg-dropdown-settings
chapter: management
order: 16
title: Dropdown Settings
summary: Manage the lists behind the dropdowns of Employee Information, such as Position, Division and Department.
requires: [management.employee.dropdown-settings]
updated: 2026-11-02
---
`Management` › `Master Employee Settings` › `Dropdown Settings` controls the choices shown in the employee form: Department, Division, Employee Group, Employee Subgroup, Personnel Area, Personnel Subarea, Position and any list you add.

## Who uses this

Administrators and HR, and any role given the `Management` › `Dropdown Settings` menu in Menu Access.

## Before you start

- A value is stored on the employee record exactly as written. Type it exactly as it should appear, with the right spelling and capitals.
- Turning a whole list off hides that field from the Employee form, not just its options.

## Step by step

![Dropdown Settings with the list of dropdown lists on the left and the values of Department on the right](images/guide/mgmt-dropdown-settings.png)

*Screenshot with sample data.*

1. Open `Management` › `Dropdown Settings`. The left side lists every dropdown, with its code and how many values it has.
2. Choose a list. Its values appear on the right with the `Order`, the `Value` and the `Status`. Filter them with `Search value` and the `All Status` list, and `Clear` to reset.
3. To add a value, choose `Add Value`, type the `Value`, keep `Active` ticked (offered in the dropdown) and `Save`. A value must be new within its list.
4. Use the arrows to move a value up or down. The order is the order people see.
5. Use the pencil to rename or switch a value to inactive, so it is no longer offered. Use the bin to delete it.
6. To add a new list, choose `New Dropdown List`. Fill `Name` (the label shown on the form), a unique `Code` and a `Description`.
7. To change or remove a whole list, use the pencil or the bin next to its title.

## What happens next

The change is offered at once in the Employee form. Employees who already hold an older value keep it on their record.

## Common questions

**A value I added does not appear.** Check that it is `Active` and that the list itself is on.

**The code says it already exists.** Every list needs its own unique `Code`. Pick another.

<!-- sources: resources/views/management/employee/dropdown-settings/index.blade.php, app/Http/Controllers/DropdownConfigController.php -->
