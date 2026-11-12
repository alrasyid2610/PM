<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\Fields\StatusField;
use App\Support\ListFilter\FilterSet;
use App\Support\ListFilter\Fields\TextField;

/** Filter daftar Termin (alias: `t` = termin — lihat TerminController::data). Termin tidak punya soft delete. */
final class TerminFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::nomor('no_termin', 'No. Termin', 't.no_termin'),
            TextField::make('nama', 'Nama', 't.nama'),
            StatusField::make('status', 'Status', [
                'all' => 'All', 'pending' => 'Pending', 'proses' => 'Proses', 'selesai' => 'Selesai',
            ], 't.status'),
        ];
    }
}
