---
key: calendar-events
chapter: my-workspace
order: 114
title: Events
summary: See events on the calendar and add meetings, tasks, deadlines and reminders.
requires: [calendar.events]
updated: 2026-11-11
---
`Calendar` › `Events` shows the events you may see and lets you add new ones.

## Who uses this

Employees who have `Events` under `Calendar`.

## Before you start

- Know the title, the type, the date and the start time of the event.
- The `Create Event` button appears only if your role allows it. If it is missing, ask your administrator.

## Step by step

### Read the calendar

![The Events Calendar page for October with Today, month navigation and the Month, Week and Day views](images/guide/calendar-events.png)

*Screenshot with sample data.*

1. Open `Calendar` › `Events`. The page is titled `Events Calendar`.
2. Use the arrows to change month, or `Today` to return to the current date.
3. Switch between `Month`, `Week` and `Day`.
4. Select an event to read its details.

### Create an event

![The Create New Event form with title, type, description, dates, times and location](images/guide/calendar-create-event.png)

*Screenshot with sample data.*

1. Select `Create Event`. The form is titled `Create New Event`.
2. Enter the `Event Title` and choose the `Event Type`: `Meeting`, `Task`, `Deadline`, `Urgent` or `Reminder`.
3. Enter the `Start Date` and `Start Time`. Add the `End Date` and `End Time` if needed, or tick `All day event`.
4. Add a `Description` and a `Location` if useful.
5. Select `Save Event`.

## Rules to know

These rules are checked when you save. If one is broken, nothing is saved.

- Creating an event needs the `Create Event` function in `Menu Access`. Without it the request is refused, even if it is sent directly.
- `Event Title`, `Event Type`, `Start Date` and `Start Time` are required.
- The `Event Type` must be `Meeting`, `Task`, `Deadline`, `Urgent` or `Reminder`.
- The `End Date` cannot be earlier than the `Start Date`.
- The title and the location can each be at most 255 characters.

## What happens next

The new event is saved and shows on the calendar. Select it to read its details.

## Common questions

**I cannot see `Create Event`.** Your role does not have that function. Ask your administrator to enable it in `Menu Access`.

**Where do I log my working hours?** In `Calendar` › `Timesheets`.

<!-- sources: routes/web.php, app/Http/Controllers/CalendarController.php, resources/views/calendar/events.blade.php -->
