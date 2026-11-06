---
key: ms-employee-record
chapter: master
order: 11
title: Employee record: Basic Data, Address, Identification, Family, Education, Qualification, Contract, Bank, Payment, Attachment
summary: Read and update everything about one employee, tab by tab, with each tab shown only to roles that may see it.
requires: [employee.section.basic_data.view, employee.section.address.view, employee.section.identification.view, employee.section.family.view, employee.section.education.view, employee.section.qualification.view, employee.section.contract.view, employee.section.bank.view, employee.section.payment.view, employee.section.attachment.view]
updated: 2026-11-02
---
Click an employee in `Master` › `Employee` to open the record. The header shows the name, ECI, status, email, phone, department, division and since date. The tabs below hold the details.

## Who uses this

HR and administrators. Every tab has its own `view` and `update` permission in Menu Access, so a role may see `Basic Data` but not `Bank Account`. A tab you may not see is simply missing, and `Save Changes` is hidden on a tab you may not change.

> [!IMPORTANT]
> `Identification`, `Family`, `Bank Account`, `Basic Payment` and `Attachment` hold personal and financial data. Open them only when your work needs it.

## Before you start

- Change data only from a document or a written request, not from memory.
- The same data is listed for all employees in `Management` › `Master Employee Settings`. Those lists are read-only, and their `Detail →` link brings you here.

## Step by step

![The Employee Detail page with the header card and the Basic Data tab](images/guide/mst-employee-detail.png)

*Screenshot with sample data.*

1. Open `Master` › `Employee` and click the person. Use `Back to List` to return.
2. `Basic Data`: title, nick name, first and last name, gender, religion, marital status, birth date and place, since date, and the organizational data (personnel area and subarea, position, employee group and sub-group, division, department, home base, manager and more). Fields with a red star are required. The search terms are generated for you. Choose `Save Changes` after editing.
3. `Address`: one or more addresses, with the type and the primary one marked.
4. `Identification`: ID documents with their number, issuer and validity.
5. `Family`: family members with their relation and birth date.
6. `Education`: schools and degrees with the study branch and graduation year.
7. `Qualification`: the modules the consultant is qualified in. The list of modules itself is kept in `Management` › `Qualification`.
8. `Contract`: contract number, type, start and end date and whether it is active.
9. `Bank Account` and `Basic Payment`: the bank account and the basic payment of the person.
10. `Attachment`: supporting files of the employee.

On the tabs that hold lists, add, change or remove rows with the buttons of that tab, then save.

## What happens next

Saving updates the record at once and appears in `Control Center` › `Audit Log`. Changes to the position, division or department also change how the person shows in lists and reports.

## Common questions

**A tab is missing.** Your role does not have that section in Menu Access. Ask an administrator, with a reason.

**I cannot change the employee ID here.** Use `Change Employee ID` in the row menu of `Master` › `Employee`.

<!-- sources: routes/web.php, resources/views/master/employee/show.blade.php -->
