---
key: dl-support-detail
chapter: delivery
order: 21
title: Support detail: General, Approval, Financial, Team and Customer PIC
summary: Read and update one delivery support: information, approval, sales data and payment terms, team, customer PIC, folder and linked tickets.
requires: [delivery-support.general.view, delivery-support.approval.view, delivery-support.financial.view, delivery-support.team.view, delivery-support.customer-pic.view, delivery-support.documents.view, delivery-support.remove-ticket]
updated: 2026-11-02
---
The support detail page is one long page, like the project detail. The tabs on top (`General`, `Approval`, `Financial`, `Team`, `Customer PIC`, `Activities`, `SLA`, `Plan Cost`, `Recons`) scroll to a section. This topic covers the first five and the buttons at the top.

## Who uses this

Support managers, helpdesk, support heads and the team of the support. Each section has its own `view`, `edit` and `create or delete` permission in Menu Access, so a section or button you may not use is hidden.

## Before you start

- A ticket can belong to only one delivery support. Putting a ticket in another one moves it from the first.
- Customer PICs decide what a customer sees in the customer portal (JARVIES), so keep them accurate.

## Step by step

![The Support Details page with the title buttons and the Support Information card](images/guide/dlv-support-detail.png)

*Screenshot with sample data.*

1. Open `Delivery` › `Support` and click an item. The title shows its customer, type and progress, with the buttons `Edit Support`, `Open Planning`, `Deliverable Folder`, `Documents`, `Updates & Notes`, `Remove Ticket` and `Delete Support`.
2. `Support Information` shows the name, client, type, method, modules, start and end date, estimate, total mandays and service window, with the overall progress at the right. Choose the pencil or `Edit Support` to change it.
3. `Approval Information` shows the approval date and who approved it (`Not approved yet` until set).

![The Financial Information card with sales data and the payment term table](images/guide/dlv-support-financial.png)

*Screenshot with sample data.*

4. `Financial Information` holds the `Sales Data`: IO number (unique), revenue, plan cost, gross profit and its percentage, actual figures and an optional vendor. Choose the `TOP Type` (`% of Revenue` or `Contract Line Item`) and use `Add Payment Term` to build the `Term Of Payment Plan`, as in a project.

![The Team and Customer PIC cards of a delivery support](images/guide/dlv-support-team.png)

*Screenshot with sample data.*

5. `Team` shows the `Delivery Owner`, `Support Manager`, `Co PM`, `Support Admin` and `Sales`. Choose the pencil to change them.
6. In `Customer PIC` choose `Manage Customer PIC` and pick the customer contacts of this support. Only those people see the tickets of this support in JARVIES.
7. `Deliverable Folder` gives access to the OneDrive folder for deliverables. `Documents` and `Updates & Notes` show how many files and notes the support has.
8. To take a ticket out of the support, choose `Remove Ticket`, pick the ticket in the window and confirm.
9. `Delete Support` removes the support for good after a confirmation.

## What happens next

Changes apply at once. Removing a ticket frees it to be assigned to another support. A customer contact added as PIC sees the support's tickets on their next sign-in.

## Common questions

**A customer cannot see their ticket in JARVIES.** Check that the person is in `Customer PIC` of the support the ticket belongs to.

**I cannot delete the support.** You may lack `Delete Delivery Support`, or the support may still have related data.

<!-- sources: routes/delivery-support.php, resources/views/delivery/support/list/show.blade.php -->
