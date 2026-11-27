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
4. Click an item to open its detail page.
5. The `Planning` tab shows the `Support Planning List`: each support with its progress, status, how many activities are completed and its timeline.

### Export the list

1. Choose `Export Excel` above the table. The button appears only if your role has the export function for Delivery Support.
2. A window titled `Export Delivery Support` opens. It shows the `Template` list and every `Fields` checkbox.
3. Tick the fields you want in the file. Type in `Search fields` to find one in a long list. `Select all` ticks every field and `Unselect all` unticks them.
4. To reuse this choice, type a name under `Save these fields as a template`, choose `Personal` or `Global` and choose `Save`. The template then appears in the `Template` list, marked `(Personal)` or `(Global)`.
5. Next time, open the same window and pick the template in `Template`. Its fields are ticked for you. `Global` templates made by colleagues are in the same list. Choose `Delete` to remove a template of yours you no longer need.
6. Choose `Export`. The file contains only the ticked fields.

### Add a support item

![The Create New Support form with basic information, timeline, team and approval](images/guide/dlv-support-create.png)

*Screenshot with sample data.*

1. Choose `Add Delivery Support`.
2. In `Basic Information` fill `Support Name`, `Client` and `Type` (required). The type decides the category of the support and of its tickets. `Support Method`, `Modules`, `IO Number` (unique, optional) and `Vendor` (optional, business partners of type Vendor only) are optional.
3. In `Timeline` give the `Start Date`, `End Date`, `Resolution Estimated` and the `Service Window`, the operational hours per day.
4. In `Team Assignment` choose the `Delivery Owner`, the `Support Manager` (one or more), the `Co PM`, the `Support Admin` and `Sales`.
5. Add `Total Mandays` and the `Approval` date and name if known, then choose `Create Delivery Support`.

Tickets are linked to a support from the ticket page, not from this form.

## Rules to know

### When you export

- The fields you can tick are the columns of the full export: `Customer`, `Support Name`, `Type`, `IO`, `Total Mandays`, `Start Date`, `End Date`, `Delivery Owner`, `Support Manager`, `Co PM`, `Support Admin` and `Sales`.
- Templates only choose columns. Which rows are exported is still decided by the filters, cards and search active on the list.
- With every field ticked you get the normal full export. With fewer fields the file has plain cells: a red header row, the first row frozen and columns sized to fit, without the number and alignment formats of the full export.
- At least one field must be ticked, and a template needs a name of up to 80 characters.
- A `Personal` template is seen and used only by you. A `Global` template is seen and used by everyone who can open this export, and is marked with the name of its creator.
- Only the creator can change a template. Others can use a `Global` template but not edit or delete it; to keep a variation, they save it under another name. An EC Administrator can delete any `Global` template.
- Each list has its own fields and its own templates.
- Among your own templates for one list a name is unique, whether `Personal` or `Global`. Saving with a name you already use replaces that template and can switch it between `Personal` and `Global`. A `Global` name must also differ from every other `Global` template of that list.
- `Save` stores the fields that are ticked at that moment. Changing the ticks of a chosen template and then choosing `Export` does not change the template.
- The window opens with the template you used last on this browser.
- If a field is removed from the export later, it simply disappears from your saved templates.
- Templates, `Global` ones included, do not change who may export. The export button still follows your access in `Menu Access`, so a `Global` template is only useful to people who can export this list.

## What happens next

The support appears in the list and can be picked when assigning a ticket. Its detail page is described in the next topics.

## Common questions

**There is no `Add Delivery Support` button.** Your role does not have `Add Delivery Support`. Ask your administrator.

**The IO number is refused.** It must be unique across all delivery supports.

<!-- sources: routes/delivery-support.php, resources/views/delivery/support/index.blade.php, resources/views/delivery/support/list/create.blade.php -->
