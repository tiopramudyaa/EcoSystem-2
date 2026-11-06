---
key: mg-employee-data-lists
chapter: management
order: 17
title: Master Employee Settings: Basic Data, Address, Identification, Family, Education, Contract, Bank and Payment, Attachment
summary: Read-only lists of one kind of employee data across everyone, with search and a link to the full employee record.
requires: [management.employee.basic-data, management.employee.address, management.employee.identification, management.employee.family, management.employee.education, management.employee.contract, management.employee.bank, management.employee.payment, management.employee.attachment]
updated: 2026-11-02
---
Under `Management` › `Master Employee Settings` there is one list per kind of employee data: `Basic Data`, `Address`, `Identification`, `Family`, `Education`, `Contract`, `Bank`, `Payment` and `Attachment`. They let HR review one kind of data for all employees at once. Editing is done in the employee's own record.

## Who uses this

HR and administrators. Each list has its own menu in Menu Access, so you see only the lists your role was given.

> [!IMPORTANT]
> `Identification`, `Family`, `Bank`, `Payment` and `Attachment` hold personal and financial data. Open them only when your work needs it, and do not copy or share what you see.

## Before you start

- The lists are read-only. There is no add, change or delete button on them.
- Change the data itself in `Master` › `Employee`, in the matching tab of the employee.

## Step by step

![The Employee Basic Data list with ECI, name, position, department, since date, status and a Detail link](images/guide/mgmt-employee-basic-data.png)

*Screenshot with sample data.*

1. Open `Management` › `Master Employee Settings` and choose the list, for example `Basic Data`.
2. Read the columns of the list you opened:
   - `Basic Data`: ECI, name, position, department, since date and status.
   - `Address`: type, address, city and region.
   - `Identification`: ID type, ID number and valid until.
   - `Family`: family member name, relationship and birth date.
   - `Education`: institution, major, degree and graduation year.
   - `Contract`: contract type, start, end and status.
   - `Bank`: bank, account number and account name.
   - `Payment`: type, amount, currency and valid from.
   - `Attachment`: file name, type and size.
3. Type in the search box to find a person or a value. The placeholder tells you what it searches, for example name, ECI or position on `Basic Data`, and name, city or province on `Address`.
4. The line under the table counts the records found, such as `4 employee(s)`.
5. Choose `Detail →` on a row to open that employee's full record in `Master` › `Employee`.

## What happens next

Nothing is changed by reading a list. To correct data, open the employee through `Detail →` and edit it there.

## Common questions

**A list says `No data`.** Nobody has that kind of data entered yet, or your search matches nothing.

**I can open `Basic Data` but not `Bank`.** Each list is a separate menu. Ask an administrator for the one you need, with a reason.

<!-- sources: resources/views/management/employee/basic-data/index.blade.php, address, identification, family, education, contract, bank, payment, attachment -->
