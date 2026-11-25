---
key: dl-project
chapter: delivery
order: 10
title: Project
summary: Find delivery projects, filter them with the summary cards, add a new project or import many from Excel, export the list and close or delete a project.
requires: [delivery.project, delivery-project.add-new, delivery-project.close-project, delivery-project.delete-project]
updated: 2026-11-25
---
`Delivery` › `Project` lists every delivery project of the company. From here you open a project, add a new one and export the list.

## Who uses this

Project heads, project administrators, RPMO and management, and any role given the `Delivery` › `Project` menu in Menu Access. Buttons such as `Add New` appear only for roles that were given them.

## Before you start

- Know the customer, the project owner, the project type, the contract dates and the IO number of the project.
- The customer must already exist in `Master` › `Business Partner`, and the project types come from `Management` › `Project Type`.

## Step by step

![The Project Delivery list with summary cards and the table of projects](images/guide/dlv-project-list.png)

*Screenshot with sample data.*

### Find a project

1. Open `Delivery` › `Project`. The three tabs on top are `Project`, `Issues` and `Planning`. `Project` is the list.
2. Read the `SUMMARY` cards: `Total Projects`, `Open`, `In Process`, `Closed`, `On Track`, `At Risk`, `At Critical` and `Ending ≤ 30 days`. Click a card to filter the list to it.
3. In the table, each project shows its `Last Update`, `IO Number`, `Customer`, `Contract Start & End Date`, `Project Type`, `Project Name`, `AE`, `Delivery Owner`, `Project Manager`, `Progress`, `Method`, `Category` and `Project Status`. The most recently updated project is on top. Use the arrows in a column title to sort and the funnel icon to filter. `Contract Start & End Date` can be sorted by the start date or by the end date. `Progress` sorts with one click on its title. On a phone the same fields appear on each project card.
4. Click a project to open its detail page (see the topic about the project detail).
5. Choose `Export Excel`, then `Project List`, to download the list. The other items in that menu (`Project Revenue`, `Invoiced Terms of Payment`) download straight away as before.

### Choose the fields to export

1. A window titled `Export Delivery Projects` opens. It shows the `Template` list and every `Fields` checkbox.
2. Tick the fields you want in the file. Type in `Search fields` to find one in a long list. `Select all` ticks every field and `Unselect all` unticks them.
3. To reuse this choice, type a name under `Save these fields as a template`, choose `Personal` or `Global` and choose `Save`. The template then appears in the `Template` list, marked `(Personal)` or `(Global)`.
4. Next time, open the same window and pick the template in `Template`. Its fields are ticked for you. `Global` templates made by colleagues are in the same list. Choose `Delete` to remove a template of yours you no longer need.
5. Choose `Export`. The file contains only the ticked fields.

### Add a project

![The New Delivery Project Form with the required fields marked](images/guide/dlv-project-create.png)

*Screenshot with sample data.*

1. Choose `Add New`.
2. Fill the required fields marked with a red star: `Customer/Client`, `Project Owner`, `Project Type`, `Contract Start Date`, `Contract End Date`, `IO/Number Order`, `Project Name` and `Description`. `High Level Risk` is optional.
3. `Category`, `Phase` and `Go Live Estimated` cannot be typed. They are filled in automatically from the project planning once it exists.
4. The same form also holds the sales data (`Delivery Information`), `Delivery Data`, the first `Team Members` and the `Location Information`. Fill what you know now, the rest can be added later on the project detail page.
5. Submit the form. The new project then appears in the list.

To add many projects at once, `Import Excel` (next to `Add New`) reads them from an Excel file. It has its own topic in this chapter.

### Close, reopen or delete a project

On the project detail page:

- `Close Project` locks the project: its data can no longer be changed. The button then becomes `Reopen Project` for people who may use it.
- `Delete Project` removes the project for good. If the project still has related records, EcoSystem refuses and tells you it may still have related records. Close the project instead when work has ended.

## Rules to know

### Where the columns come from

- `Contract Start & End Date` are the contract dates in `General Information` of the project.
- `Delivery Owner` is the person chosen as `Project Owner` in `General Information`. Only the column title on the list changed; the form still calls the field `Project Owner`.
- `Project Manager` lists every member with the role `Project Manager` in `Team Members` of the project. A project can show more than one name. A project manager saved on older projects before `Team Members` existed is also shown. A dash means no team member has that role yet.
- `Progress` is the same number as `Overall Project Progress` in `Project Planning`. It stays 0% until the project has a planning with phase weights.
- `Method` is the `Delivery Method` (`Onsite`, `Hybrid` or `WFH`). A dash means it was not filled.
- Filtering `Project Manager` on one name keeps every project where that person is one of the project managers.
- The `Project Manager` column of the `Project List` export follows the same rule as the list.

### When you export

- The fields you can tick are the columns of the full `Project List` export.
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

A new project appears in the list and in the reports that read projects, such as `Collection Outlook`. A closed project is counted under `Closed` in the cards.

## Common questions

**There is no `Add New` button.** Your role does not have `Add New Project`. Ask your administrator. The `Import Excel` button needs the same permission.

**I cannot change anything on a project.** It is probably closed. Reopen it first if you are allowed to.

**`Project Manager` shows a dash.** Nobody in `Team Members` of that project has the role `Project Manager`. Add or change a member on the project detail page.

**`Progress` shows 0% although work has started.** The progress is read from `Project Planning`. Check that the planning has visible phases with weights and that activities have progress filled in.

<!-- sources: routes/web.php, app/Http/Controllers/DeliveryProjectController.php (index, export, projectManagerNames), app/Models/DeliveryProject.php (calculateOverallProgress), resources/views/delivery/project/projects/index.blade.php, resources/views/delivery/project/projects/create.blade.php -->
