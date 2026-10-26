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

// Widget Dashboard: item grup "Widget Dashboard" di bawah dibangun dari katalog
// config/dashboard_widgets.php (satu sumber), supaya widget baru otomatis muncul di matrix permission.
$dashboardWidgetItems = array_map(fn ($w) => [
    'slug'    => $w['slug'],
    'label'   => $w['label'],
    'icon'    => $w['icon'],
    'section' => $w['kelompok'],
], require __DIR__ . '/dashboard_widgets.php');

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
        'group'   => 'Sales Quotation',
        'icon'    => 'fa-file-signature',
        'type'    => 'submenu',
        'divider' => 'Main Menu',
        'items'   => [
            ['slug' => 'sales-quotations', 'label' => 'Sales Quotation', 'icon' => 'fa-file-signature'],
            ['slug' => 'sq-work-orders',   'label' => 'SQ Work Order',   'icon' => 'fa-briefcase'],
            ['slug' => 'sq-fieldworks',    'label' => 'SQ Fieldwork',    'icon' => 'fa-helmet-safety'],
        ],
    ],
    [
        'group'   => 'Sales Order',
        'icon'    => 'fa-file-invoice',
        'type'    => 'submenu',
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
            ['slug' => 'office',          'label' => 'Office',         'icon' => 'fa-building'],
            ['slug' => 'alasan-tidak-terlaksana', 'label' => 'Alasan Tidak Terlaksana', 'icon' => 'fa-ban'],
            ['slug' => 'bank-accounts', 'label' => 'Bank Account', 'icon' => 'fa-building-columns'],
            ['slug' => 'poin-keterangan', 'label' => 'Poin Keterangan', 'icon' => 'fa-list-ol'],
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
            ['slug' => 'data-import', 'label' => 'Import Data',      'icon' => 'fa-file-import'],
            ['slug' => 'documentations', 'label' => 'Dokumentasi', 'icon' => 'fa-book'],
            ['slug' => 'notification-settings', 'label' => 'Pengaturan Notifikasi', 'icon' => 'fa-bell'],
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
            ['slug' => 'output-pekerjaan-siap', 'label' => 'Siap Output (Pekerjaan & Other)', 'icon' => 'fa-check'],
            ['slug' => 'termin-siap-kirim',     'label' => 'Siap Kirim Termin',         'icon' => 'fa-paper-plane'],
            ['slug' => 'termin-selesai',        'label' => 'Selesaikan Termin',         'icon' => 'fa-flag-checkered'],
            ['slug' => 'wo-budget-close',       'label' => 'Selesaikan Plan Budget WO', 'icon' => 'fa-box-archive'],
            ['slug' => 'fwo-budget-close',      'label' => 'Selesaikan Plan Budget FWO','icon' => 'fa-box-archive'],
            ['slug' => 'sq-approve',            'label' => 'Setujui/Tolak SQ (Approval)', 'icon' => 'fa-circle-check'],
        ],
    ],
    [
        // Akses tab di halaman detail (mulai 2026-10-26: Work Order). Tidak tampil di sidebar.
        // permission_mode: 'tabs' → matrix membuat 1 baris per halaman berisi chip centang per tab
        // (bukan 1 baris per tab), disimpan di kolom can_update seperti mode 'action'.
        // Aturan: user/grup yang TIDAK punya satu pun tab di sebuah halaman → semua tab tampil
        // (kompatibel mundur); begitu ada ≥ 1 tab dicentang → hanya tab itu yang tampil.
        // 'section' = nama halaman (pengelompokan baris di matrix).
        'group'           => 'Akses Tab',
        'icon'            => 'fa-table-columns',
        'type'            => 'submenu',
        'show_in_sidebar' => false,
        'permission_mode' => 'tabs',
        'divider'         => 'Akses Tab',
        'items'           => [
            ['slug' => 'wo-tab-info',          'label' => 'Informasi',        'icon' => 'fa-circle-info',        'section' => 'Work Order'],
            ['slug' => 'wo-tab-boq',           'label' => 'BOQ',              'icon' => 'fa-layer-group',        'section' => 'Work Order'],
            ['slug' => 'wo-tab-boq-other',     'label' => 'BOQ Other',        'icon' => 'fa-file-invoice',       'section' => 'Work Order'],
            ['slug' => 'wo-tab-boq-sampling',  'label' => 'BOQ Sampling',     'icon' => 'fa-vial-virus',         'section' => 'Work Order'],
            ['slug' => 'wo-tab-fwo',           'label' => 'Fieldwork Orders', 'icon' => 'fa-hard-hat',           'section' => 'Work Order'],
            ['slug' => 'wo-tab-output',        'label' => 'Output Pekerjaan', 'icon' => 'fa-file-circle-check',  'section' => 'Work Order'],
            ['slug' => 'wo-tab-output-other',  'label' => 'Output Other',     'icon' => 'fa-file-circle-plus',   'section' => 'Work Order'],
            ['slug' => 'wo-tab-budget',        'label' => 'Budget',           'icon' => 'fa-wallet',             'section' => 'Work Order'],
            ['slug' => 'wo-tab-lab-sample-reg', 'label' => 'Lab Sample Reg',  'icon' => 'fa-vial',               'section' => 'Work Order'],

            ['slug' => 'so-tab-info',          'label' => 'Informasi',        'icon' => 'fa-circle-info',        'section' => 'Sales Order'],
            ['slug' => 'so-tab-wo',            'label' => 'Work Orders',      'icon' => 'fa-briefcase',          'section' => 'Sales Order'],
            ['slug' => 'so-tab-termin',        'label' => 'Termin',           'icon' => 'fa-file-invoice-dollar', 'section' => 'Sales Order'],
            ['slug' => 'so-tab-output-other',  'label' => 'Output Other',     'icon' => 'fa-file-circle-plus',   'section' => 'Sales Order'],

            ['slug' => 'fwo-tab-info',         'label' => 'Informasi',        'icon' => 'fa-hard-hat',           'section' => 'Fieldwork Order'],
            ['slug' => 'fwo-tab-personel',     'label' => 'Personel',         'icon' => 'fa-users',              'section' => 'Fieldwork Order'],
            ['slug' => 'fwo-tab-boq',          'label' => 'Fieldwork BOQ',    'icon' => 'fa-clipboard-list',     'section' => 'Fieldwork Order'],
            ['slug' => 'fwo-tab-boq-other',    'label' => 'BOQ Other',        'icon' => 'fa-file-invoice',       'section' => 'Fieldwork Order'],
            ['slug' => 'fwo-tab-boq-sampling', 'label' => 'BOQ Sampling',     'icon' => 'fa-vial-virus',         'section' => 'Fieldwork Order'],
            ['slug' => 'fwo-tab-attachment',   'label' => 'Attachment',       'icon' => 'fa-paperclip',          'section' => 'Fieldwork Order'],
            ['slug' => 'fwo-tab-budget',       'label' => 'Budget',           'icon' => 'fa-wallet',             'section' => 'Fieldwork Order'],
            ['slug' => 'fwo-tab-sample',       'label' => 'Sample',           'icon' => 'fa-flask',              'section' => 'Fieldwork Order'],
        ],
    ],
    [
        // Akses per widget di halaman Dashboard (lihat config/dashboard_widgets.php).
        // Sama seperti Aksi Khusus: tidak tampil di sidebar, matrix 1 kolom "Diizinkan".
        'group'           => 'Widget Dashboard',
        'icon'            => 'fa-gauge',
        'type'            => 'submenu',
        'show_in_sidebar' => false,
        'permission_mode' => 'action',
        'divider'         => 'Widget Dashboard',
        'items'           => $dashboardWidgetItems,
    ],
];
