<?php

use App\Support\DocumentNumber;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lab Sample Reg dipisah dari Work Order menjadi menu sendiri (grup "Lab", slug `lab-sample-regs`).
 *
 * 1. `wo_lab_sample_regs.no_registrasi` — nomor dokumen per pendaftaran, format `LSR.YY.HURUF.NNNN`
 *    (lewat DocumentNumber). Pendaftaran lama diberi nomor urut sesuai id, dengan tahun dari `created_at`.
 * 2. Hak akses menu baru disalin dari `work-orders` (baris izin yang sama: CRUD), HANYA untuk grup/user yang
 *    sebelumnya boleh membuka tab "Lab Sample Reg" di WO (aturan Akses Tab: tidak punya satu pun slug `wo-tab-*`
 *    → semua tab boleh; punya ≥ 1 → hanya tab yang dicentang `wo-tab-lab-sample-reg`).
 *    Slug tab `wo-tab-lab-sample-reg` tidak diubah — sekarang mengatur tab read-only di detail WO.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('wo_lab_sample_regs', 'no_registrasi')) {
            Schema::table('wo_lab_sample_regs', function (Blueprint $table) {
                $table->string('no_registrasi', 50)->nullable()->after('id_wo_lab_sample_reg');
            });
        }

        // Backfill pendaftaran lama (urut id; tahun dari created_at supaya nomor sejalan dengan tahun pendaftaran)
        $regs = DB::table('wo_lab_sample_regs')->whereNull('no_registrasi')->orderBy('id_wo_lab_sample_reg')
            ->get(['id_wo_lab_sample_reg', 'created_at']);
        foreach ($regs as $reg) {
            $no = DocumentNumber::next('wo_lab_sample_regs', 'no_registrasi', 'LSR', $reg->created_at ? Carbon::parse($reg->created_at) : null);
            DB::table('wo_lab_sample_regs')->where('id_wo_lab_sample_reg', $reg->id_wo_lab_sample_reg)->update(['no_registrasi' => $no]);
        }

        if (!Schema::hasIndex('wo_lab_sample_regs', 'wo_lab_sample_regs_no_registrasi_unique')) {
            Schema::table('wo_lab_sample_regs', function (Blueprint $table) {
                $table->unique('no_registrasi', 'wo_lab_sample_regs_no_registrasi_unique');
            });
        }

        $this->salinAkses('menu_group_permissions', 'menu_group_id');
        $this->salinAkses('user_menu_permissions', 'user_id');
    }

    /** Salin izin `work-orders` → `lab-sample-regs` untuk pemilik (grup/user) yang boleh membuka tab Lab Sample Reg. */
    private function salinAkses(string $tabel, string $kolomPemilik): void
    {
        $pemilik = DB::table($tabel)->where('menu_slug', 'work-orders')
            ->get([$kolomPemilik, 'can_read', 'can_create', 'can_update', 'can_delete']);

        foreach ($pemilik as $row) {
            $id = $row->{$kolomPemilik};

            $tabAktif = DB::table($tabel)->where($kolomPemilik, $id)
                ->where('menu_slug', 'like', 'wo-tab-%')->where('can_update', 1)->pluck('menu_slug');
            $boleh = $tabAktif->isEmpty() || $tabAktif->contains('wo-tab-lab-sample-reg');
            if (!$boleh) continue;

            DB::table($tabel)->updateOrInsert(
                [$kolomPemilik => $id, 'menu_slug' => 'lab-sample-regs'],
                [
                    'can_read'   => $row->can_read,
                    'can_create' => $row->can_create,
                    'can_update' => $row->can_update,
                    'can_delete' => $row->can_delete,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'lab-sample-regs')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'lab-sample-regs')->delete();

        if (Schema::hasColumn('wo_lab_sample_regs', 'no_registrasi')) {
            Schema::table('wo_lab_sample_regs', function (Blueprint $table) {
                $table->dropUnique('wo_lab_sample_regs_no_registrasi_unique');
                $table->dropColumn('no_registrasi');
            });
        }
    }
};
