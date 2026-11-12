<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\Fields\StatusField;
use App\Support\ListFilter\FilterSet;

/** Filter daftar FWO (alias: `fw`, `wo`, `br`, `brs` = site FWO, `brs_wo` = site WO — lihat FieldworkController::listBaseQuery). */
final class FieldworkFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::nomor('no_fwo', 'No. FWO', 'fw.no_fwo'),
            CommonFields::nomor('no_wo', 'No. WO', 'wo.no_wo'),
            CommonFields::judul('fw.judul_pekerjaan', 'Judul Pekerjaan'),
            CommonFields::pelanggan('br.nama'),
            // Site FWO, cadangan Site WO (sama dengan kolom "Site Pelanggan" di daftar)
            CommonFields::site(['brs.nama_lokasi', 'brs_wo.nama_lokasi'], 'Site Pelanggan'),
            // Status efektif: planned yang tanggal mulainya tiba = on_progress (fwoStatusSql)
            StatusField::make('status', 'Status', [
                'all' => 'All', 'belum_jadwal' => 'Belum Dijadwalkan', 'planned' => 'Planned',
                'on_progress' => 'On Progress', 'completed' => 'Completed', 'deleted' => 'Deleted',
            ], null, 'fw.deleted_at', fwoStatusSql('fw')),
        ];
    }
}
