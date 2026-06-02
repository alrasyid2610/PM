<?php

/**
 * Konfigurasi menu aplikasi.
 * Satu-satunya tempat untuk tambah/ubah/hapus menu.
 * Digunakan oleh: sidebar, permission matrix (form.js via /api/menus), middleware.
 *
 * Struktur item:
 *   slug    — identifier unik, dipakai di user_menu_permissions & route prefix
 *   label   — teks yang tampil di UI
 *   icon    — FontAwesome class (fa-xxx) untuk ikon item
 *   section — (opsional) sub-header di dalam group sidebar
 *
 * Struktur group:
 *   type    — 'submenu' (punya sub-items) | 'direct' (link langsung, tanpa submenu)
 */

return [
    [
        'group'           => 'Dashboard',
        'icon'            => 'fa-gauge',
        'type'            => 'direct',
        'show_in_sidebar' => false,
        'items'           => [
            ['slug' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge'],
        ],
    ],
    [
        'group'   => 'Master Data',
        'icon'    => 'fa-database',
        'type'    => 'submenu',
        'divider' => 'Main Menu',
        'items'   => [
            ['slug' => 'business-relations',               'label' => 'Business Relation',          'icon' => 'fa-handshake',       'section' => 'Business Relation'],
            ['slug' => 'business-relation-contacts',       'label' => 'Business Relation Contacts', 'icon' => 'fa-address-book',    'section' => 'Business Relation'],
            ['slug' => 'business-estates',                 'label' => 'Business Estate',            'icon' => 'fa-map-location-dot', 'section' => 'Location'],
            ['slug' => 'commercial-buildings',             'label' => 'Commercial Buildings',       'icon' => 'fa-city',            'section' => 'Location'],
            ['slug' => 'contracts',                        'label' => 'Contracts',                  'icon' => 'fa-file-contract',   'section' => 'Product'],
            ['slug' => 'testing-units',                    'label' => 'Testing Units',              'icon' => 'fa-ruler',           'section' => 'Product'],
            ['slug' => 'testing-parameters',               'label' => 'Testing Parameters',         'icon' => 'fa-flask',           'section' => 'Product'],
            ['slug' => 'testing-kelompok-matriks-samples', 'label' => 'Kelompok Matriks Sample',    'icon' => 'fa-object-group',    'section' => 'Product'],
            ['slug' => 'testing-matriks-samples',          'label' => 'Matriks Sample',             'icon' => 'fa-table-cells',     'section' => 'Product'],
            ['slug' => 'testing-standards',                'label' => 'Testing Standards',          'icon' => 'fa-certificate',     'section' => 'Product'],
            ['slug' => 'testing-points',                   'label' => 'Testing Points',             'icon' => 'fa-location-dot',    'section' => 'Product'],
        ],
    ],
    [
        'group'   => 'Transaksi',
        'icon'    => 'fa-file-invoice',
        'type'    => 'submenu',
        'items'   => [
            ['slug' => 'sales-orders', 'label' => 'Sales Order', 'icon' => 'fa-file-invoice-dollar'],
            ['slug' => 'work-orders',  'label' => 'Work Order',  'icon' => 'fa-briefcase'],
            ['slug' => 'boq',          'label' => 'BOQ',         'icon' => 'fa-layer-group'],
            ['slug' => 'fieldworks',   'label' => 'Fieldworks',  'icon' => 'fa-helmet-safety'],
            ['slug' => 'termin',       'label' => 'Termin'],
            // ['slug' => 'output-pekerjaan',   'label' => 'output pekerjaan',  'icon' => 'fa-helmet-safety'],
        ],
    ],
    [
        'group'   => 'System',
        'icon'    => 'fa-gear',
        'type'    => 'submenu',
        'divider' => 'System',
        'items'   => [
            ['slug' => 'menu-groups', 'label' => 'Grup Menu',        'icon' => 'fa-sitemap'],
            ['slug' => 'users',       'label' => 'User Management',  'icon' => 'fa-users-gear'],
        ],
    ],
];
