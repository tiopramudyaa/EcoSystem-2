---
key: ms-employee
chapter: master
order: 10
title: Employee
summary: Find employees, create a new employee, assign roles, change an employee ID or password, export the list and delete an employee.
requires: [master.employee, master.employee.create, master.employee.action]
updated: 2026-11-02
---
`Master` › `Employee` is the list of everyone who works with EcoSystem. An employee is also a user account: the employee ID (ECI) is the username they sign in with.

## Who uses this

HR, administrators, and any role given the `Master` › `Employee` menu in Menu Access. `Create Employee` and the row menu appear only for roles given `Create Employee` and `Actions (Edit/Delete)`.

> [!IMPORTANT]
> This page holds personal data and controls who can sign in. Make changes carefully, and never share a password in chat or email.

## Before you start

- Know the full name, the employee ID, the work email (it receives the account activation link) and the position of the person.
- Roles decide which menus a person sees. Pick them with the person's manager, and see `Management` › `Role` for what each role opens.

## Step by step

![The Employee Management list with filters in the column headers and the Create Employee button](images/guide/mst-employee-list.png)

*Screenshot with sample data.*

### Find an employee

1. Open `Master` › `Employee`. The title line says how many employees are shown.
2. Use the funnel icon in a column title to filter by `ECI`, `Full Name`, `Position`, `Module`, `Employee Group`, `Division`, `Department`, `Home Base`, `Personnel Area`, `Employee Type`, `Status` and more. `Clear` resets a filter.
3. The `Status` column is `Active`, `Blocked` or `Flagged for Deletion`. A blocked or flagged person cannot sign in.
4. Click a row to open the employee's record. `Export Excel` downloads the list. `Manage Dropdown Data` opens the dropdown lists used by the form.

### Create an employee

![The Create Employee window with general data, standard address and organizational data](images/guide/mst-employee-create.png)

*Screenshot with sample data.*

1. Choose `Create Employee`.
2. In `General Data` fill the employee ID, a password and its confirmation, the name, nick name, gender, birth date and work email. The work email is where the activation link is sent.
3. In `Standard Address` fill the street and the region chain (province, city, district, village).
4. In `Organizational Data` choose the personnel area, position, employee group and sub-group, division and home base.
5. Save. The person activates the account through the link sent to the work email, then signs in with the employee ID.

### The row menu

Choose the menu icon on a row for:

- `Change Password`: type a new password (at least 6 characters) twice.
- `Change Employee ID`: the ID is also the login username, so it is locked in the normal form. Type the new ID twice and confirm with your own password.
- `Change Role`: tick one or more roles in `Assign Roles` and save. A person may hold several roles and gets the menus of all of them.
- `Delete`: removes the employee permanently after you confirm.

## What happens next

A new employee gets an activation email at the work email. A changed role applies from the person's next page load. Every change is recorded in `Control Center` › `Audit Log`.

## Common questions

**I deleted an employee by mistake.** Deleting is permanent and cannot be undone. Create the person again. To stop someone signing in without losing data, ask the technical team to block the account instead of deleting it.

**The employee ID cannot be edited.** Use `Change Employee ID` from the row menu.

**Where is the rest of the person's data?** Click the row and use the tabs, as described in the employee record topic.

<!-- sources: routes/web.php, resources/views/master/employee/index.blade.php -->
