<?php

namespace App\Support;

/**
 * Master event notifikasi (kurasi). Daftar event yang bisa dipilih admin di menu
 * "Pengaturan Notifikasi" (tombol Tambah Event). Titik pemicunya (NotificationDispatcher::fire)
 * sudah dipasang di controller terkait; event baru = 1 entri di sini + 1 pemicu di kode.
 *
 * roles: peran penerima yang diambil dari data record saat event terpicu
 *        (lihat App\Support\NotificationRoles). 'actor' selalu tersedia.
 */
class NotificationCatalog
{
    /** Label peran penerima dari data record. */
    public const ROLE_LABELS = [
        'actor'              => 'Pelaku aksi (user yang melakukannya)',
        'pic_input'          => 'PIC Input (SO/SQ)',
        'pic_order'          => 'PIC Order (SO/SQ)',
        'marketing_internal' => 'Marketing Internal (SO/SQ)',
        'marketing_eksternal' => 'Marketing Eksternal (SO/SQ)',
        'wo_pic'             => 'PIC Pekerjaan WO',
        'fwo_penyelia'       => 'Penyelia FWO',
        'fwo_personel'       => 'Personel FWO (yang punya akun login)',
        'pengaju'            => 'Pengaju SQ',
    ];

    private const ROLES_SO  = ['actor', 'pic_input', 'pic_order', 'marketing_internal', 'marketing_eksternal'];
    private const ROLES_WO  = ['actor', 'wo_pic', 'pic_input', 'pic_order', 'marketing_internal', 'marketing_eksternal'];
    private const ROLES_FWO = ['actor', 'fwo_penyelia', 'fwo_personel', 'wo_pic', 'pic_input', 'pic_order', 'marketing_internal', 'marketing_eksternal'];
    private const ROLES_SQ  = ['actor', 'pengaju', 'pic_input', 'pic_order', 'marketing_internal', 'marketing_eksternal'];

