---
key: mg-hidden-tickets
chapter: management
order: 13
title: Hidden Tickets
summary: Find tickets that were hidden from the main list and show them again.
requires: [management.hidden-tickets]
updated: 2026-11-02
---
A ticket can be hidden from the main `Ticket` list, for example a duplicate or a test ticket. `Management` › `Hidden Tickets` lists them so nothing is lost and you can bring one back.

## Who uses this

Helpdesk and administrators, and any role given the `Management` › `Hidden Tickets` menu in Menu Access.

## Before you start

- A hidden ticket is also left out of the reports, such as `Ticketing Overview`, `Resolution Days` and `Log Shifting`. Showing it again puts it back in the figures.
- This page and its screens are partly in Indonesian: `Tiket` means ticket and `Tampilkan` means show.

## Step by step

![The Hidden Tickets list with the count of hidden tickets and a button to show each one again](images/guide/mgmt-hidden-tickets.png)

*Screenshot with sample data.*

1. Open `Management` › `Hidden Tickets`. The orange badge counts the hidden tickets.
2. Read each row: `Tiket #`, `Deskripsi`, `Customer`, `Ticket Lead`, `Priority`, `Status` and `Tgl Dibuat` (created on).
3. Use the search box (`Cari nomor tiket atau deskripsi...`) to find one ticket by number or text.
4. To bring a ticket back, choose `Tampilkan` on its row and confirm `Tampilkan Kembali`.

To hide a ticket in the first place, open it and choose `Sembunyikan Tiket`. This needs the `ticket.hide` permission. On a hidden ticket the same place shows `Tampilkan Kembali`.

## What happens next

The ticket returns to the main `Ticket` list and to the reports, with all its history intact.

## Common questions

**I cannot find a ticket in the main list.** It may be hidden. Search for its number here.

**Is a hidden ticket deleted?** No. Nothing is removed. It is only left out of the lists and reports.

<!-- sources: resources/views/management/hidden-tickets/index.blade.php, resources/views/ticket/show.blade.php -->
