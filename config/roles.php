<?php

/*
 * Role names the application itself needs to know about. Everything else — who may open or do what — comes from the permissions
 * ticked per role in Management → Roles; no code compares a role ID. Names, not IDs: IDs differ between databases.
 */
return [
    // Roles every newly created employee receives (the account cannot log in without them). Extend or rename here, or via env.
    'new_employee_roles' => array_filter(array_map('trim', explode('|', (string) env('NEW_EMPLOYEE_ROLES', 'User System Registered|EC User')))),

    // The role that owns the system: seeded with every new permission by migrations (App\Support\MenuRegistrar).
    'admin_role_name' => env('ADMIN_ROLE_NAME', 'EC Administrator'),
];
