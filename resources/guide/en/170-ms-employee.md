---
key: ms-employee
chapter: master
order: 10
title: Employee
summary: Find employees, create a new employee, assign roles, change an employee ID or password, reset two-step verification, export the list and delete an employee.
requires: [master.employee, master.employee.create, master.employee.action, master.employee.reset-two-factor]
updated: 2026-11-12
---
`Master` › `Employee` is the list of everyone who works with EcoSystem. An employee is also a user account: the employee ID (ECI) is the username they sign in with.

## Who uses this

HR, administrators, and any role given the `Master` › `Employee` menu in Menu Access. `Create Employee` and the row menu appear only for roles given `Create Employee` and `Actions (Edit/Delete)`. `Reset 2FA` in the row menu needs one more permission, `Reset Two-Factor Authentication`, which only an EC Administrator has unless Menu Access says otherwise.

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
- `Reset 2FA`: turns off the person's two-step verification when they lost their phone and their recovery codes. See the next part.
- `Delete`: removes the employee permanently after you confirm.

### Reset a person's two-step verification

Use this only when a person lost their phone and their recovery codes and cannot sign in. Make sure the request really comes from that person, for example by asking them in person or by phone.

![The row menu of an employee with Change Password, Change Employee ID, Change Role, Reset 2FA and Delete](images/guide/employee-reset-2fa-menu.png)

*Screenshot with sample data.*

1. Choose the menu icon on the person's row, then `Reset 2FA`.
2. Read the message and select `OK`.

![The confirmation window asking to reset two-factor authentication for the person](images/guide/employee-reset-2fa-confirm.png)

*Screenshot with sample data.*

3. When asked, enter your own 2FA code and submit.
4. A message says the person was notified. They can now sign in with their password and set up two-step verification again.

## Rules to know

- The row menu appears only with `Actions (Edit/Delete)`. `Reset 2FA` also needs `Reset Two-Factor Authentication`. An EC Administrator has it by default, and Menu Access can change that.
- You cannot reset your own two-step verification here. Use `Settings` › `Security`.
- The person must have two-step verification turned on. Otherwise the reset is refused.
- You must have two-step verification turned on yourself, and you must enter a fresh code from your own authenticator app for every reset. A wrong code is refused, and repeated wrong codes lock your account for a while.
- A reset removes the person's key, recovery codes and trusted browsers. From then on they sign in with their password only. If their role requires two-step verification, they are asked to set it up again after they sign in.
- The person is told in the app and by email, so a reset they did not ask for does not go unnoticed. If the email cannot be sent, the reset still stands and the message tells you so.
- Every reset is written to `Control Center` › `Audit Log` with your name and the person's name.
- `Change Password`: at least 6 characters, typed twice. `Change Employee ID`: must be new, at most 50 characters, needs your own password, and ends the person's current sessions. `Change Role`: at least one role must stay selected, and giving someone the EC Administrator role needs a fresh 2FA code from you. `Delete` cannot be undone.

## What happens next

A new employee gets an activation email at the work email. A changed role applies from the person's next page load. Every change is recorded in `Control Center` › `Audit Log`. After a `Reset 2FA`, ask the person to set up two-step verification again soon.

## Common questions

**I deleted an employee by mistake.** Deleting is permanent and cannot be undone. Create the person again. To stop someone signing in without losing data, ask the technical team to block the account instead of deleting it.

**The employee ID cannot be edited.** Use `Change Employee ID` from the row menu.

**Where is the rest of the person's data?** Click the row and use the tabs, as described in the employee record topic.

<!-- sources: routes/web.php, routes/api.php, resources/views/master/employee/index.blade.php, app/Http/Controllers/EmployeeController.php, app/Services/SecurityMailer.php -->