    public static function all(): array
    {
        return [
            'so.created' => [
                'label' => 'SO Dibuat', 'module' => 'Sales Order',
                'description' => 'Terpicu begitu Sales Order baru selesai dibuat.',
                'fields' => ['no_so', 'judul_order', 'nama_user'],
                'roles'  => self::ROLES_SO,
            ],
            'wo.created' => [
                'label' => 'WO Dibuat', 'module' => 'Work Order',
                'description' => 'Terpicu begitu Work Order baru dibuat.',
                'fields' => ['no_wo', 'judul_pekerjaan', 'no_so', 'nama_user'],
                'roles'  => self::ROLES_WO,
            ],
            'wo.completed' => [
                'label' => 'WO Diselesaikan', 'module' => 'Work Order',
                'description' => 'Terpicu begitu Work Order berhasil diselesaikan.',
                'fields' => ['no_wo', 'judul_pekerjaan', 'no_so', 'nama_user'],
                'roles'  => self::ROLES_WO,
            ],
            'fwo.created' => [
                'label' => 'FWO Dibuat', 'module' => 'Fieldwork Order',
                'description' => 'Terpicu begitu Fieldwork Order baru dibuat.',
                'fields' => ['no_fwo', 'judul_pekerjaan', 'no_wo', 'nama_user'],
                'roles'  => self::ROLES_FWO,
            ],
            'fwo.completed' => [
                'label' => 'FWO Diselesaikan', 'module' => 'Fieldwork Order',
                'description' => 'Terpicu begitu Fieldwork Order berhasil diselesaikan.',
                'fields' => ['no_fwo', 'judul_pekerjaan', 'no_wo', 'nama_user'],
                'roles'  => self::ROLES_FWO,
            ],
            'output_pekerjaan.siap' => [
                'label' => 'Output Pekerjaan Siap', 'module' => 'Output',
                'description' => 'Terpicu begitu Output Pekerjaan ditandai Siap.',
                'fields' => ['nama_output', 'no_wo', 'nama_user'],
                'roles'  => self::ROLES_WO,
            ],
            'output_other.siap' => [
                'label' => 'Output Other Siap', 'module' => 'Output',
                'description' => 'Terpicu begitu Output Other (level WO atau SO) ditandai Siap.',
                'fields' => ['nama_item', 'no_dokumen', 'nama_user'],
                'roles'  => self::ROLES_WO,
            ],
            'fwo_budget.verifikasi_dibutuhkan' => [
                'label' => 'Budget FWO Butuh Verifikasi', 'module' => 'Budget',
                'description' => 'Terpicu begitu realisasi pengeluaran Budget FWO diinput (menunggu verifikasi).',
                'fields' => ['no_fwo', 'label_plan', 'nominal', 'nama_user'],
                'roles'  => self::ROLES_FWO,
            ],
            'wo_budget.verifikasi_dibutuhkan' => [
                'label' => 'Budget WO Butuh Verifikasi', 'module' => 'Budget',
                'description' => 'Terpicu begitu realisasi pengeluaran Budget WO diinput (menunggu verifikasi).',
                'fields' => ['no_wo', 'label_plan', 'nominal', 'nama_user'],
                'roles'  => self::ROLES_WO,
            ],
            'fwo_budget.plan_selesai' => [
                'label' => 'Plan Budget FWO Diselesaikan', 'module' => 'Budget',
                'description' => 'Terpicu begitu Plan Budget FWO diselesaikan.',
                'fields' => ['no_fwo', 'label_plan', 'nama_user'],
                'roles'  => self::ROLES_FWO,
            ],
            'wo_budget.plan_selesai' => [
                'label' => 'Plan Budget WO Diselesaikan', 'module' => 'Budget',
                'description' => 'Terpicu begitu Plan Budget WO diselesaikan.',
                'fields' => ['no_wo', 'label_plan', 'nama_user'],
                'roles'  => self::ROLES_WO,
            ],
            'sq.created' => [
                'label' => 'SQ Dibuat', 'module' => 'Sales Quotation',
                'description' => 'Terpicu begitu Sales Quotation baru dibuat.',
                'fields' => ['no_sq', 'judul_order', 'nama_user'],
                'roles'  => self::ROLES_SQ,
            ],
            'sq.terbit_so' => [
                'label' => 'SQ Diterbitkan jadi SO', 'module' => 'Sales Quotation',
                'description' => 'Terpicu begitu SQ (Win) diterbitkan menjadi Sales Order.',
                'fields' => ['no_sq', 'no_so', 'judul_order', 'nama_user'],
                'roles'  => self::ROLES_SQ,
            ],
            'sq.diajukan' => [
                'label' => 'SQ Diajukan (Menunggu Approval)', 'module' => 'Sales Quotation',
                'description' => 'Terpicu begitu SQ diajukan untuk approval manager.',
                'fields' => ['no_sq', 'judul_order', 'nama_user'],
                'roles'  => self::ROLES_SQ,
            ],
            'sq.disetujui' => [
                'label' => 'SQ Disetujui', 'module' => 'Sales Quotation',
                'description' => 'Terpicu begitu SQ disetujui manager (menjadi Final).',
                'fields' => ['no_sq', 'judul_order', 'nama_user'],
                'roles'  => self::ROLES_SQ,
            ],
            'sq.ditolak' => [
                'label' => 'SQ Ditolak', 'module' => 'Sales Quotation',
                'description' => 'Terpicu begitu SQ ditolak manager (kembali Draft).',
                'fields' => ['no_sq', 'judul_order', 'catatan', 'nama_user'],
                'roles'  => self::ROLES_SQ,
            ],
            'sq.win' => [
                'label' => 'SQ Win', 'module' => 'Sales Quotation',
                'description' => 'Terpicu begitu SQ ditandai Win.',
                'fields' => ['no_sq', 'judul_order', 'nama_user'],
                'roles'  => self::ROLES_SQ,
            ],
            'sq.lose' => [
                'label' => 'SQ Lose', 'module' => 'Sales Quotation',
                'description' => 'Terpicu begitu SQ ditandai Lose.',
                'fields' => ['no_sq', 'judul_order', 'nama_user'],
                'roles'  => self::ROLES_SQ,
            ],
            'termin.siap_kirim' => [
                'label' => 'Termin Siap Kirim', 'module' => 'Termin',
                'description' => 'Terpicu begitu Termin ditandai Siap Kirim.',
                'fields' => ['no_termin', 'no_so', 'nama_user'],
                'roles'  => self::ROLES_SO,
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** Peran (key => label) yang tersedia untuk sebuah event. */
    public static function rolesOf(string $key): array
    {
        $out = [];
        foreach (self::get($key)['roles'] ?? [] as $role) {
            $out[$role] = self::ROLE_LABELS[$role] ?? $role;
        }
        return $out;
    }
}
