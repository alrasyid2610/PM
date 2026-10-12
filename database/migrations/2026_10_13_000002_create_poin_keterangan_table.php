<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Poin Keterangan" — varian poin keterangan penawaran (SQ) yang dipilih user
 * (bukan diketik). Template memakai isian {kunci:tipe=default}, lihat
 * App\Support\PoinKeteranganTemplate. Seed dari 3 contoh penawaran; poin yang ditandai
 * is_default = paket standar yang otomatis terisi di SQ baru.
 * Sekaligus seed permission menu 'poin-keterangan' (dari 'satuan').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poin_keterangan', function (Blueprint $table) {
            $table->increments('id_poin');
            $table->string('kategori', 40);
            $table->text('template');
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('urutan_default')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        $rows = [
            // ── Paket standar (contoh penawaran no. 3) ──
            ['jadwal_sampling', 'Penjadwalan sampling dilakukan setelah PO diterbitkan dan diterima oleh laboratorium', 1],
            ['waktu_laporan',   'Penerbitan laporan hasil uji adalah {hari:angka=12} hari kerja setelah sampel diterima laboratorium', 2],
            ['biaya_sampling',  'Penawaran sudah termasuk biaya pengambilan sampel di lokasi yang disepakati pelanggan', 3],
            ['pelunasan',       'Pelunasan dilakukan segera setelah draft hasil uji dikirimkan', 4],
            ['rekening',        'Pembayaran dapat dilakukan dengan tunai atau transfer bank ke {rekening:rekening}', 5],
            ['kontak',          'Informasi lebih lanjut dapat menghubungi {kontak:personel}', 6],
            // ── Varian lain (contoh penawaran no. 1 & 2) ──
            ['jadwal_sampling', 'Jadwal sampling ditetapkan setelah PO diterima laboratorium', 0],
            ['waktu_laporan',   'Estimasi lama pengerjaan adalah {hari_air:angka=5} hari kerja untuk pengujian air dan {hari_udara:angka=3} hari kerja untuk pengujian udara dan emisi setelah sampel diterima laboratorium', 0],
            ['dokumen_laporan', 'Draft Dokumen Laporan Implementasi RKL-RPL selesai paling lambat {bulan:angka=1} bulan setelah sampling, pendistribusian dilakukan setelah disetujui pelanggan', 0],
            ['dokumen_laporan', 'Draft Dokumen Laporan Implementasi RKL-RPL selesai paling lambat {bulan:angka=1} bulan setelah laporan hasil uji laboratorium, pendistribusian {buku:angka=2} buku dilakukan setelah disetujui pelanggan', 0],
            ['progres',         "Progres pekerjaan 100% adalah:\n- Laporan Hasil Uji Laboratorium diterima pelanggan\n- Buku Dokumen Asli Laporan Implementasi RKL-RPL diterima pelanggan sebanyak {eksemplar:angka=2} eksemplar", 0],
            ['pelunasan',       'Pelunasan dilakukan {hari:angka=60} hari kalender setelah Invoice dinyatakan lengkap', 0],
            ['pelunasan',       'Termin pembayaran {hari:angka=14} hari kalender setelah invoice diterima', 0],
        ];
        foreach ($rows as [$kategori, $template, $urutan]) {
            DB::table('poin_keterangan')->insert([
                'kategori'       => $kategori,
                'template'       => $template,
                'is_default'     => $urutan > 0 ? 1 : 0,
                'urutan_default' => $urutan,
                'is_aktif'       => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        foreach ([
            ['menu_group_permissions', 'menu_group_id'],
            ['user_menu_permissions', 'user_id'],
        ] as [$tbl, $col]) {
            DB::table($tbl)
                ->select($col, 'can_read', 'can_create', 'can_update', 'can_delete')
                ->where('menu_slug', 'satuan')
                ->get()
                ->each(function ($row) use ($tbl, $col) {
                    DB::table($tbl)->updateOrInsert(
                        [$col => $row->$col, 'menu_slug' => 'poin-keterangan'],
                        [
                            'can_read'   => $row->can_read,
                            'can_create' => $row->can_create,
                            'can_update' => $row->can_update,
                            'can_delete' => $row->can_delete,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'poin-keterangan')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'poin-keterangan')->delete();
        Schema::dropIfExists('poin_keterangan');
    }
};
