<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\Fields\StatusField;
use App\Support\ListFilter\FilterSet;

/** Filter daftar Master Output (alias query: `m` = output_judul — lihat OutputMasterController::data). */
final class OutputMasterFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::judul('m.judul', 'Judul'),
            StatusField::make('tipe', 'Tipe', ['all' => 'All', 'pekerjaan' => 'Pekerjaan', 'other' => 'Other'], 'm.tipe'),
            StatusField::make('status', 'Status', ['all' => 'All', '1' => 'Aktif', '0' => 'Tidak Aktif'], 'm.is_aktif'),
        ];
    }
}
