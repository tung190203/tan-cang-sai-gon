<?php

return [
    'name' => 'Bản đồ hệ thống cảng',
    'slug' => 'port-map',
    'view_namespace' => 'tenant-tan-cang-port-map',
    'menu' => [
        'title' => 'Bản đồ cảng',
        'icon' => 'fas fa-map-marked-alt',
        'route' => 'tenant.tan_cang.port_map.index',
        'section' => 'content',
        'sort_order' => 20,
    ],
    'permissions' => ['view'],
];
