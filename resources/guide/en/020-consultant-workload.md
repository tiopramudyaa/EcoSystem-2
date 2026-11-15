---
key: consultant-workload
chapter: work-service
order: 20
title: Consultant Workload
summary: See how loaded each consultant is, open a consultant to see their tickets, and export the list to Excel.
requires: [ticket.consultant-workload]
updated: 2026-11-12
---
`Consultant Workload` shows every consultant with the work they carry: how many tickets, how many mandays are allocated, how many are left, and a load score. Use it to decide who has room for a new ticket and who is overloaded.

## Who uses this

Heads, managers and anyone given the `Consultant Workload` menu in Menu Access, for example to plan who takes the next ticket.

## Before you start

- The list is built from tickets and their Consultant Mandays. A consultant with no allocation shows no workload figures.
- Only active employees with the role Delivery Support User and the position SAP CONSULTANT are listed.

## Step by step

### Read the list

![Consultant Workload with a filter bar and one line per consultant](images/guide/consultant-workload-list.png)

*Screenshot with sample data.*

1. Open `Consultant Workload` in the sidebar. The consultants with the highest workload percentage come first.
2. Each line shows the consultant, `Personnel Sub Area`, `Current Assignment`, `Module` (the modules of their qualifications), the number of `Tickets`, `Alloc Days`, `Remain`, `Workload` and `Load Score`.
3. Read the colours: the `Workload` bar is red from 70%, yellow from 40% and green below that. `Load Score` is red from 10, yellow from 5 and green below that.
4. The small orange badge next to `Remain`, for example `↑3 d`, is the remaining days rounded up to whole days.

### Filter and sort

1. Use `Group Modul` at the top right to show the consultants of one module group.
2. In the header, filter by `Consultant` (name or ECI), `Personnel Sub Area` and `Module`.
3. Select the title of `Tickets`, `Alloc Days`, `Remain`, `Workload` or `Load Score` to sort. Select it again to reverse the order.
4. `Expand All` opens every consultant and `Collapse All` closes them.

### See the tickets of a consultant

![All consultants opened, each with a table of their tickets, role, status, priority, allocated and remaining days and progress](images/guide/consultant-workload-expanded.png)

*Screenshot with sample data.*

1. Select a consultant line.
2. The table lists each ticket with the consultant's `Role` (Ticket Lead or Member), `Status`, `Priority`, `Day not Close`, `Alloc Days`, `Add. Days`, `Remain` and `Progress`, and a total line.
3. To update a progress, select the pencil next to the progress bar. The pencil shows on every line, but you can save only your own progress, or any consultant on a ticket where you are the Ticket Lead.
4. Select `Export Excel` to download that consultant's tickets as a spreadsheet.

## Rules to know

- The list covers tickets with the status Open, In Process, Hold, Waiting on Customer, Waiting on 3rd Party or Waiting to Confirmation. Hidden and deleted tickets are left out. This includes tickets that are still `Open`, so the numbers can be higher than in `My Tasks`.
- `Alloc Days` are the approved mandays of the consultant on the ticket. Until the Head approves a proposal, the proposed mandays are used, so the figures follow the latest proposal.
- `Remain` is the allocated days (with approved additional days) times (100% minus the consultant's progress on that ticket). `Workload` is remain divided by allocated. `Load Score` is remain times (1 plus 0.1 times the number of tickets).
- The progress of one ticket is the average of the progress of all consultants on it. A progress is 0 to 100, and a note is at most 500 characters.
- You can change only your own progress, unless you are the Ticket Lead of that ticket. Then you can change every consultant on it. Saving anything else is refused with a message that you can change only your own progress.
- Changes of progress are written to the Audit Log. `Export Excel` produces the same figures you see on screen for that consultant.
- `Day not Close` counts the days since the ticket was created.

## What happens next

Use the load figures when you assign the next ticket. The consultants update their own progress in `My Tasks`.

## Common questions

**A consultant is missing from the list.** Only active employees with the role Delivery Support User and the position SAP CONSULTANT are listed. Ask HR to check the position and the role.

**A consultant shows 0 days.** No mandays are allocated to that consultant on an active ticket.

**The page shows the current month only.** The list always reflects the tickets that are active now, not a past month.

**I get a message that I can change only my own progress.** The pencil shows on every line, but only the consultant and the Ticket Lead of that ticket can save a change.

**The numbers differ from `My Tasks`.** `My Tasks` leaves out tickets that are still `Open`. This page includes them.

<!-- sources: routes/web.php, routes/api.php, resources/views/ticket/consultant-workload.blade.php, app/Http/Controllers/ConsultantWorkloadController.php -->
