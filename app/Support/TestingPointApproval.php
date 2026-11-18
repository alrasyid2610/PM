<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Approval Testing Point & Paket Testing Point (pola sama seperti approval SQ).
 *
 *   draft ──ajukan──▶ pending ──setujui──▶ approved (bisa dipakai di BOQ baru)
 *     ▲                  │
 *     └─── tolak/tarik ──┘
 *
 * Satu helper untuk dua tabel (`testing_points` & `testing_point_paket`) supaya aturannya tidak
 * tersalin di dua controller. Slug izin approver: `testing-point-approve` (Aksi Khusus, kolom can_update).
 */
class TestingPointApproval
{
    public const DRAFT    = 'draft';
    public const PENDING  = 'pending';
    public const APPROVED = 'approved';

    public const SLUG = 'testing-point-approve';

    public const LABELS = [
        self::DRAFT    => 'Draft',
        self::PENDING  => 'Menunggu Approval',
        self::APPROVED => 'Disetujui',
    ];

    public static function label(?string $status): string
    {
        return self::LABELS[$status ?? self::DRAFT] ?? (string) $status;
    }

    // ── Pemakaian di BOQ ─────────────────────────────────────────────────────

    /**
     * Nama Testing Point (dari daftar id) yang BELUM boleh dipakai di BOQ baru:
     * belum Disetujui, terhapus, atau tidak ada. Kosong = semua boleh dipakai.
     * @return string[]
     */
    public static function namaTidakBisaDipakai(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) return [];

        $ok  = DB::table('testing_points')->whereIn('id_testing_point', $ids)
            ->whereNull('deleted_at')->where('status_approval', self::APPROVED)
            ->pluck('id_testing_point')->map(fn ($v) => (int) $v);
        $bad = $ids->diff($ok);
        if ($bad->isEmpty()) return [];

