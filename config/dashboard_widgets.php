<?php

/**
 * Katalog widget Dashboard — satu-satunya tempat untuk menambah/mengubah widget.
 * Dipakai oleh: DashboardController (data + penjagaan akses), view dashboard,
 * dan matrix permission (digabung ke config/menus.php sebagai grup "Widget Dashboard").
 *
 * Akses: tiap widget = 1 slug permission (mode 'action', dipetakan ke can_update),
 * diatur di Grup Menu / User Management — sama seperti "Aksi Khusus".
 * Kartu angka dan daftar (tabel) adalah widget TERPISAH dengan izin masing-masing.
 *
 * Struktur item:
 *   slug     — unik, diawali "dash-"; juga dipakai di user_menu_permissions / menu_group_permissions
 *   label    — nama widget di matrix & judul widget
 *   kelompok — pengelompokan di matrix (Sales, Operasional, Finance)
 *   type     — 'kpi' (kartu angka) | 'chart' (grafik) | 'table' (tab daftar)
 *   icon     — FontAwesome (tanpa prefix fa-solid)
 *   color/bg — warna ikon (khusus kpi)
 *   tab      — (kpi) tab tabel yang dibuka saat kartu diklik; hanya aktif bila widget tabelnya juga diizinkan
 *   key      — (table) nilai ?type= di /dashboard/list
 *   kind     — (chart) 'bar_tahun' (batang per bulan + pilihan tahun) | 'bar_tahun_nilai' (nilai Rp per bulan,
 *              + pilihan tahun & Kotor/Bersih) | 'donut' (proporsi) | 'peta' (peta provinsi Indonesia)
 *
 * Menambah widget: tambah entri di sini + tambah method data di DashboardController,
 * lalu beri akses lewat Grup Menu / User Management (tanpa akses, widget tidak tampil).
 */

return [
    // ── Sales ──
    ['slug' => 'dash-so-outstanding', 'label' => 'Outstanding SO',        'kelompok' => 'Sales', 'type' => 'kpi',   'icon' => 'fa-file-lines', 'color' => '#6366f1', 'bg' => '#eef2ff', 'tab' => 'so'],
    ['slug' => 'dash-so-per-bulan',   'label' => 'Grafik SO per Bulan',   'kelompok' => 'Sales', 'type' => 'chart', 'icon' => 'fa-chart-column', 'kind' => 'bar_tahun'],
    // Peta Indonesia (per provinsi): jumlah customer berdasarkan provinsi Site (BRS). Customer dengan site di
    // beberapa provinsi dihitung di tiap provinsi tersebut.
    ['slug' => 'dash-peta-customer', 'label' => 'Peta Customer per Provinsi', 'kelompok' => 'Sales', 'type' => 'chart', 'icon' => 'fa-map-location-dot', 'kind' => 'peta'],
    ['slug' => 'dash-so-nilai-per-bulan', 'label' => 'Nilai SO per Bulan', 'kelompok' => 'Sales', 'type' => 'chart', 'icon' => 'fa-sack-dollar', 'kind' => 'bar_tahun_nilai'],
    ['slug' => 'dash-list-so',        'label' => 'Daftar SO Outstanding', 'kelompok' => 'Sales', 'type' => 'table', 'icon' => 'fa-file-lines', 'key' => 'so'],

    // SQ (penawaran). Final = terkunci, menunggu keputusan customer; Win = disetujui (siap jadi SO); Lose/Void = tidak lanjut.
    ['slug' => 'dash-sq-final',        'label' => 'SQ Win Belum Jadi SO', 'kelompok' => 'Sales', 'type' => 'kpi',   'icon' => 'fa-file-signature', 'color' => '#1d4ed8', 'bg' => '#eff6ff', 'tab' => 'sq'],
    ['slug' => 'dash-sq-pending',      'label' => 'SQ Menunggu Approval', 'kelompok' => 'Sales', 'type' => 'kpi',   'icon' => 'fa-hourglass-half', 'color' => '#b45309', 'bg' => '#fffbeb', 'tab' => 'sq-approval'],
    ['slug' => 'dash-sq-kedaluwarsa',  'label' => 'SQ Final Mendekati Kedaluwarsa', 'kelompok' => 'Sales', 'type' => 'kpi', 'icon' => 'fa-hourglass-half', 'color' => '#d97706', 'bg' => '#fffbeb', 'tab' => 'sq'],
    ['slug' => 'dash-sq-per-status',   'label' => 'SQ per Status',          'kelompok' => 'Sales', 'type' => 'chart', 'icon' => 'fa-chart-pie', 'kind' => 'donut'],
    ['slug' => 'dash-list-sq',         'label' => 'Daftar SQ Win Belum Jadi SO', 'kelompok' => 'Sales', 'type' => 'table', 'icon' => 'fa-file-signature', 'key' => 'sq'],
    ['slug' => 'dash-list-sq-approval', 'label' => 'Daftar SQ Menunggu Approval', 'kelompok' => 'Sales', 'type' => 'table', 'icon' => 'fa-hourglass-half', 'key' => 'sq-approval'],

    // ── Operasional ──
    ['slug' => 'dash-wo-outstanding',  'label' => 'Outstanding WO',        'kelompok' => 'Operasional', 'type' => 'kpi',   'icon' => 'fa-briefcase',     'color' => '#0284c7', 'bg' => '#e0f2fe', 'tab' => 'wo'],
    ['slug' => 'dash-fwo-outstanding', 'label' => 'Outstanding FWO',       'kelompok' => 'Operasional', 'type' => 'kpi',   'icon' => 'fa-helmet-safety', 'color' => '#0d9488', 'bg' => '#f0fdfa', 'tab' => 'fwo'],
    ['slug' => 'dash-overdue',         'label' => 'Overdue (WO + FWO)',    'kelompok' => 'Operasional', 'type' => 'kpi',   'icon' => 'fa-flag',          'color' => '#dc2626', 'bg' => '#fef2f2'],
    ['slug' => 'dash-list-wo',         'label' => 'Daftar WO On Progress', 'kelompok' => 'Operasional', 'type' => 'table', 'icon' => 'fa-briefcase',     'key' => 'wo'],
    ['slug' => 'dash-list-fwo',        'label' => 'Daftar FWO Planned',    'kelompok' => 'Operasional', 'type' => 'table', 'icon' => 'fa-helmet-safety', 'key' => 'fwo'],

    // ── Finance ──
    ['slug' => 'dash-termin-outstanding', 'label' => 'Outstanding Termin',          'kelompok' => 'Finance', 'type' => 'kpi',   'icon' => 'fa-receipt', 'color' => '#7c3aed', 'bg' => '#f5f3ff', 'tab' => 'termin'],
    ['slug' => 'dash-list-termin',        'label' => 'Daftar Termin Belum Selesai', 'kelompok' => 'Finance', 'type' => 'table', 'icon' => 'fa-receipt', 'key' => 'termin'],
];
