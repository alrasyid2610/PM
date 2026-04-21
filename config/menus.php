<?php

/**
 * Konfigurasi menu aplikasi.
 * Satu-satunya tempat untuk tambah/ubah/hapus menu.
 * Digunakan oleh: sidebar, permission matrix (form.js via /api/menus), middleware.
 *
 * Struktur item:
 *   slug    — identifier unik, dipakai di user_menu_permissions & route prefix
 *   label   — teks yang tampil di UI
 *   section — (opsional) sub-header di dalam group sidebar
 *
 * Struktur group:
 *   type    — 'submenu' (punya sub-items) | 'direct' (link langsung, tanpa submenu)
 */

return [
    [
        'group'          => 'Dashboard',
        'icon'           => 'fa-gauge',
        'type'           => 'direct',
        'show_in_sidebar' => false,
        'items'          => [
            ['slug' => 'dashboard', 'label' => 'Dashboard'],
        ],
    ],
    [
        'group'   => 'Master Data',
        'icon'    => 'fa-database',
        'type'    => 'submenu',
        'divider' => 'Main Menu',
        'items'   => [
            ['slug' => 'business-relations',               'label' => 'Business Relation',           'section' => 'Business Relation'],
            ['slug' => 'business-relation-contacts',       'label' => 'Business Relation Contacts',  'section' => 'Business Relation'],
            ['slug' => 'business-estates',                 'label' => 'Business Estate',             'section' => 'Location'],
            ['slug' => 'commercial-buildings',             'label' => 'Commercial Buildings',        'section' => 'Location'],
            ['slug' => 'testing-units',                    'label' => 'Testing Units',               'section' => 'Product'],
            ['slug' => 'testing-parameters',               'label' => 'Testing Parameters',          'section' => 'Product'],
            ['slug' => 'testing-kelompok-matriks-samples', 'label' => 'Kelompok Matriks Sample',     'section' => 'Product'],
            ['slug' => 'testing-matriks-samples',          'label' => 'Matriks Sample',              'section' => 'Product'],
            ['slug' => 'testing-standards',                'label' => 'Testing Standards',           'section' => 'Product'],
            ['slug' => 'testing-points',                   'label' => 'Testing Points',              'section' => 'Product'],
        ],
    ],
    [
        'group'   => 'Transaksi',
        'icon'    => 'fa-file-invoice',
        'type'    => 'submenu',
        'items'   => [
            ['slug' => 'sales-orders', 'label' => 'Sales Order'],
            ['slug' => 'work-orders',  'label' => 'Work Order'],
            ['slug' => 'boq',          'label' => 'BOQ'],
        ],
    ],
    [
        'group'   => 'System',
        'icon'    => 'fa-gear',
        'type'    => 'submenu',
        'divider' => 'System',
        'items'   => [
            ['slug' => 'menu-groups', 'label' => 'Grup Menu'],
            ['slug' => 'users',       'label' => 'User Management'],
        ],
    ],
];
