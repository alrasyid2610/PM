<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\Fields\StatusField;
use App\Support\ListFilter\FilterSet;

/** Filter daftar Master Label Budget (alias query: `m` = budget_label — lihat BudgetLabelController::data). */
final class BudgetLabelFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::judul('m.nama', 'Label'),
            StatusField::make('status', 'Status', ['all' => 'All', '1' => 'Aktif', '0' => 'Tidak Aktif'], 'm.is_aktif'),
        ];
    }
}
