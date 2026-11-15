---
key: mg-menu-access
chapter: management
order: 11
title: Menu Access
summary: Browse the full list of menus and their sub-items with the exact name and slug of each.
requires: [management.permissions]
updated: 2026-11-02
---
`Management` › `Menu Access` is the reference list of everything that can be switched on or off for a role: every group, page and button-level `function`, with its slug. You read it here and grant it in `Management` › `Role`.

## Who uses this

Administrators, and any role given the `Management` › `Menu Access` menu in Menu Access.

## Before you start

- This page only shows the list. Nothing you do here changes anyone's access.
- To grant or revoke, use `Menu Access` on a role in `Management` › `Role`.

## Step by step

![The Menu List page with the full menu tree and the slug of every item](images/guide/mgmt-menu-access.png)

*Screenshot with sample data.*

1. Open `Management` › `Menu Access`.
2. Read the tree. A purple stack icon is a group (such as `Calendar` or `Reporting`), a blue page icon is a page, and a lightning icon is a function: a button or action inside a page, for example `Create Event`.
3. The grey text at the right of each row is its slug, a short dotted name. Use it when you report a missing menu to an administrator or look it up in a topic.
4. Click the arrow of a row, or `Expand All` and `Collapse All`, to show or hide sub-items.
5. Type in `Search menu name...` to find a menu. Choose `Reset` to clear the search.

## What happens next

Nothing is changed. Go to `Management` › `Role` to grant a menu you found.

## Common questions

**A page I know is not in the list.** The list shows active menus only. Ask the technical team if a page should exist.

**Why do some functions sit under a page?** They are the buttons of that page. A role can open the page without being allowed to use them.

<!-- sources: resources/views/management/permissions/index.blade.php -->
