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
        'group'   => 'Transaksi',
        'icon'    => 'fa-file-invoice',
        'type'    => 'submenu',
        'divider' => 'Main Menu',
        'items'   => [
            ['slug' => 'sales-orders', 'label' => 'Sales Order', 'icon' => 'fa-file-invoice-dollar'],
            ['slug' => 'contracts',    'label' => 'Contracts',   'icon' => 'fa-file-contract'],
            ['slug' => 'termin',       'label' => 'Termin',      'icon' => 'fa-money-bill-transfer'],
            ['slug' => 'work-orders',  'label' => 'Work Order',  'icon' => 'fa-briefcase'],
            ['slug' => 'fieldworks',   'label' => 'Fieldworks',  'icon' => 'fa-helmet-safety'],
            // ['slug' => 'boq',              'label' => 'BOQ',              'icon' => 'fa-layer-group'],
            // ['slug' => 'output-pekerjaan', 'label' => 'output pekerjaan', 'icon' => 'fa-helmet-safety'],
        ],
    ],
    [
        'group'   => 'Business Relation',
        'icon'    => 'fa-handshake',
        'type'    => 'submenu',
        'divider' => 'Master Data',
        'items'   => [
            ['slug' => 'business-relations',         'label' => 'Business Relation',         'icon' => 'fa-handshake',        'section' => 'Business Relation'],
            ['slug' => 'business-relation-contacts', 'label' => 'Business Relation Contact', 'icon' => 'fa-address-book',     'section' => 'Business Relation'],
            // Read-only, jalan pintas navigasi ke tab Site di workspace BR —
            // BR & BRS tetap 1 workspace gabungan, ini BUKAN modul CRUD baru.
            ['slug' => 'site-directory',              'label' => 'Site',                      'icon' => 'fa-map-location-dot', 'section' => 'Business Relation'],
            ['slug' => 'entitas',                    'label' => 'Entitas',                  'icon' => 'fa-sitemap',          'section' => 'Klasifikasi'],
            ['slug' => 'kepemilikan',                'label' => 'Kepemilikan',              'icon' => 'fa-landmark',         'section' => 'Klasifikasi'],
            ['slug' => 'kategori-bisnis',            'label' => 'Kategori Bisnis',          'icon' => 'fa-tags',             'section' => 'Klasifikasi'],
            ['slug' => 'sub-kategori-bisnis',        'label' => 'Sub Kategori Bisnis',      'icon' => 'fa-tag',              'section' => 'Klasifikasi'],
            ['slug' => 'business-estates',           'label' => 'Business Estate',          'icon' => 'fa-map-location-dot', 'section' => 'Lokasi'],
            ['slug' => 'commercial-buildings',       'label' => 'Commercial Buildings',     'icon' => 'fa-city',             'section' => 'Lokasi'],
        ],
    ],
    [
        'group'   => 'Lab Testing',
        'icon'    => 'fa-flask',
        'type'    => 'submenu',
        'items'   => [
            ['slug' => 'testing-units',                    'label' => 'Testing Units',           'icon' => 'fa-ruler'],
            ['slug' => 'testing-parameters',               'label' => 'Testing Parameters',      'icon' => 'fa-flask'],
            ['slug' => 'testing-standards',                'label' => 'Testing Standards',       'icon' => 'fa-certificate'],
            ['slug' => 'testing-kelompok-matriks-samples', 'label' => 'Kelompok Matriks Sample', 'icon' => 'fa-object-group'],
            ['slug' => 'testing-matriks-samples',          'label' => 'Matriks Sample',          'icon' => 'fa-table-cells'],
            ['slug' => 'testing-points',                   'label' => 'Testing Points',          'icon' => 'fa-location-dot'],
        ],
    ],
    [
        'group'   => 'Referensi',
        'icon'    => 'fa-list',
        'type'    => 'submenu',
        'items'   => [
            ['slug' => 'satuan',          'label' => 'Satuan',         'icon' => 'fa-ruler-combined'],
            ['slug' => 'budget-accounts', 'label' => 'Budget Account', 'icon' => 'fa-layer-group'],
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
            ['slug' => 'personnel',   'label' => 'Personnel',        'icon' => 'fa-people-group'],
            // 'lab-data-import' (Import Data Lab) dinonaktifkan 2026-09-07 —
            // fiturnya belum terverifikasi. Diblokir 403 di semua route
            // lewat DISABLED_SEGMENTS di CheckMenuPermission, dan sengaja
            // dihapus dari sini supaya tidak muncul di sidebar maupun
            // matrix permission (Grup Menu/User Management). Row permission
            // lama di menu_group_permissions/user_menu_permissions untuk
            // slug ini SENGAJA dibiarkan (tidak dihapus dari DB) — begitu
            // modul ini di-uncomment lagi nanti, akses lama otomatis balik
            // tanpa perlu setup ulang.
            ['slug' => 'documentations', 'label' => 'Dokumentasi', 'icon' => 'fa-book'],
        ],
    ],
    [
        // Grup khusus untuk aksi granular (bukan menu/halaman sungguhan) yang
        // levelnya lebih detail dari sekadar CRUD menu — mis. "Selesaikan FWO"
        // atau "Verifikasi Budget". Tidak tampil di sidebar, tapi tetap muncul
        // di matrix permission (Grup Menu & User Management) via /api/menus.
        // permission_mode: 'action' → matrix cuma render 1 kolom "Diizinkan"
        // (dipetakan ke can_update), bukan 4 kolom CRUD seperti menu biasa.
        'group'           => 'Aksi Khusus',
        'icon'            => 'fa-bolt',
        'type'            => 'submenu',
        'show_in_sidebar' => false,
        'permission_mode' => 'action',
        'divider'         => 'Aksi Khusus',
        'items'           => [
            ['slug' => 'fwo-complete',          'label' => 'Selesaikan FWO',            'icon' => 'fa-flag-checkered'],
            ['slug' => 'wo-complete',           'label' => 'Selesaikan WO',             'icon' => 'fa-flag-checkered'],
            ['slug' => 'wo-budget-verify',      'label' => 'Verifikasi Budget WO',      'icon' => 'fa-stamp'],
            ['slug' => 'fwo-budget-verify',     'label' => 'Verifikasi Budget FWO',     'icon' => 'fa-stamp'],
            ['slug' => 'output-pekerjaan-siap', 'label' => 'Siap Output Pekerjaan (WO)', 'icon' => 'fa-check'],
            ['slug' => 'termin-siap-kirim',     'label' => 'Siap Kirim Termin',         'icon' => 'fa-paper-plane'],
            ['slug' => 'termin-selesai',        'label' => 'Selesaikan Termin',         'icon' => 'fa-flag-checkered'],
            ['slug' => 'wo-budget-close',       'label' => 'Selesaikan Plan Budget WO', 'icon' => 'fa-box-archive'],
            ['slug' => 'fwo-budget-close',      'label' => 'Selesaikan Plan Budget FWO','icon' => 'fa-box-archive'],
        ],
    ],
];
