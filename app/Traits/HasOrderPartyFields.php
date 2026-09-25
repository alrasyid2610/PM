<?php

namespace App\Traits;

/**
 * Field "pihak order" yang identik antara Sales Order dan Sales Quotation
 * (Data Pemesan/Pengiriman/Pembayaran + PIC Internal + Office) — didefinisikan
 * 1x di sini supaya tidak drift antara 2 modul. Lihat Obsidian
 * Modules/Sales Quotation.md bagian "Field bersama SQ & SO lewat Trait".
 *
 * CATATAN: per Fase 1 (2026-09-25), trait ini dipakai SalesQuotationController.
 * SalesOrderController BELUM di-refactor untuk memakainya (rule-nya sudah ada
 * duluan di soRequiredRules()/soRequiredMessages()) — kalau salah satu field
 * di bawah berubah, cek juga SalesOrderController::soRequiredRules().
 */
trait HasOrderPartyFields
{
    public static function orderPartyRules(): array
    {
        return [
            'id_pelanggan' => 'required|integer',
            'id_site_pelanggan' => 'required|integer',
            'id_pic_pelanggan' => 'required|integer',
            'id_pelanggan_delivery' => 'required|integer',
            'id_site_pelanggan_delivery' => 'required|integer',
            'id_pic_pelanggan_delivery' => 'required|integer',
            'id_pelanggan_payment' => 'required|integer',
            'id_site_pelanggan_payment' => 'required|integer',
            'id_pic_pelanggan_payment' => 'required|integer',
            'pic_input' => 'required|integer',
            'pic_marketing_internal' => 'required|integer',
            'pic_marketing_eksternal' => 'nullable|integer',
            'id_office' => 'nullable|integer',
        ];
    }

    public static function orderPartyMessages(): array
    {
        return [
            'id_pelanggan.required' => 'Perusahaan pada Data Pemesan wajib dipilih.',
            'id_site_pelanggan.required' => 'Site pada Data Pemesan wajib dipilih.',
            'id_pic_pelanggan.required' => 'PIC pada Data Pemesan wajib dipilih.',
            'id_pelanggan_delivery.required' => 'Perusahaan pada Data Pengiriman wajib dipilih.',
            'id_site_pelanggan_delivery.required' => 'Site pada Data Pengiriman wajib dipilih.',
            'id_pic_pelanggan_delivery.required' => 'PIC pada Data Pengiriman wajib dipilih.',
            'id_pelanggan_payment.required' => 'Perusahaan pada Data Pembayaran wajib dipilih.',
            'id_site_pelanggan_payment.required' => 'Site pada Data Pembayaran wajib dipilih.',
            'id_pic_pelanggan_payment.required' => 'PIC pada Data Pembayaran wajib dipilih.',
            'pic_input.required' => 'PIC Input wajib dipilih.',
            'pic_marketing_internal.required' => 'Marketing Internal wajib dipilih.',
        ];
    }

    /**
     * Kolom yang dipetakan dari $request untuk insert/update — dipakai bersama
     * store() & update() supaya daftar field tidak dobel ditulis.
     */
    public static function orderPartyColumns(\Illuminate\Http\Request $request): array
    {
        return [
            'id_pelanggan' => $request->id_pelanggan,
            'id_site_pelanggan' => $request->id_site_pelanggan,
            'id_pic_pelanggan' => $request->id_pic_pelanggan,
            'id_pelanggan_delivery' => $request->id_pelanggan_delivery,
            'id_site_pelanggan_delivery' => $request->id_site_pelanggan_delivery,
            'id_pic_pelanggan_delivery' => $request->id_pic_pelanggan_delivery,
            'id_pelanggan_payment' => $request->id_pelanggan_payment,
            'id_site_pelanggan_payment' => $request->id_site_pelanggan_payment,
            'id_pic_pelanggan_payment' => $request->id_pic_pelanggan_payment,
            'pic_input' => $request->pic_input,
            'pic_marketing_internal' => $request->pic_marketing_internal,
            'pic_marketing_eksternal' => $request->pic_marketing_eksternal,
            'id_office' => $request->id_office,
        ];
    }
}
