---
key: dl-support
chapter: delivery
order: 20
title: Support
summary: Find delivery support items, filter them with the summary cards, add a new one, export the list and follow the planning list.
requires: [delivery.support, delivery-support.add-new, delivery-support.export, delivery-support.delete-support]
updated: 2026-11-02
---
`Delivery` › `Support` lists every delivery support item: a support contract that tickets are worked under (for example AMS, MO or hypercare). Each ticket belongs to one delivery support.

## Who uses this

Helpdesk, support heads and managers, project and RPMO staff, and any role given the `Delivery` › `Support` menu in Menu Access. Buttons such as `Add Delivery Support` appear only for roles that were given them.

## Before you start

- Know the customer, the support type, the period and the team of the contract.
- The customer must exist in `Master` › `Business Partner`, and support types come from `Management` › `Support Type`.

## Step by step

![The Delivery Support list with summary cards and the table of support items](images/guide/dlv-support-list.png)

*Screenshot with sample data.*

### Find a support item

1. Open `Delivery` › `Support`. The tabs on top are `Support` (the list) and `Planning`.
2. Read the `SUMMARY` cards: `Total Delivery Support`, `In Progress`, `Completed`, `Not Started`, `Ending ≤ 30 days`, `Past End Date`, `Upcoming` and `No Timeline`. Click a card to filter.
3. Type in the search box (client, ticket ID or description) or use the funnel icons in the column titles. Columns include `Customer`, `Support Name`, `Support Type`, `IO Number`, `Progress` and `Timeline`.
4. Click an item to open its detail page. Choose `Export Excel` to download the list.
5. The `Planning` tab shows the `Support Planning List`: each support with its progress, status, how many activities are completed and its timeline.

### Add a support item

![The Create New Support form with basic information, timeline, team and approval](images/guide/dlv-support-create.png)

*Screenshot with sample data.*

1. Choose `Add Delivery Support`.
2. In `Basic Information` fill `Support Name`, `Client` and `Type` (required). The type decides the category of the support and of its tickets. `Support Method`, `Modules`, `IO Number` (unique, optional) and `Vendor` (optional, business partners of type Vendor only) are optional.
3. In `Timeline` give the `Start Date`, `End Date`, `Resolution Estimated` and the `Service Window`, the operational hours per day.
4. In `Team Assignment` choose the `Delivery Owner`, the `Support Manager` (one or more), the `Co PM`, the `Support Admin` and `Sales`.
5. Add `Total Mandays` and the `Approval` date and name if known, then choose `Create Delivery Support`.

Tickets are linked to a support from the ticket page, not from this form.

## What happens next

The support appears in the list and can be picked when assigning a ticket. Its detail page is described in the next topics.

## Common questions

**There is no `Add Delivery Support` button.** Your role does not have `Add Delivery Support`. Ask your administrator.

**The IO number is refused.** It must be unique across all delivery supports.

<!-- sources: routes/delivery-support.php, resources/views/delivery/support/index.blade.php, resources/views/delivery/support/list/create.blade.php -->
