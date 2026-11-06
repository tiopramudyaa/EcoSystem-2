---
key: sla-config
chapter: sla
order: 10
title: SLA Config
summary: Define the response and resolution targets of a delivery support by priority and scale, and the working hours that count.
requires: [sla.config]
updated: 2026-11-02
---
`SLA` › `SLA Config` is where the service level agreements are set. A policy says how fast tickets of one priority and scale must get a first response and a resolution under one delivery support.

## Who uses this

Helpdesk and support managers, and any role given the `SLA` › `SLA Config` menu in Menu Access. The same policies can also be edited in the `SLA` section of a delivery support (see `Delivery` › `Support`).

## Before you start

- Policies belong to a delivery support. Create the delivery support first.
- A ticket receives the policy of the delivery support it is linked to, with the same priority and scale. A ticket with no matching active policy has no SLA.
- Only active policies apply, and only to new tickets.

## Step by step

![The SLA Configuration page with summary cards and the table of policies](images/guide/sla-config.png)

*Screenshot with sample data.*

1. Open `SLA` › `SLA Config`. The cards show `Total Policies`, `Active`, `Global Defaults` (policies not tied to one delivery support) and `24/7 Policies`.
2. In the table each policy shows its name, the delivery support, the priority (`1: Very High` to `4: Low`), the scale, the response and resolution target in hours, the mode and the status. Use the `All Delivery Supports` list and the funnel icons to filter.
3. To add a policy choose `Add Policy`.

![The Add SLA Policy window with delivery support, priority, scale, response, resolution and working hours](images/guide/sla-config-add.png)

*Screenshot with sample data.*

4. Choose the `Delivery Support`, the `Priority` and the `Scale` (`Simple`, `Medium` or `Complex`). The `Label preview` at the top shows the policy name.
5. Give the `Response` time in hours or minutes and the `Resolution (Hour)` time.
6. Tick `24/7 Calendar Hours` to count every hour. Leave it off to count business hours only, and fill `Work Start` and `Work End`, and the `Break Start` and `Break End` if there is a break.
7. Choose `Save Policy`. Use `Edit` on a row to change a policy, switch `Policy Active` off to stop applying it to new tickets, or delete it after confirming `Delete Policy?`.

## What happens next

New tickets of that delivery support, priority and scale get the targets at once. Tickets already running keep the policy they had. The results are read in `SLA` › `SLA Report`.

## Common questions

**A ticket shows no SLA.** It is not linked to a delivery support, or no active policy exists for its priority and scale.

**The clock counts nights and weekends.** The policy is set to `24/7 Calendar Hours`. Edit it and turn that off.

<!-- sources: routes/web.php, resources/views/admin/sla/config.blade.php, app/Models/SlaPolicy.php -->
