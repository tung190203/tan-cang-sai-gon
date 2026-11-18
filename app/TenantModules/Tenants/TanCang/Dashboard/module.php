<?php

return [
    'name' => 'Dashboard',
    'slug' => 'dashboard',
    'view_namespace' => 'tenant-tan-cang-dashboard',
    'menu' => [
        'title' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'route' => 'tenant.tan_cang.dashboard.index',
        'section' => 'content',
        'sort_order' => 10,
    ],
    'permissions' => ['view'],
];
