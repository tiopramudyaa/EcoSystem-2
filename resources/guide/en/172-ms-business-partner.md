---
key: ms-business-partner
chapter: master
order: 20
title: Business Partner
summary: Find customers and vendors, create a business partner, group customers, and understand parent and end customers.
requires: [master.customer, master.customer.create, master.customer.action]
updated: 2026-11-02
---
`Master` › `Business Partner` is the list of the companies EcoSystem works with: customers (the clients of projects, supports and tickets) and vendors (selectable as the vendor of a delivery support).

## Who uses this

Sales, HR and administrators, project and support staff, and any role given the `Master` › `Business Partner` menu in Menu Access. `Create Business Partner` and the row menu appear only for roles given `Create Business Partner` and `Actions (Edit/Delete)`.

## Before you start

- Check that the company is not in the list already, also under its parent customer.
- Know the customer code (up to 4 letters and numbers), the company name and, if relevant, the email domain.

## Step by step

![The Business Partner list with email, company name, type, group, category, industry and status](images/guide/mst-partner-list.png)

*Screenshot with sample data.*

### Find a business partner

1. Open `Master` › `Business Partner`. The title line says how many are shown.
2. Use the funnel icons in the column titles (email, company name, type, customer group, customer category, industry sector, status) to filter.
3. A company that belongs to a parent shows the parent's name under its own, for example `Customer A Plant Surabaya` under `Customer A`.
4. Click a row to open the record (see the next topic).

### Create a business partner

![The Create Business Partner window with general data, standard address and organizational data](images/guide/mst-partner-create.png)

*Screenshot with sample data.*

1. Choose `Create Business Partner`.
2. In `General Data` choose the `Type`: `Customer` or `Vendor`. Fill the `Customer Code` (required, up to 4 letters and numbers), the `Company Name` (required) and optionally the company email, the title (PT, CV, UD) and the `Email Domain`.
3. The `Email Domain` routes incoming mail: an email whose sender ends with this domain goes to this customer's `Ticket Validation` list.
4. In `Standard Address` fill the street, building, postal code and the country and region chain.
5. In `Organizational Data` choose the `Parent Customer` (leave empty for a top-level customer), the `Customer Group`, the category and the credit limit type. More detail can be added later in the record.
6. Save. Login access is not set here: it is granted per contact person later.

### Parent customers, end customers and groups

- A parent customer can have end customers (for example the plants of one company). Tickets and credentials of an end customer are handled under that end customer.
- `View Grouping` opens `Customer Grouping`: the first part lists parent customers with their end customers, the second part lists flat `Customer Groups`.
- In `Customer Groups` choose `New group` to create one, `Add member` to put a customer in it, and `Edit` or `Delete` to change it. Members of a group stay independent customers, with their own login, contacts and tickets, and are only shown together.

![Customer Grouping with parent and end customers and the customer groups](images/guide/mst-partner-grouping.png)

*Screenshot with sample data.*

### Delete a business partner

Choose `Delete` in the row menu and confirm. The action cannot be undone.

## What happens next

The customer is offered in projects, supports, tickets and reports. Mail from its email domain is routed to its staging list.

## Common questions

**I created a duplicate by mistake.** Check the parent customer first: a company with the same name under a parent may be a separate end customer on purpose.

**There is no `Create Business Partner` button.** Your role does not have it. Ask your administrator.

<!-- sources: routes/web.php, resources/views/master/customer/index.blade.php, resources/views/master/customer/grouping.blade.php -->
