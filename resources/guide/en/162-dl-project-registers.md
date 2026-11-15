---
key: dl-project-registers
chapter: delivery
order: 12
title: Project registers: Stakeholders, Issues, Risks and WRICEF
summary: Keep the stakeholder register, issue log, risk register and WRICEF log of a project, and see all issues across projects.
requires: [delivery-project.stakeholder.view, delivery-project.issue-log.view, delivery-project.risk.view, delivery-project.wricef.view]
updated: 2026-11-02
---
Four registers on the project detail page record what could go wrong and what must be built. They are the project's working memory: keep them current before each status meeting.

## Who uses this

Project heads, project administrators and the project team. Each register has its own `view`, `edit` and `create or delete` permission in Menu Access.

## Before you start

- Some screens in these registers are written in Indonesian. Column and button names are the same as described here.
- IDs are generated for you (`SH-001`, `ISS-001`, `RSK-001`). You do not type them.

## Step by step

### Issue Log

![The Project Issue Log with the issue, priority, status and owner](images/guide/dlv-project-issues.png)

*Screenshot with sample data.*

1. On the project detail page choose the `Issues` tab and `Add Issue`.
2. Describe the issue and fill the module, the date identified, the priority (`H`, `M` or `L`), the owner, the estimated closing date and whether escalation is needed (`Y` or `N`). Add the impact and tracking comments as you go.
3. When it is solved, set the status and the closed date.
4. To see the issues of all projects together, open `Delivery` › `Project` and the `Issues` tab: each row shows the customer, ID, description, priority, status, owner, project status and date.

### Risk Register

![The Project Risk Register with type, category, probability, impact and score](images/guide/dlv-project-risks.png)

*Screenshot with sample data.*

1. Choose the `Risks` tab and `Add Risk`.
2. Fill the risk type (`Threat` or `Opportunity`), the category, the description, the cause and the project impact. Give the probability `P` and impact `I` from 1 to 5.
3. The `Score` is `P × I`. A score of 12 or more is `High`, 5 to 11 is `Medium` and 4 or less is `Low`. Pick the response strategy, the mitigation plan, the owner and the target date.
4. `Risk Dashboard` summarises the register. A risk can be linked from an issue through its project risk ID.

### Stakeholder Register

1. Choose the `Stakeholders` tab and `Add Stakeholder`.
2. Fill the name, role, organization, category and the `Power` and `Interest` levels. The power-interest quadrant is worked out for you.
3. Record the current and expected attitude, the expectations, the engagement strategy, how often and how to communicate, and the internal PIC.

### WRICEF Log

![The WRICEF Log with SAP module, category, object ID and status](images/guide/dlv-project-wricef.png)

*Screenshot with sample data.*

1. Choose the `WRICEF` tab and `Add WRICEF` for each workflow, report, interface, conversion, enhancement or form.
2. Fill the SAP module, the category, the name, the priority, the requestor, the effort in mandays and the approval. The object ID is built from the module and the category, for example `MM` plus `Report` gives `MMR001`.
3. Track the three stages one after another: `FSD`, `Development` and `Testing`, each with a PIC, dates, status and remarks. The footer shows the total mandays.

## What happens next

The registers update the project at once. Open issues and risks are what your project head and the customer review in status meetings.

## Common questions

**I cannot type the ID.** It is created automatically in order.

**The risk level looks wrong.** It follows the score. Change `P` or `I` and the level changes.

<!-- sources: resources/views/delivery/project/projects/show.blade.php, resources/views/delivery/project/issues/index.blade.php -->
