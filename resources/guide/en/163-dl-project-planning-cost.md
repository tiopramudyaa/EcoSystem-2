---
key: dl-project-planning-cost
chapter: delivery
order: 13
title: Project planning and Plan Cost
summary: Plan phases and activities of a project, follow its progress, and keep the budget, release and actual cost.
requires: [delivery-project.planning.view, delivery-project.plan-cost.view]
updated: 2026-11-02
---
The `Planning` and `Plan Cost` sections of a project answer two questions: when will the work be done, and what does it cost. Both feed the progress and the profit shown elsewhere.

## Who uses this

Project heads, project administrators and project managers. Each section has its own `view`, `edit` and `create or delete` permission in Menu Access.

## Before you start

- Planning sets the project `Category`, `Phase` and `Go Live Estimated`. Until planning exists those stay empty.
- A closed project cannot be planned or costed. Reopen it first.

## Step by step

### Planning

1. On the project detail page choose the `Planning` tab. The section `Project Planning` shows the phases, groups, stages and activities with their weight, description, object, start, end, days, status and progress.
2. Switch between `Table View` and `Gantt Chart`. Use `Expand All`, `Collapse All` and `Refresh` to read the tree.
3. To build or change the plan, choose `Manage Full Planning`. Create the phases first, then the groups, stages and activities under them, and give each a weight, dates and status.
4. The activity marked as `Go-Live` sets the project `Go Live Estimated`. Progress of the project is calculated from the weights.
5. To compare all projects, open `Delivery` › `Project` and the `Planning` tab. The `Project Planning List` shows each project with progress, status, budget and creation date, and can be filtered by status.

![The Project Planning List with progress, status and budget of every project](images/guide/dlv-project-planning-list.png)

*Screenshot with sample data.*

### Plan Cost

![The Plan Cost section with total budget, release and actual and the cost table](images/guide/dlv-project-plan-cost.png)

*Screenshot with sample data.*

1. Choose the `Plan Cost` tab. The five cards show `TOTAL BUDGET`, `TOTAL RELEASE`, `TOTAL ACTUAL`, `AVAIL. BUDGET` and `AVAIL. RELEASE`.
2. Choose `Add Cost Item` to add a cost line with a code, a name, the budget and the cost type (indirect or direct). Use `Add sub-item` under a line to break it down, with an amount, a period and a document if you have one.
3. Read the table: `Avail. Budget` is the budget minus the release, and `Avail. Release` is the release minus the actual.
4. Choose `Export Excel` to download the recap.

## What happens next

Progress, go-live and the budget figures update in the project and in the lists. The plan cost also feeds the gross profit in `Delivery Information`.

## Common questions

**The phase and category are empty.** Planning has not been created yet.

**The progress does not move.** Progress follows activity status and weight. Check that the activities have a weight.

<!-- sources: resources/views/delivery/project/projects/show.blade.php, resources/views/delivery/project/project-planning/index.blade.php -->
