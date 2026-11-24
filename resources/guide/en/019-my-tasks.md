---
key: my-tasks
chapter: work-service
order: 19
title: My Tasks
summary: See the active tickets assigned to you, how much work is left on them, and update your progress.
requires: [ticket.my-tasks]
updated: 2026-11-12
---
`My Tasks` lists the active tickets that are assigned to you, with your share of the work on each one. Use it to see what is left and to keep your progress up to date for the team.

## Who uses this

Consultants and ticket leads who have the `My Tasks` menu in Menu Access.

## Before you start

- A ticket appears here only when it is assigned to you, as Ticket Lead, as an active team member, or through a Consultant Mandays allocation. The Ticket topics explain how tickets are assigned and how mandays are proposed.
- The numbers need an allocation of mandays on the ticket. Without it, the ticket is listed but has no workload figures.

## Step by step

### Read your list

![My Tasks with the summary cards and a list of two active tickets](images/guide/task-list.png)

*Screenshot with sample data.*

1. Open `My Tasks` in the sidebar.
2. The five cards at the top sum up your tickets:
   - `Active Tickets`: how many tickets are listed.
   - `Alloc MD`: your allocated mandays on them.
   - `Remaining`: the mandays you still have to do.
   - `Workload`: remaining divided by allocated, as a percentage.
   - `Load Score`: a single number for how heavy your load is (see the rules below).
3. Each row shows the ticket number (with a `Lead` tag when you are the Ticket Lead), `Customer`, `Status`, `Priority`, `Module`, `Man Days`, your `Workload` bar, the `End Date` and the ticket `Progress`.
4. Type in the search box to find a ticket by number, subject, customer or module. Use the arrow next to `Status` to show one status only. Select `Module` or `Workload` in the header to sort.

### See who works on a ticket

![A ticket row expanded to show each consultant with allocated mandays, remaining mandays, progress and notes](images/guide/task-detail.png)

*Screenshot with sample data.*

1. Select the small button with a number at the end of the row. The number is how many consultants work on the ticket.
2. The table shows each consultant's module, `Alloc MD`, `Add. MD`, `Remain`, progress and note, with a total line.

### Update your progress

![The progress window with a slider, a percentage box and a note for the consultant](images/guide/task-edit-progress.png)

*Screenshot with sample data.*

1. In the expanded table, select `Edit` on your own line. A Ticket Lead sees `Edit` on every consultant line.
2. Move the slider or type a percentage from 0 to 100.
3. Add a short note in `Catatan` if you want. The note can have up to 500 characters.
4. Select `Simpan`. Select `Batal` to leave without saving.

When a ticket has no consultant allocation, the Ticket Lead can set the ticket progress directly with the `Edit` button next to the progress percentage.

## Rules to know

- The list shows tickets with the status In Progress, Hold, Waiting on Customer, Waiting on 3rd Party or Waiting to Confirmation. Tickets that are `Open`, closed, hidden or deleted are not listed here.
- The month and year boxes at the top are shown, but the list always shows your current active tickets whichever month you choose.
- Your allocated mandays are the approved mandays plus the approved additional mandays. Before the Head approves a proposal, the proposed numbers are used.
- `Remaining` for a ticket is your allocated mandays times (100% minus your progress). `Workload` is remaining divided by allocated. `Load Score` is remaining times (1 plus 0.1 times the number of tickets), so more tickets make the same remaining work count heavier.
- The progress of a ticket is the simple average of the progress of all consultants on it.
- You can change only your own progress. The Ticket Lead can change the progress of every consultant on that ticket and the ticket progress itself. Anyone else is refused.
- Progress must be between 0 and 100, and a note is at most 500 characters.
- Every change of a consultant's progress is written to the Audit Log with the old and new value.

## What happens next

The ticket progress and your numbers update right after you save. The same figures feed `Consultant Workload`, where the team sees everyone's load.

## Common questions

**My ticket is not in the list.** Check that it is assigned to you, and that its status is not `Open`. A ticket that is still `Open` appears here after it moves to In Progress. Hidden and closed tickets never appear.

**I do not see the `Edit` button.** You can edit only your own line, unless you are the Ticket Lead of that ticket.

**My numbers differ from `Consultant Workload`.** `Consultant Workload` also counts tickets that are still `Open`. `My Tasks` does not list them.

**`Workload` shows a dash.** There is no mandays allocation for you on that ticket yet.

<!-- sources: routes/web.php, routes/api.php, resources/views/ticket/task.blade.php, app/Http/Controllers/TaskController.php, app/Http/Controllers/ConsultantWorkloadController.php, app/Models/Ticket.php -->
