<?php

namespace App\Support\ListFilter\Sets;

use App\Support\ListFilter\Fields\StatusField;
use App\Support\ListFilter\Fields\TextField;
use App\Support\ListFilter\FilterSet;

/** Filter Report Kelengkapan SO & SQ (alias query: `r` = gabungan SO+SQ — lihat ReportKelengkapanController::data). */
final class KelengkapanFilters extends FilterSet
{
    protected function fields(): array
    {
        $yn = ['all' => 'All', 'Y' => 'Y (Ada)', 'N' => 'N (Belum ada)'];

        return [
            StatusField::make('jenis', 'Jenis', ['all' => 'All', 'SO' => 'SO', 'SQ' => 'SQ'], 'r.jenis'),
            TextField::make('nomor', 'No SO/SQ', 'r.nomor'),
            TextField::make('pelanggan', 'Pelanggan', 'r.pelanggan'),
            TextField::make('site', 'Site', 'r.site'),
            TextField::make('pic_input', 'PIC Input', 'r.pic_input'),
            TextField::make('pic_order', 'PIC Order', 'r.pic_order'),
            StatusField::make('ada_wo', 'Ada WO', $yn, 'r.ada_wo'),
            StatusField::make('ada_fwo', 'Ada FWO', $yn, 'r.ada_fwo'),
            StatusField::make('ada_budget_wo', 'Ada Budget WO', $yn, 'r.ada_budget_wo'),
            StatusField::make('ada_budget_fwo', 'Ada Budget FWO', $yn, 'r.ada_budget_fwo'),
        ];
    }
}
