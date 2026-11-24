---
key: dl-project-detail
chapter: delivery
order: 11
title: Project detail: General, Delivery Info, Team, Documents and Location
summary: Read and update one project: general information, sales data and payment terms, team members, documents and location.
requires: [delivery-project.general.view, delivery-project.delivery-data.view, delivery-project.delivery-info.view, delivery-project.location.view, delivery-project.team.view, delivery-project.documents.view]
updated: 2026-11-02
---
The project detail page is one long page. The tabs on top (`General`, `Delivery Info`, `Stakeholders`, `Team`, `Documents`, `Issues`, `Risks`, `Location`, `Planning`, `WRICEF`, `Plan Cost`) scroll to a section. This topic covers the first group. The registers and the plan are in the next two topics.

## Who uses this

Project heads, project administrators and the team of the project. Each section has its own `view`, `edit` and `create or delete` permission in Menu Access. A section you may not see is simply missing, and a pencil or `Add` button you may not use is hidden.

## Before you start

- A closed project is locked. Reopen it first if you need to change it.
- Revenue and payment terms are financial data. Check them before you save.

## Step by step

![The Project Detail page with the tabs on top and the General Information card](images/guide/dlv-project-detail.png)

*Screenshot with sample data.*

1. Open `Delivery` › `Project` and click a project. The title bar shows the project and customer, with the buttons `Create Folder`, `Close Project` and `Delete Project`.
2. In `General Information` read the customer, name, owner, type, IO number, category, phase, contract dates and go-live estimate. Choose the pencil icon to edit. `Category`, `Phase` and `Go Live Estimated` are filled from the planning and cannot be typed.
3. `Delivery Data` holds the delivery method, the warranty period and the total mandays.

![The Delivery Information card with sales data and the Term Of Payment plan](images/guide/dlv-project-delivery-info.png)

*Screenshot with sample data.*

4. In `Delivery Information` the `Sales Data` shows the account executive, `Revenue`, `Plan Cost`, `Gross Profit`, its percentage and the actual figures. Choose the pencil to edit.
5. Under it, choose the `TOP Type`: `% of Revenue` (the amount follows the payment percentage) or `Contract Line Item`. Then use `Add Payment Term` to add each term with its percentage or amount, requirement, estimated date, invoice data, paid date and status. The footer shows the total and warns when the terms are below or above the revenue. `Export Excel` downloads the table.

![The Team Members table with name, position, module, role and period](images/guide/dlv-project-team.png)

*Screenshot with sample data.*

6. In `Team Members` choose `Add Team Member` to add a person with a module, a role and a period. Use the row actions to change or remove a member.
7. In `Project Documents` choose `Create Folder` first: documents are stored in a OneDrive folder and the page says so until it exists. Then `Upload Document`, and change or delete a document from its row.
8. `Location Information` holds the location name, address type, country, region, city, validity and street. Choose the pencil to edit.

## What happens next

Saving a section updates the project at once and refreshes `Last Update`. The financial figures feed reports such as `Collection Outlook`.

## Common questions

**Documents cannot be uploaded.** Create the folder first with `Create Folder`.

**I can see Delivery Info but not edit it.** You have `view` but not `edit`. Ask for the permission if your work needs it.

**A payment term total does not match the revenue.** The footer tells you how much is missing or extra. Adjust a term or the revenue.

<!-- sources: resources/views/delivery/project/projects/show.blade.php, routes/web.php -->
