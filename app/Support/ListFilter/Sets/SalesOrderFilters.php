<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\Fields\StatusField;
use App\Support\ListFilter\FilterSet;

/** Filter daftar Sales Order (alias query: `s` = sales_orders, `br` = business_relations — lihat SalesOrderController::listBaseQuery). */
final class SalesOrderFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::nomor('no_so', 'No. SO', 's.no_so'),
            CommonFields::judul('s.judul_order', 'Judul Order'),
            CommonFields::pelanggan('br.nama'),
            CommonFields::picOrder('s.pic_order'),
            StatusField::make('status', 'Status', [
                'all' => 'All', 'on-progress' => 'On Progress', 'completed' => 'Completed', 'cancel' => 'Cancel', 'deleted' => 'Deleted',
            ], 's.status', 's.deleted_at'),
        ];
    }
}
