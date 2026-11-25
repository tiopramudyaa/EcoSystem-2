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
2. Read the cards at the top: the total number of menus and how many are groups, pages and functions. Then read the tree. A purple stack icon is a group (such as `Calendar` or `Reporting`), a blue page icon is a page, and a lightning icon is a function: a button or action inside a page, for example `Create Event`. A group also shows how many items it holds.
3. The grey tag at the right of each row is its slug, a short dotted name. Click it to copy it, then paste it when you report a missing menu to an administrator.
4. Click the arrow of a row, or `Expand All` and `Collapse All`, to show or hide sub-items.
5. Type in `Search menu name...` to find a menu. Use the `All`, `Groups`, `Pages` and `Functions` buttons to show only one kind. Choose `Reset` to clear the search and the filter.

## Rules to know

- **Read only.** This page changes nothing. Menus are granted only from `Management` › `Role`.
- **Who may open it.** Only people given the `Management` › `Menu Access` menu in Menu Access. People who can open `Management` › `Role` also receive this same list inside the role window, but not this page.
- **Only active menus are listed.** A menu that has been switched off does not appear.

## What happens next

Nothing is changed. Go to `Management` › `Role` to grant a menu you found.

## Common questions

**A page I know is not in the list.** The list shows active menus only. Ask the technical team if a page should exist.

**Why do some functions sit under a page?** They are the buttons of that page. A role can open the page without being allowed to use them.

<!-- sources: resources/views/management/permissions/index.blade.php -->
