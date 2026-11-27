---
key: ticket-export
chapter: work-service
order: 21
title: Export Tickets
summary: Download the ticket list as an Excel file, choose which fields go into it and save that choice as a template.
requires: [ticket.export]
updated: 2026-11-25
---
On the `Support Tickets` page, the `Export` button downloads the tickets you are looking at as an Excel file. You choose the fields first, and you can keep that choice as a template for next time.

## Who uses this

Anyone whose role has the `Export` function for tickets in `Menu Access`. Ticket leads, Helpdesk and heads use it most to report on tickets.

## Before you start

- Set the filters on the `Support Tickets` page first: status card, column filters, date range, and the search boxes. The file follows exactly what you set.
- Decide which fields you need. The full file has many columns, for example `Last Update`, `Tiket`, `Description`, `Start Date`, `Close Date`, `Day on Close`, `Customer`, `End Customer`, `Ticket Lead`, `Priority`, `Scale`, `Status`, `Type`, `Module`, `Assign Delivery`, `Customer Mandays`, `Activity Date`, `Progress`, the response and resolution time columns and their statuses.

## Step by step

### Choose fields and export

1. On `Support Tickets`, set your filters, then choose `Export` at the top right.
2. A window titled `Export Support Tickets` opens. It shows the `Template` list and every `Fields` checkbox.
3. Tick the fields you want in the file. Type in `Search fields` to find one in a long list. `Select all` ticks every field and `Unselect all` unticks them.
4. To reuse this choice, type a name under `Save these fields as a template`, choose `Personal` or `Global` and choose `Save`. The template then appears in the `Template` list, marked `(Personal)` or `(Global)`.
5. Next time, open the same window and pick the template in `Template`. Its fields are ticked for you. `Global` templates made by colleagues are in the same list. Choose `Delete` to remove a template of yours you no longer need.
6. Choose `Export`. The file contains only the ticked fields.

### Use a template again

1. Choose `Export` again.
2. Pick your template in `Template`. The fields of that template are ticked.
3. Choose `Export`.

Example: template `A` holds `Tiket`, `Description` and the SLA columns, template `B` holds `Tiket`, `Description` and `Customer`. Create each one once, then pick `A` or `B` whenever you export.

## Rules to know

### Who can export and which tickets are included

- Exporting needs the `Export` function for tickets in `Menu Access`. Without it the download is refused, even if the address is opened directly.
- The file contains the tickets that match the filters on the page: status card, `Status`, `Priority`, `Scale`, `Type`, `Document Status`, `Module`, `Assign Delivery`, `Customer`, `Ticket Lead`, the date range (by the date the ticket was created) and the ticket number and description search.
- Hidden tickets and deleted tickets are never in the file.
- The file is named `TICKET SUPPORT` with today's date and is sorted by ticket number from oldest to newest.

### When you export

- The fields you can tick are the columns of the full ticket export.
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

The file downloads in your browser. Your template stays saved and is offered again the next time you choose `Export`.

## Common questions

**Can a colleague use my template?** Only if you saved it as `Global`. A `Personal` template is visible to you alone. Colleagues who use a `Global` template cannot change it, but they can save their own version under another name.

**My template lost a field.** The field was removed from the export after you saved the template. Tick the fields you want and choose `Save` with the same name to refresh it.

**The file has fewer rows than I expected.** The filters on the page are still applied. Clear them first if you want every ticket.

<!-- sources: routes/web.php, app/Http/Controllers/TicketController.php, app/Exports/TicketExport.php, app/Support/ExportColumns.php, resources/views/ticket/index.blade.php, resources/views/partials/export-picker.blade.php -->
