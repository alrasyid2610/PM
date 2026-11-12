<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\CommonFields;
use App\Support\ListFilter\Fields\StatusField;
use App\Support\ListFilter\FilterSet;

/** Filter daftar Sales Quotation (alias: `s` = sales_quotations, `br`, `brs` — lihat SalesQuotationController::listBaseQuery). */
final class SalesQuotationFilters extends FilterSet
{
    protected function fields(): array
    {
        return [
            CommonFields::nomor('no_sq', 'No. SQ', 's.no_sq'),
            CommonFields::judul('s.judul_order', 'Judul Order'),
            CommonFields::pelanggan('br.nama'),
            CommonFields::site('brs.nama_lokasi', 'Site'),
            CommonFields::picOrder('s.pic_order'),
            StatusField::make('status', 'Status', [
                'all' => 'All', 'draft' => 'Draft', 'pending' => 'Menunggu Approval', 'final' => 'Final',
                'win' => 'Win', 'lose' => 'Lose', 'void' => 'Void', 'deleted' => 'Deleted',
            ], 's.status', 's.deleted_at'),
        ];
    }
}
