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

1. Open `Management` › `Role`. Three cards at the top show the total roles, the total role assignments, and how many roles have no members. Use the search box to find a role by name or description; the count beside it shows how many of all roles are listed.
2. Choose `Add Role`, type a `Name` (required, must be new) and an optional description, then `Save`. To change an existing role, choose the pencil button on its row (tooltip `Edit role`).
3. Read the `Members` badge: it shows how many people hold the role. Click it to open a window with their names and ECI numbers. When a role has more than 8 members, a search box appears in that window.

### Choose the menus of a role

![The Menu Access window of a role with a switch for every menu](images/guide/mgmt-role-menu-access.png)

*Screenshot with sample data.*

1. On the role, choose `Menu Access`. The window lists every menu as a tree. At the top, a bar shows how many menus the role has been granted. Use the search box to find a menu, the `All`, `Granted` and `Not granted` buttons to narrow the list, and `Expand all` or `Collapse all` to open or close the tree. A group shows a count such as 3/5, the number of its sub-items already granted.
2. Turn a menu switch on to grant it. EcoSystem asks `Grant Access?`: choose `Yes, Grant`. If the menu has sub-items, all of them are granted too.
3. Turn the switch off to take a menu away. EcoSystem asks `Revoke Access?`: choose `Yes, Revoke`. Its sub-items are removed as well.
4. A sub-item is greyed out while its parent menu is off. Turn the parent on first.
5. Each switch is saved at once. There is no separate save button.

### Delete a role

Choose the trash button on the role (tooltip `Delete role`) and confirm. If people still hold it, EcoSystem refuses and tells you how many.

## Rules to know

- **Who may change roles.** Only people given the `Management` › `Role` menu in Menu Access. The system checks this on every change, not only by hiding the menu. Someone without it is refused, even if they know how to send the request directly.
- **Role names.** A name is required, up to 100 characters, and must not be used by another role. The description is optional, up to 255 characters.
- **A role in use cannot be deleted.** If one or more people still hold it, EcoSystem refuses and shows how many. Take the role from them in `Master` › `Employee` first.
- **Granting gives view access only.** The switches in `Menu Access` grant viewing of a page or button. Create, edit and delete rights are not switched on from this window.
- **Sub-items follow the parent.** Turning a menu on also turns on all its sub-items. Turning it off removes them too. A sub-item is greyed out while its parent is off.
- **Several roles add up.** A person holding more than one role gets every menu that any of their roles allows. Revoking a menu from one role does not remove it if another role of theirs still grants it.
- **Inactive menus never open**, even if a role was granted them.
- **Every change is recorded** in `Control Center` › `Audit Log` under `Role Management`, with the value before and after.

## What happens next

People with the role get the new menus the next time they open a page. Every change is recorded in `Control Center` › `Audit Log` under the module `Role Management`.

## Common questions

**A person cannot see a menu I granted.** Ask them to reload. Also check that the parent menu is on, and that the person really holds this role in `Master` › `Employee`.

**I need the exact menu name or slug.** Open `Management` › `Menu Access`, which lists them all.

**How do I close a window without saving?** Choose the X, press `Esc`, or click outside the window. Nothing is changed.

<!-- sources: resources/views/management/roles/index.blade.php, app/Http/Controllers/RoleController.php -->
