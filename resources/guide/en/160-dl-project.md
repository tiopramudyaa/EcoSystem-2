---
key: dl-project
chapter: delivery
order: 10
title: Project
summary: Find delivery projects, filter them with the summary cards, add a new project, export the list and close or delete a project.
requires: [delivery.project, delivery-project.add-new, delivery-project.close-project, delivery-project.delete-project]
updated: 2026-11-02
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
3. In the table, each project shows its `Last Update`, `IO Number`, `Customer`, `Project Type`, `Project Name`, `AE`, `Project Owner`, `Category` and `Project Status`. The most recently updated project is on top. Use the arrows in a column title to sort and the funnel icon to filter.
4. Click a project to open its detail page (see the topic about the project detail).
5. Choose `Export Excel` to download the list.

### Add a project

![The New Delivery Project Form with the required fields marked](images/guide/dlv-project-create.png)

*Screenshot with sample data.*

1. Choose `Add New`.
2. Fill the required fields marked with a red star: `Customer/Client`, `Project Owner`, `Project Type`, `Contract Start Date`, `Contract End Date`, `IO/Number Order`, `Project Name` and `Description`. `High Level Risk` is optional.
3. `Category`, `Phase` and `Go Live Estimated` cannot be typed. They are filled in automatically from the project planning once it exists.
4. The same form also holds the sales data (`Delivery Information`), `Delivery Data`, the first `Team Members` and the `Location Information`. Fill what you know now, the rest can be added later on the project detail page.
5. Submit the form. The new project then appears in the list.

### Close, reopen or delete a project

On the project detail page:

- `Close Project` locks the project: its data can no longer be changed. The button then becomes `Reopen Project` for people who may use it.
- `Delete Project` removes the project for good. If the project still has related records, EcoSystem refuses and tells you it may still have related records. Close the project instead when work has ended.

## What happens next

A new project appears in the list and in the reports that read projects, such as `Collection Outlook`. A closed project is counted under `Closed` in the cards.

## Common questions

**There is no `Add New` button.** Your role does not have `Add New Project`. Ask your administrator.

**I cannot change anything on a project.** It is probably closed. Reopen it first if you are allowed to.

<!-- sources: routes/web.php, resources/views/delivery/project/projects/index.blade.php, resources/views/delivery/project/projects/create.blade.php -->
