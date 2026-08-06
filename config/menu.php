<?php

/*
|--------------------------------------------------------------------------
| Sidebar Navigation
|--------------------------------------------------------------------------
|
| Single source of truth for the admin sidebar. The <x-sidebar /> component
| renders this array, so adding a module means adding an entry here — never
| editing markup.
|
| Item keys:
|   caption    Renders a section heading instead of a link.
|   label      Visible menu text.
|   icon       Tabler icon class (https://tabler.io/icons).
|   route      Laravel named route. Resolved with route().
|   active     Route pattern for highlighting. Accepts wildcards, so
|              'shops.*' keeps the parent lit on shops.create, shops.edit, etc.
|   permission Optional ability checked against the authenticated user, from
|              config/permissions.php. A string requires that one ability; an
|              array requires ANY of them (used by captions so a heading hides
|              when the role holds none of its items). Null means always
|              visible.
|   children   Optional array of sub-items. Presence makes the item
|              collapsible; the component handles the rest.
|
| Collapsible example — drop this in place of a flat item:
|
|   [
|       'label'    => 'Shop Management',
|       'icon'     => 'ti ti-building-store',
|       'active'   => 'shops.*',
|       'children' => [
|           ['label' => 'All Shops',  'route' => 'shops.index',  'active' => 'shops.index'],
|           ['label' => 'Add Shop',   'route' => 'shops.create', 'active' => 'shops.create'],
|       ],
|   ],
|
*/

return [

    [
        'label'      => 'Dashboard',
        'icon'       => 'ti ti-layout-dashboard',
        'route'      => 'dashboard',
        'active'     => 'dashboard',
        'permission' => 'dashboard.view',
    ],

    [
        'caption'    => 'Management',
        // Hidden entirely when the role holds none of the items beneath it,
        // so a Manager never sees an empty "Management" heading.
        'permission' => ['shops.manage', 'staff.view', 'staff.manage', 'leads.access', 'tasks.manage'],
    ],

    [
        'label'      => 'Shop Management',
        'icon'       => 'ti ti-building-store',
        'route'      => 'shops.index',
        'active'     => 'shops.*',
        'permission' => 'shops.manage',
    ],

    [
        'label'      => 'Staff Management',
        'icon'       => 'ti ti-users',
        'route'      => 'staff.index',
        'active'     => 'staff.*',
        'permission' => 'staff.manage',
    ],

    [
        'label' => 'Lead Management',
        'icon' => 'ti ti-target-arrow',
        'route' => 'leads.index',
        'active' => 'leads.*',

        // leads.access, not leads.manage: Admins and Managers qualify on
        // their role, while an Employee qualifies only with the per-user
        // lead_module_access flag. Turning the flag off hides the menu.
        'permission' => 'leads.access',
    ],

    [
        'label'      => 'Task Management',
        'icon'       => 'ti ti-checklist',
        'route'      => 'tasks.index',
        'active'     => 'tasks.*',
        'permission' => 'tasks.manage',
    ],

    [
        'label'      => 'Reports',
        'icon'       => 'ti ti-chart-bar',
        'route'      => 'reports.index',
        'active'     => 'reports.*',
        'permission' => 'reports.view',
    ],

    [
        'caption' => 'Account',
    ],

    [
        'label'      => 'Profile',
        'icon'       => 'ti ti-user-circle',
        'route'      => 'profile.index',
        'active'     => 'profile.*',
        'permission' => 'profile.view',
    ],

    [
        'label'      => 'Settings',
        'icon'       => 'ti ti-settings',
        'route'      => 'settings.index',
        'active'     => 'settings.*',
        'permission' => 'settings.manage',
    ],

];
