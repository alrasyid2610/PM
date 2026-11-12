<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\FilterSet;

/** Filter daftar SQ Fieldwork (alias: `f` = sq_fieldworks, `w`, `sq`, `pel` = pelanggan, `brs` — lihat SqFieldworkController::data). */
final class SqFieldworkFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::nomor('no_sq_fwo', 'No. FWO', 'f.no_sq_fwo'),
            CommonFields::nomor('no_sq_wo', 'No. WO', 'w.no_sq_wo'),
            CommonFields::nomor('no_sq', 'No. SQ', 'sq.no_sq'),
            CommonFields::pelanggan('pel.nama'),
            CommonFields::site('brs.nama_lokasi', 'Site Pekerjaan'),
            CommonFields::judul('f.judul_pekerjaan', 'Judul Pekerjaan'),
        ];
    }
}
