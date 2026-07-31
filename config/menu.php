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
|   permission Optional ability string checked against the authenticated user.
|              Null or absent means always visible.
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
        'label'  => 'Dashboard',
        'icon'   => 'ti ti-layout-dashboard',
        'route'  => 'dashboard',
        'active' => 'dashboard',
    ],

    [
        'caption' => 'Management',
    ],

    [
        'label'      => 'Shop Management',
        'icon'       => 'ti ti-building-store',
        'route'      => 'shops.index',
        'active'     => 'shops.*',
        'permission' => null,
    ],

    [
        'label'      => 'Staff Management',
        'icon'       => 'ti ti-users',
        'route'      => 'staff.index',
        'active'     => 'staff.*',
        'permission' => null,
    ],

    [
        'label'      => 'Lead Management',
        'icon'       => 'ti ti-target-arrow',
        'route'      => 'leads.index',
        'active'     => 'leads.*',
        'permission' => null,
    ],

    [
        'label'      => 'Task Management',
        'icon'       => 'ti ti-checklist',
        'route'      => 'tasks.index',
        'active'     => 'tasks.*',
        'permission' => null,
    ],

    [
        'caption' => 'Account',
    ],

    [
        'label'  => 'Profile',
        'icon'   => 'ti ti-user-circle',
        'route'  => 'profile.index',
        'active' => 'profile.*',
    ],

    [
        'label'      => 'Settings',
        'icon'       => 'ti ti-settings',
        'route'      => 'settings.index',
        'active'     => 'settings.*',
        'permission' => null,
    ],

];
