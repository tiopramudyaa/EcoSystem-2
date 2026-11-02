---
key: dl-support-work
chapter: delivery
order: 22
title: Support work: Activities, SLA, Plan Cost and Recons
summary: Plan the activities of a support, set its SLA policies and cost plan, and group closed tickets into recons batches.
requires: [delivery-support.activities.view, delivery-support.sla.view, delivery-support.plan-cost.view, delivery-support.recons.view]
updated: 2026-11-02
---
The lower sections of the support detail page cover the day-to-day running of a support: its activities and planning, its SLA targets, its cost plan and the reconciliation of closed tickets.

## Who uses this

Support managers, helpdesk, support heads and project administrators. Each section has its own `view`, `edit` and `create or delete` permission in Menu Access.

## Before you start

- SLA policies decide the response and resolution targets of the tickets under this support. Set them before tickets arrive.
- Recons only take closed tickets that have a Customer MD and were never reconciled before.

## Step by step

### Activities and planning

![The Support Planning page with phase management and the progress overview](images/guide/dlv-support-planning.png)

*Screenshot with sample data.*

1. In `Activities` read the activities of the support with their progress. Choose `View all` or `Open Planning` for the full page.
2. On the planning page choose `Configure Phases` to create the phases, then add groups, stages and activities with weight, dates and status. Linked tickets appear as activities.
3. Switch between `Table View`, `Gantt Chart` and `S-Curve`, and choose `Export` to download. The overview shows overall progress and progress per phase.

### SLA Configuration

![The SLA Configuration table with the Add Policy button and the Plan Cost section below it](images/guide/dlv-support-sla.png)

*Screenshot with sample data.*

1. Choose the `SLA` tab and `Add Policy`.
2. Pick the `Priority` and the `Scale` (`Simple`, `Medium` or `Complex`). Give the response time, in hours or minutes, and the resolution time in hours.
3. Choose the mode: tick `24/7 Calendar Hours` to count every hour, or leave it off and set `Work Start` and `Work End` so only business hours count.
4. Save. The table lists each policy with its mode and status. Edit or remove a policy from its row.

### Plan Cost

1. Choose the `Plan Cost` tab. The cards show total budget, release, actual and what is still available.
2. A new support starts with two lines, `INDIRECT COST` and `DIRECT COST`. Choose `Add sub-item` under a line, or `Add Cost Item`, to add costs with a budget, a release and an actual.

### Recons

![The Recons section with the Tickets and Recons List views and the New Recons button](images/guide/dlv-support-recons.png)

*Screenshot with sample data.*

1. Choose the `Recons` tab. `Tickets` lists the tickets of the support with their close date, type, Customer MD and recons number. `Recons List` lists the batches.
2. Choose `New Recons`, pick the eligible closed tickets and save. A recons starts as a draft and is later submitted.
3. The close date is filled in when a ticket reaches `Closed`. A ticket already in a recons cannot be added again.

## What happens next

SLA policies apply to the tickets of this support from then on. Recons batches are the basis for the customer reconciliation of mandays.

## Common questions

**A closed ticket is not offered for a recons.** It may have no Customer MD, or it is already in another recons.

**The SLA clock counts nights.** The policy is set to `24/7 Calendar Hours`. Turn it off and set the work window to count business hours only.

<!-- sources: routes/delivery-support.php, resources/views/delivery/support/list/show.blade.php, resources/views/delivery/support/planning/index.blade.php -->
