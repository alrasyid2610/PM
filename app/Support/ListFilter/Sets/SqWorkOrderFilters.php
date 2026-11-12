<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\FilterSet;

/** Filter daftar SQ Work Order (alias: `w` = sq_work_orders, `sq`, `br`, `brs` — lihat SqWorkOrderController::data). */
final class SqWorkOrderFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::nomor('no_sq_wo', 'No. WO', 'w.no_sq_wo'),
            CommonFields::nomor('no_sq', 'No. SQ', 'sq.no_sq'),
            CommonFields::judul('w.judul_pekerjaan', 'Judul Pekerjaan'),
            CommonFields::pelanggan('br.nama'),
            CommonFields::site('brs.nama_lokasi', 'Site Pekerjaan'),
        ];
    }
}
