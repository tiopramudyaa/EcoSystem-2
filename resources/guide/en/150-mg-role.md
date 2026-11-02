---
key: mg-role
chapter: management
order: 10
title: Role
summary: Create and edit roles (Role Management), see who holds each role, and decide which menus a role can open.
requires: [management.roles]
updated: 2026-11-02
---
`Management` › `Role` (the page is called Role Management) is where the roles of EcoSystem live. A role is a set of menus: everyone who holds the role gets exactly those menus.

## Who uses this

Administrators, and any role given the `Management` › `Role` menu in Menu Access.

> [!IMPORTANT]
> Menu access decides who can see customer, employee and financial data. Change it only when you know why, and check the result afterwards.

## Before you start

- A role cannot be deleted while any employee still holds it. Move those people to another role first.
- Granting a menu gives view access. Buttons inside a page (create, approve, export) are separate `function` menus under the page, shown with a lightning icon in `Menu Access`.
- To give a person a role, use `Master` › `Employee`. Roles are not assigned on this page.

## Step by step

![The Role Management list with Menu Access, Edit and Delete on every role](images/guide/mgmt-roles.png)

*Screenshot with sample data.*

### Add or edit a role

1. Open `Management` › `Role`. Use the search box to find a role by name or description.
2. Choose `Add Role`, type a `Name` (required, must be new) and an optional description, then `Save`. To change an existing role, choose `Edit`.
3. Read the `Members` badge: it shows how many people hold the role. Click it to see their names.

### Choose the menus of a role

![The Menu Access window of a role with a tick box for every menu](images/guide/mgmt-role-menu-access.png)

*Screenshot with sample data.*

1. On the role, choose `Menu Access`. The window lists every menu as a tree. Use the search box to find one.
2. Tick a menu to grant it. EcoSystem asks `Grant Access?`: choose `Yes, Grant`. If the menu has sub-items, all of them are granted too.
3. Untick a menu to take it away. EcoSystem asks `Revoke Access?`: choose `Yes, Revoke`. Its sub-items are removed as well.
4. A sub-item is greyed out while its parent menu is off. Turn the parent on first.
5. Each tick is saved at once. There is no separate save button.

### Delete a role

Choose `Delete` on a role and confirm. If people still hold it, EcoSystem refuses and tells you how many.

## What happens next

People with the role get the new menus the next time they open a page. Every change is recorded in `Control Center` › `Audit Log` under the module `Role Management`.

## Common questions

**A person cannot see a menu I granted.** Ask them to reload. Also check that the parent menu is on, and that the person really holds this role in `Master` › `Employee`.

**I need the exact menu name or slug.** Open `Management` › `Menu Access`, which lists them all.

<!-- sources: resources/views/management/roles/index.blade.php, app/Http/Controllers/RoleController.php -->
