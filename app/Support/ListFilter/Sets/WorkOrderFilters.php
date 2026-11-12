<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\Fields\StatusField;
use App\Support\ListFilter\FilterSet;

/** Filter daftar Work Order (alias: `wo`, `s` = sales_orders, `br` — lihat WorkOrderController::listBaseQuery). */
final class WorkOrderFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::nomor('no_wo', 'No. WO', 'wo.no_wo'),
            CommonFields::nomor('no_so', 'No. SO', 's.no_so'),
            CommonFields::judul('s.judul_order', 'Judul Pekerjaan'),
            CommonFields::pelanggan('br.nama'),
            StatusField::make('status', 'Status', [
                'all' => 'All', 'onprogress' => 'On Progress', 'completed' => 'Completed', 'deleted' => 'Deleted',
            ], 'wo.status', 'wo.deleted_at'),
        ];
    }
}
