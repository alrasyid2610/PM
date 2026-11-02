<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Segregasi data SO & SQ (sejak 2026-11-01).
 * Pemilik data = PIC Order + PIC Input. User melihat data miliknya dan milik seluruh bawahannya
 * (hierarki `users.id_atasan`, bertingkat ke bawah). Pemegang Aksi Khusus `so-lihat-semua` /
 * `sq-lihat-semua` melihat semuanya. Approver SQ (`sq-approve`) juga melihat SQ berstatus pending.
 */
class DataScope
{
    private static array $cache = [];

    /** ID user sendiri + semua bawahan (rekursif, kebal siklus). */
    public static function userIds(?int $userId = null): array
    {
        $userId = $userId ?? (int) auth()->id();
        if (isset(self::$cache[$userId])) {
            return self::$cache[$userId];
        }

        $map = [];
        foreach (DB::table('users')->whereNotNull('id_atasan')->get(['id', 'id_atasan']) as $u) {
            $map[(int) $u->id_atasan][] = (int) $u->id;
        }

        $ids   = [$userId];
        $queue = [$userId];
        $seen  = [$userId => true];
        while ($queue) {
            $cur = array_shift($queue);
            foreach ($map[$cur] ?? [] as $child) {
                if (!isset($seen[$child])) {
                    $seen[$child] = true;
                    $ids[]   = $child;
                    $queue[] = $child;
                }
            }
        }

        return self::$cache[$userId] = $ids;
    }

    public static function seeAllSo(): bool
    {
        return userCan('so-lihat-semua', 'can_update');
    }

    public static function seeAllSq(): bool
    {
        return userCan('sq-lihat-semua', 'can_update');
    }

    /** Batasi query SO. $alias = alias tabel sales_orders ('' bila tanpa alias). */
    public static function so($query, string $alias = '')
    {
        if (self::seeAllSo()) {
            return $query;
        }
        $p   = $alias === '' ? '' : $alias . '.';
        $ids = self::userIds();

        return $query->where(fn ($q) => $q->whereIn($p . 'pic_input', $ids)->orWhereIn($p . 'pic_order', $ids));
    }

    /** Batasi query SQ. Approver juga melihat SQ pending. */
    public static function sq($query, string $alias = '')
    {
        if (self::seeAllSq()) {
            return $query;
        }
        $p   = $alias === '' ? '' : $alias . '.';
        $ids = self::userIds();
        $approver = userCan('sq-approve', 'can_update');

        return $query->where(function ($q) use ($p, $ids, $approver) {
            $q->whereIn($p . 'pic_input', $ids)->orWhereIn($p . 'pic_order', $ids);
            if ($approver) {
                $q->orWhere($p . 'status', 'pending');
            }
        });
    }

    public static function canSeeSo($idSo): bool
    {
        if (!$idSo || self::seeAllSo()) {
            return true;
        }

        // Record tidak ada → biarkan lolos agar controller yang membalas 404
        return !DB::table('sales_orders')->where('id_so', $idSo)->exists()
            || self::so(DB::table('sales_orders')->where('id_so', $idSo))->exists();
    }

    public static function canSeeSq($idSq): bool
    {
        if (!$idSq || self::seeAllSq()) {
            return true;
        }

        return !DB::table('sales_quotations')->where('id_sq', $idSq)->exists()
            || self::sq(DB::table('sales_quotations')->where('id_sq', $idSq))->exists();
    }
}