        $nama = DB::table('testing_points')->whereIn('id_testing_point', $bad)->pluck('nama', 'id_testing_point');
        return $bad->map(fn ($id) => $nama[$id] ?? ('#' . $id))->values()->all();
    }

    /** Pesan 422 siap pakai; null bila semua Testing Point boleh dipakai. */
    public static function pesanTidakBisaDipakai(iterable $ids): ?string
    {
        $nama = self::namaTidakBisaDipakai($ids);
        if (!$nama) return null;
        return 'Testing Point berikut belum disetujui sehingga belum bisa dipakai: ' . implode(', ', $nama)
            . '. Ajukan & setujui dulu di menu Testing Points.';
    }

    // ── Transisi status ──────────────────────────────────────────────────────

    /**
     * @param string $table  testing_points | testing_point_paket
     * @param string $pk     id_testing_point | id_paket
     * @param ?array $parent parent audit ['testing_points', id] untuk paket
     */
    private static function row(string $table, string $pk, int $id)
    {
        return DB::table($table)->where($pk, $id)
            ->when($table === 'testing_points', fn ($q) => $q->whereNull('deleted_at'))
            ->first();
    }

    private static function simpan(string $table, string $pk, int $id, array $data, ?array $parent): void
    {
        $before = DB::table($table)->where($pk, $id)->get()->toJson();
        DB::table($table)->where($pk, $id)->update($data + ['updated_at' => now()]);
        saveAudit($table, $id, 'update', $before, DB::table($table)->where($pk, $id)->get()->toJson(), $parent);
    }

    /** @return array{0:int,1:array} [http status, payload] */
    public static function ajukan(string $table, string $pk, int $id, string $nama, ?array $parent = null, ?string $kind = null): array
    {
        $row = self::row($table, $pk, $id);
        if (!$row) return [404, ['message' => 'Data tidak ditemukan']];
        if ($row->status_approval !== self::DRAFT) {
            return [422, ['message' => 'Hanya data berstatus Draft yang bisa diajukan untuk approval.']];
        }
        self::simpan($table, $pk, $id, [
            'status_approval' => self::PENDING, 'diajukan_oleh' => auth()->id(), 'diajukan_at' => now(),
            'approval_oleh' => null, 'approval_at' => null, 'catatan_approval' => null,
        ], $parent);
        self::notify('testing_point.diajukan', $table, $pk, $id, $nama);
        return [200, ['success' => true, 'message' => 'Diajukan untuk approval. Data terkunci sampai ada keputusan.']];
    }

    /** @return array{0:int,1:array} */
    public static function tarik(string $table, string $pk, int $id, ?array $parent = null): array
    {
        $row = self::row($table, $pk, $id);
        if (!$row) return [404, ['message' => 'Data tidak ditemukan']];
        if ($row->status_approval !== self::PENDING) {
            return [422, ['message' => 'Hanya data berstatus Menunggu Approval yang bisa ditarik kembali.']];
        }
        if ((int) $row->diajukan_oleh !== (int) auth()->id() && !userCan(self::SLUG, 'can_update')) {
            return [403, ['message' => 'Hanya pengaju atau approver yang bisa menarik pengajuan ini.']];
        }
        self::simpan($table, $pk, $id, [
            'status_approval' => self::DRAFT, 'diajukan_oleh' => null, 'diajukan_at' => null,
        ], $parent);
        return [200, ['success' => true, 'message' => 'Pengajuan ditarik. Data kembali Draft dan bisa diedit.']];
    }

    /**
     * @param ?callable $syaratTambahan fn($row): ?string — pesan alasan bila belum boleh disetujui (mis. induk Paket belum Disetujui)
     * @return array{0:int,1:array}
     */
    public static function setujui(string $table, string $pk, int $id, string $nama, ?array $parent = null, ?callable $syaratTambahan = null): array
    {
        if (!userCan(self::SLUG, 'can_update')) {
            return [403, ['message' => 'Anda tidak memiliki akses untuk menyetujui.']];
        }
        $row = self::row($table, $pk, $id);
        if (!$row) return [404, ['message' => 'Data tidak ditemukan']];
        if ($row->status_approval !== self::PENDING) {
            return [422, ['message' => 'Hanya data berstatus Menunggu Approval yang bisa disetujui.']];
        }
        if ($syaratTambahan && ($alasan = $syaratTambahan($row))) {
            return [422, ['message' => $alasan]];
        }
        self::simpan($table, $pk, $id, [
            'status_approval' => self::APPROVED, 'approval_oleh' => auth()->id(), 'approval_at' => now(), 'catatan_approval' => null,
        ], $parent);
        self::notify('testing_point.disetujui', $table, $pk, $id, $nama);
        return [200, ['success' => true, 'message' => 'Disetujui dan sekarang bisa dipakai di BOQ.']];
    }

    /** @return array{0:int,1:array} */
    public static function tolak(string $table, string $pk, int $id, string $nama, string $catatan, ?array $parent = null): array
    {
        if (!userCan(self::SLUG, 'can_update')) {
            return [403, ['message' => 'Anda tidak memiliki akses untuk menolak.']];
        }
        if (trim($catatan) === '') return [422, ['message' => 'Alasan penolakan wajib diisi.']];
        $row = self::row($table, $pk, $id);
        if (!$row) return [404, ['message' => 'Data tidak ditemukan']];
        if ($row->status_approval !== self::PENDING) {
            return [422, ['message' => 'Hanya data berstatus Menunggu Approval yang bisa ditolak.']];
        }
        $pengaju = $row->diajukan_oleh;
        self::simpan($table, $pk, $id, [
            'status_approval' => self::DRAFT, 'approval_oleh' => auth()->id(), 'approval_at' => now(), 'catatan_approval' => $catatan,
        ], $parent);
        self::notify('testing_point.ditolak', $table, $pk, $id, $nama, ['catatan' => $catatan], $pengaju);
        return [200, ['success' => true, 'message' => 'Ditolak dan dikembalikan ke Draft.']];
    }

    /**
     * Perubahan struktural pada data yang sudah Disetujui → otomatis Menunggu Approval (diajukan atas nama pengubah).
     * Tidak melakukan apa-apa bila statusnya bukan `approved`.
     */
    public static function ajukanOtomatis(string $table, string $pk, int $id, string $nama, ?array $parent = null): bool
    {
        $row = DB::table($table)->where($pk, $id)->first();
        if (!$row || $row->status_approval !== self::APPROVED) return false;
        self::simpan($table, $pk, $id, [
            'status_approval' => self::PENDING, 'diajukan_oleh' => auth()->id(), 'diajukan_at' => now(),
            'approval_oleh' => null, 'approval_at' => null, 'catatan_approval' => null,
        ], $parent);
        self::notify('testing_point.diajukan', $table, $pk, $id, $nama);
        return true;
    }

    // ── Notifikasi ───────────────────────────────────────────────────────────

    private static function notify(string $event, string $table, string $pk, int $id, string $nama, array $extra = [], $pengaju = null): void
    {
        try {
            $idPoint = $table === 'testing_points'
                ? $id
                : (int) DB::table('testing_point_paket')->where($pk, $id)->value('id_testing_point');
            $pengaju ??= DB::table($table)->where($pk, $id)->value('diajukan_oleh');

            \App\Services\NotificationDispatcher::fire($event, [
                'title' => ['testing_point.diajukan' => 'Testing Point diajukan: ', 'testing_point.disetujui' => 'Testing Point disetujui: ', 'testing_point.ditolak' => 'Testing Point ditolak: '][$event] . '{{nama}}',
                'body'  => '',
                'url'   => url('testing-points?open=' . $idPoint),
            ], ['nama' => $nama, 'nama_user' => auth()->user()?->name] + $extra, [
                'pengaju' => $pengaju ? [(int) $pengaju] : [],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notifikasi approval Testing Point gagal: ' . $e->getMessage());
        }
    }
}
