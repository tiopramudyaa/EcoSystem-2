---
key: resource-timeline
chapter: reporting
order: 122
title: Resource Timeline
summary: See where every consultant is placed day by day, and record or change a consultant's assignment to a customer.
requires: [reporting.resource-timeline]
updated: 2026-11-09
---
Resource Timeline is a calendar grid of your consultants. Each row is a consultant and each column is a day, so you can see who is placed at which customer and when.

## Who uses this

Delivery operation leaders, RPMO and administrators whose access includes `Reporting` › `Resource Timeline`.

## Before you start

- Assignments are recorded by customer code, so know the code of the customer first. A customer that is not in the master list can be typed in instead.
- Changing a timeline changes what everyone with access sees, so check the dates before you save.

## Step by step

### Read the grid

1. Open `Reporting` › `Resource Timeline`.
2. Choose `Monthly` or `Yearly`, then the month and year.
3. Narrow the rows with `Search customer code` and the `Home Base` filter (`Jakarta`, `Yogyakarta`, `Surabaya` or `Others`).
4. Read each row: `Consultant`, `Module`, `Status` and one cell per day. Weekend days are shaded.

### Record an assignment

1. Click a consultant's row, then `Create Timeline`.
2. Check the `Consultant`, then set `Start Date` and `End Date`.
3. Pick the customer from the `Customer Code` dropdown. If the customer is not in the master list, type its name in the field `Or enter a customer that is not in the master list` instead.
4. Select `Save`.

### Change or remove an assignment

1. Click the consultant's row. `Existing entries` lists each assignment with its customer code and dates.
2. To change one, select its edit icon. The form fills with that entry. Change `Start Date`, `End Date` or `Customer Code`, then select `Save`. Select `Cancel Edit` to stop without saving.
3. To remove one, select its delete icon and confirm `Clear`. Only that customer code is cleared for those dates.

## Rules to know

- Only active employees whose position is SAP Consultant, Salesforce Consultant or Project Management Officer appear as rows. Blocked employees and employees flagged for deletion do not appear.
- Rows are grouped by Module (A to Z). Within a group, Leads come first, then names A to Z. `Status` shows the Module Groups the person leads.
- `End Date` cannot be before `Start Date`.
- The `Customer Code` dropdown lists active customers from the master data. A code picked from it must exist in the master data.
- A customer outside the master list can be typed in the custom field. Choosing from the dropdown clears the custom field, and typing in the custom field clears the dropdown, so only one is saved. If the text matches a master code (ignoring upper or lower case), the master code is saved instead. Extra spaces are trimmed, and the text can be up to 255 characters. Only letters, numbers, spaces and the characters `.` `,` `&` `(` `)` `/` `_` `-` are accepted. Other characters, such as quotes or angle brackets, are rejected.
- A custom customer is not added to the master data. It is shown in the grid like any other customer and can be edited or cleared the same way.
- A consultant can be at more than one customer on the same day. Saving one customer does not overwrite another customer on the same dates. Both show in the grid.
- Saving with an empty `Customer Code` clears every customer in the chosen dates.
- When you edit an entry, the old dates and customer code are removed first, so shrinking or moving the dates leaves no leftover days.
- Only people whose access includes `Reporting` › `Resource Timeline` can read or change the data.
- Every create, change and delete is written to the Audit Log as one entry per action, with who did it, which consultant, which customer code and which dates.

## What happens next

The grid shows the new assignment on the days you chose, for everyone who can open the page.

## Common questions

**The grid is empty.** No consultant matches the filters, or no one is set up for the month. Clear the filters first.

**I cannot find a customer code.** If the customer should be in the master list, ask the owner of the customer data to add it. If it is a one-off customer outside the master list, type its name in `Or enter a customer that is not in the master list`. Spell it the same way each time, because different spellings count as different customers.

**I typed a customer but the dropdown went blank.** That is expected. Only one of the two fields is saved, and the one you used last wins.

<!-- sources: routes/web.php, app/Http/Controllers/ResourceTimelineController.php, resources/views/reporting/resource-timeline.blade.php -->
