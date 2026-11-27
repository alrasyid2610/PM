<?php

namespace App\Support\DataImport;

use Illuminate\Support\Facades\DB;

/**
 * Import Site (tabel `business_relation_sites`) untuk Perusahaan (Business Relation) yang SUDAH ADA.
 * Beda dari SamplingPointImport: TIDAK ada konteks yang dipilih di muka — tiap baris punya kolom
 * sendiri "Nama Perusahaan" (+ "NPWP Perusahaan" opsional untuk memperjelas kalau nama kembar) yang
 * di-resolve ke 1 `business_relations.id_br` per baris, jadi 1 file bisa memuat Site untuk banyak
 * Perusahaan sekaligus (keputusan user 2026-11-21).
 *
 * Aturan sama dengan input manual `BusinessRelationController::storeSite()`: Nama Site wajib, unik di
 * dalam 1 Perusahaan (di antara Site yang belum dihapus, tidak peka huruf besar-kecil). Nama Site yang
 * sudah ada di Perusahaan itu DILEWATI (tidak menimpa data lama). Koordinat opsional, format sama
 * dengan Sampling ENV/WE (desimal atau derajat-menit-detik, lihat CoordParser).
 *
 * "Nama Perusahaan" dicocokkan ke DUA bentuk (fix 2026-11-22, ditemukan lewat uji user): nama MENTAH
 * `business_relations.nama` (mis. "DWIBINA PRIMA", TANPA prefix badan usaha) dan nama TAMPILAN gabungan
 * Entitas+Nama lewat `brDisplayName()` (mis. "PT DWIBINA PRIMA" — yang terlihat di seluruh UI lain).
 * User wajar mengetik bentuk tampilan karena itu yang mereka lihat; mencocokkan hanya ke nama mentah
 * (versi awal) membuat semua Perusahaan berprefiks PT/CV/FA/KOP gagal ditemukan.
 *
 * Provinsi/Kota/Kecamatan/Kelurahan divalidasi berjenjang ke master `wilayah` lewat `WilayahResolver`
 * (sejak 2026-11-22, permintaan user demi integritas data — beda dari `storeSite()` manual yang sama
 * sekali tidak memvalidasi wilayah): boleh kosong semua, tapi kalau diisi harus cocok DAN sesuai
 * hierarki induknya (tidak boleh isi Kecamatan tanpa Kota, dst), disimpan dengan ejaan resmi master.
 */
class BrSiteImport implements ImportDefinition
{
    public function key(): string { return 'br-site'; }

    public function label(): string { return 'Site Business Relation'; }

    public function note(): ?string
    {
        return 'Site dibuat untuk Perusahaan yang SUDAH ADA — dicari dari kolom "Nama Perusahaan" (boleh pakai nama yang terlihat di layar, mis. "PT ABC", maupun nama tanpa PT/CV/dst). '
            . '1 file bisa memuat Site untuk banyak Perusahaan. Kalau ada Perusahaan dengan nama kembar, isi kolom "NPWP Perusahaan" untuk memperjelas. '
            . 'Nama Site yang sudah ada di Perusahaan itu akan dilewati.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'nama_perusahaan', 'label' => 'Nama Perusahaan', 'required' => true, 'width' => 32, 'type' => 'String — harus sama persis dgn BR yg sudah ada'],
            ['key' => 'npwp_perusahaan', 'label' => 'NPWP Perusahaan', 'required' => false, 'width' => 24, 'type' => 'String (25) — isi kalau Nama Perusahaan kembar'],
            ['key' => 'nama_site', 'label' => 'Nama Site', 'required' => true, 'width' => 30, 'type' => 'String (255)'],
            ['key' => 'kantor_pusat', 'label' => 'Kantor Pusat', 'required' => false, 'width' => 14, 'type' => 'Ya / Tidak'],
            ['key' => 'alamat_lengkap', 'label' => 'Alamat Lengkap', 'required' => false, 'width' => 40, 'type' => 'Teks bebas'],
            ['key' => 'nama_jalan', 'label' => 'Nama Jalan', 'required' => false, 'width' => 28, 'type' => 'Teks bebas'],
            ['key' => 'provinsi', 'label' => 'Provinsi', 'required' => false, 'width' => 20, 'type' => 'Harus cocok master wilayah'],
            ['key' => 'kota_kabupaten', 'label' => 'Kota/Kabupaten', 'required' => false, 'width' => 20, 'type' => 'Harus cocok master wilayah, anak dari Provinsi'],
            ['key' => 'kecamatan', 'label' => 'Kecamatan', 'required' => false, 'width' => 20, 'type' => 'Harus cocok master wilayah, anak dari Kota'],
            ['key' => 'kelurahan', 'label' => 'Kelurahan', 'required' => false, 'width' => 20, 'type' => 'Harus cocok master wilayah, anak dari Kecamatan'],
            ['key' => 'kode_pos', 'label' => 'Kode Pos', 'required' => false, 'width' => 12, 'type' => 'String (10)'],
            ['key' => 'kawasan_bisnis', 'label' => 'Kawasan Bisnis', 'required' => false, 'width' => 24, 'type' => 'String (255)'],
            ['key' => 'gedung', 'label' => 'Gedung', 'required' => false, 'width' => 24, 'type' => 'String (255)'],
            ['key' => 'keterangan_alamat', 'label' => 'Keterangan Alamat', 'required' => false, 'width' => 28, 'type' => 'String (255)'],
            ['key' => 'npwp_cabang', 'label' => 'NPWP Cabang', 'required' => false, 'width' => 22, 'type' => 'String (25)'],
            ['key' => 'latitude', 'label' => 'Latitude', 'required' => false, 'width' => 16, 'type' => 'Desimal / DMS (-90 s/d 90)'],
            ['key' => 'longitude', 'label' => 'Longitude', 'required' => false, 'width' => 16, 'type' => 'Desimal / DMS (-180 s/d 180)'],
            ['key' => 'aktif', 'label' => 'Aktif', 'required' => false, 'width' => 12, 'type' => 'Ya / Tidak'],
        ];
    }

    public function rules(): array
    {
        return [
            'Kolom bertanda * (Nama Perusahaan, Nama Site) wajib diisi.',
            'Nama Perusahaan harus sama persis (tanpa peka huruf besar-kecil) dengan nama Business Relation yang sudah ada di sistem — boleh pakai nama yang terlihat di layar (mis. "PT ABC") maupun nama tanpa PT/CV/FA/KOP. Baris tidak ditemukan akan error, Site TIDAK dibuat otomatis untuk Perusahaan baru.',
            'Kalau ada beberapa Perusahaan dengan nama yang sama, isi kolom NPWP Perusahaan (harus cocok) untuk memperjelas yang mana.',
            '1 file boleh memuat Site untuk banyak Perusahaan berbeda — isi Nama Perusahaan per baris.',
            'Nama Site harus unik di dalam 1 Perusahaan. Nama yang sudah ada di Perusahaan itu, atau ganda di dalam file untuk Perusahaan yang sama, akan DILEWATI — bukan menimpa data lama.',
            'Provinsi/Kota/Kabupaten/Kecamatan/Kelurahan boleh dikosongkan semua, tapi kalau diisi harus cocok dengan data wilayah resmi (tidak peka huruf besar-kecil/tanda baca) DAN mengikuti hierarki (Kota harus bagian dari Provinsi yang diisi, Kecamatan dari Kota, Kelurahan dari Kecamatan) — tidak boleh mengisi satu level tanpa mengisi level di atasnya. Tidak cocok/urutan tidak lengkap akan error.',
            'Latitude dan Longitude boleh dikosongkan, tapi kalau salah satu diisi, pasangannya wajib diisi. Latitude -90 s/d 90, Longitude -180 s/d 180.',
            'Format koordinat boleh DESIMAL (-6.2088 atau -6,2088) atau DERAJAT-MENIT-DETIK (6°12\'31.7"S, 106°13\'8.6"E) — otomatis dikonversi ke desimal saat disimpan.',
            'Kolom Kantor Pusat dan Aktif: isi Ya atau Tidak. Kosong pada Kantor Pusat dianggap Tidak; kosong pada Aktif dianggap Ya.',
            'Sheet "Data": baris 1 = nama kolom, baris 2 = tipe data, baris 3 = contoh. Ketiga baris itu JANGAN dihapus atau diubah — isi data mulai dari baris 4 ke bawah.',
            'Jangan mengubah sheet "Info" dan jangan mengganti nama sheet "Data".',
            'Maksimal 2.000 baris per file.',
        ];
    }

    public function example(): array
    {
        return [
            'PT Contoh Sejahtera', '01.234.567.8-901.000', 'Kantor Cabang Bandung', 'Tidak',
            'Jl. Contoh No. 1', 'Jl. Contoh', 'Jawa Barat', 'Kota Bandung', 'Coblong', 'Dago', '40135',
            'Kawasan Industri ABC', 'Gedung Utama', 'Dekat pintu masuk', '02.345.678.9-012.000',
            -6.9039, 107.6186, 'Ya',
        ];
    }

    public function contexts(): array { return []; }

    public function resolveContext(array $input): array|string
    {
        return ['values' => [], 'info' => []];
    }

    public function prepare(array $context): array
    {
        // Dicocokkan ke nama MENTAH (business_relations.nama, mis. "DWIBINA PRIMA") DAN nama tampilan
        // gabungan Entitas+Nama (brDisplayName(), mis. "PT DWIBINA PRIMA" — yang terlihat di seluruh UI)
        // supaya kedua cara ketik diterima.
        $byName = [];
        $rows = DB::table('business_relations as br')
            ->leftJoin('entitas as e', 'e.id_entitas', '=', 'br.id_entitas')
            ->whereNull('br.deleted_at')
            ->get(['br.id_br', 'br.nama', 'br.npwp', 'e.nama as entitas']);
        foreach ($rows as $r) {
            $kandidat = ['id_br' => (int) $r->id_br, 'npwp' => (string) $r->npwp];
            $namaMentah = mb_strtolower(trim((string) $r->nama));
            $namaTampilan = mb_strtolower(trim(brDisplayName($r->entitas, $r->nama)));
            $byName[$namaMentah][] = $kandidat;
            if ($namaTampilan !== $namaMentah) $byName[$namaTampilan][] = $kandidat;
        }

        $adaSite = [];
        foreach (DB::table('business_relation_sites')->whereNull('deleted_at')->get(['id_br', 'nama_lokasi']) as $r) {
            $adaSite[$r->id_br . '|' . mb_strtolower(trim((string) $r->nama_lokasi))] = true;
        }

        return ['byName' => $byName, 'adaSite' => $adaSite, 'barisSite' => [], 'wilayahCache' => []];
    }

    public function summaryLabels(): array { return ['Perusahaan', 'Nama Site']; }

    public function summary(array $raw): array
    {
        return [trim((string) ($raw['nama_perusahaan'] ?? '')), trim((string) ($raw['nama_site'] ?? ''))];
    }

    public function validate(array $raw, array &$ctx): array
    {
        $gagal = fn(string $m) => ['status' => 'error', 'message' => $m, 'clean' => []];

        $namaPerusahaan = CoordParser::text($raw['nama_perusahaan'] ?? null);
        $npwpPerusahaan = $this->normNpwp(CoordParser::text($raw['npwp_perusahaan'] ?? null));
        $namaSite = CoordParser::text($raw['nama_site'] ?? null);

        if ($namaPerusahaan === '') return $gagal('Nama Perusahaan wajib diisi.');
        if ($namaSite === '') return $gagal('Nama Site wajib diisi.');
        if (mb_strlen($namaSite) > 255) return $gagal('Nama Site maksimal 255 karakter.');

        $semuaKandidat = $ctx['byName'][mb_strtolower($namaPerusahaan)] ?? [];
        if (!$semuaKandidat) {
            return $gagal('Perusahaan "' . $namaPerusahaan . '" tidak ditemukan. Pastikan nama sama persis dengan data Business Relation yang sudah ada — Site tidak bisa dibuat untuk Perusahaan baru lewat import ini.');
        }
        $kandidat = $semuaKandidat;
        if (count($kandidat) > 1 && $npwpPerusahaan !== '') {
            $kandidat = array_values(array_filter($kandidat, fn($k) => $this->normNpwp($k['npwp']) === $npwpPerusahaan));
        }
        if (count($kandidat) !== 1) {
            return $gagal('Ada ' . count($semuaKandidat) . ' Perusahaan bernama "' . $namaPerusahaan . '". Isi kolom NPWP Perusahaan yang benar untuk memperjelas.');
        }
        $idBr = $kandidat[0]['id_br'];

        $alamatLengkap = CoordParser::text($raw['alamat_lengkap'] ?? null) ?: null;
        $namaJalan = CoordParser::text($raw['nama_jalan'] ?? null) ?: null;
        $kodePos = CoordParser::text($raw['kode_pos'] ?? null) ?: null;
        $kawasanBisnis = CoordParser::text($raw['kawasan_bisnis'] ?? null) ?: null;
        $gedung = CoordParser::text($raw['gedung'] ?? null) ?: null;
        $keteranganAlamat = CoordParser::text($raw['keterangan_alamat'] ?? null) ?: null;
        $npwpCabang = CoordParser::text($raw['npwp_cabang'] ?? null) ?: null;

        foreach ([
            ['Kode Pos', $kodePos, 10], ['Kawasan Bisnis', $kawasanBisnis, 255],
            ['Gedung', $gedung, 255], ['Keterangan Alamat', $keteranganAlamat, 255], ['NPWP Cabang', $npwpCabang, 25],
        ] as [$label, $val, $max]) {
            if ($val !== null && mb_strlen($val) > $max) return $gagal($label . ' maksimal ' . $max . ' karakter.');
        }

        // Provinsi/Kota/Kecamatan/Kelurahan divalidasi berjenjang ke master `wilayah` (lihat WilayahResolver):
        // boleh kosong semua, tapi kalau diisi harus cocok DAN sesuai hierarki induknya; disimpan dengan
        // ejaan resmi master (bukan ketikan file).
        $wilayah = WilayahResolver::resolve(
            CoordParser::text($raw['provinsi'] ?? null),
            CoordParser::text($raw['kota_kabupaten'] ?? null),
            CoordParser::text($raw['kecamatan'] ?? null),
            CoordParser::text($raw['kelurahan'] ?? null),
            $ctx['wilayahCache']
        );
        if (is_string($wilayah)) return $gagal($wilayah);
        ['provinsi' => $provinsi, 'kota' => $kota, 'kecamatan' => $kecamatan, 'kelurahan' => $kelurahan] = $wilayah;

        $kantorPusat = CoordParser::yaTidak($raw['kantor_pusat'] ?? null, 0);
        if ($kantorPusat === null) return $gagal('Kolom Kantor Pusat harus diisi Ya atau Tidak.');

        $aktif = CoordParser::yaTidak($raw['aktif'] ?? null, 1);
        if ($aktif === null) return $gagal('Kolom Aktif harus diisi Ya atau Tidak.');

        $lat = CoordParser::koordinat($raw['latitude'] ?? null, 'lat', $latErr);
        $lng = CoordParser::koordinat($raw['longitude'] ?? null, 'lng', $lngErr);
        if ($latErr) return $gagal('Latitude: ' . $latErr . ' Pakai desimal (-6.2088) atau derajat (6°12\'31.7"S).');
        if ($lngErr) return $gagal('Longitude: ' . $lngErr . ' Pakai desimal (106.8456) atau derajat (106°13\'8.6"E).');
        if (($lat === null) !== ($lng === null)) return $gagal('Latitude dan Longitude harus diisi berpasangan.');
        if ($lat !== null && ($lat < -90 || $lat > 90)) return $gagal('Latitude harus antara -90 dan 90.');
        if ($lng !== null && ($lng < -180 || $lng > 180)) return $gagal('Longitude harus antara -180 dan 180.');

        // Duplikat: nama Site unik per Perusahaan (data lama, atau baris lain di file ini untuk Perusahaan yang sama)
        $kunci = $idBr . '|' . mb_strtolower($namaSite);
        if (isset($ctx['adaSite'][$kunci])) {
            return ['status' => 'skip', 'message' => 'Nama Site sudah ada di Perusahaan ini.', 'clean' => []];
        }
        if (isset($ctx['barisSite'][$kunci])) {
            return ['status' => 'skip', 'message' => 'Nama Site ganda di dalam file (sudah dipakai baris ' . $ctx['barisSite'][$kunci] . ' untuk Perusahaan yang sama).', 'clean' => []];
        }
        $ctx['barisSite'][$kunci] = $ctx['_baris'] ?? '?';

        return ['status' => 'valid', 'message' => null, 'clean' => [
            'id_br' => $idBr, 'nama_lokasi' => $namaSite, 'is_kantor_pusat' => $kantorPusat,
            'alamat_lengkap' => $alamatLengkap, 'provinsi' => $provinsi, 'kota_kabupaten' => $kota,
            'kecamatan' => $kecamatan, 'kelurahan' => $kelurahan, 'kode_pos' => $kodePos,
            'kawasan_bisnis' => $kawasanBisnis, 'gedung' => $gedung, 'keterangan_alamat' => $keteranganAlamat,
            'nama_jalan' => $namaJalan, 'npwp_cabang' => $npwpCabang, 'latitude' => $lat, 'longitude' => $lng,
            'is_aktif' => $aktif,
        ]];
    }

    public function insert(array $clean, array $context): int
    {
        $id = DB::table('business_relation_sites')->insertGetId([
            'id_br' => $clean['id_br'],
            'nama_lokasi' => $clean['nama_lokasi'],
            'is_kantor_pusat' => $clean['is_kantor_pusat'],
            'alamat_lengkap' => $clean['alamat_lengkap'],
            'provinsi' => $clean['provinsi'],
            'kota_kabupaten' => $clean['kota_kabupaten'],
            'kecamatan' => $clean['kecamatan'],
            'kelurahan' => $clean['kelurahan'],
            'kode_pos' => $clean['kode_pos'],
            'kawasan_bisnis' => $clean['kawasan_bisnis'],
            'gedung' => $clean['gedung'],
            'keterangan_alamat' => $clean['keterangan_alamat'],
            'nama_jalan' => $clean['nama_jalan'],
            'npwp_cabang' => $clean['npwp_cabang'],
            'latitude' => $clean['latitude'],
            'longitude' => $clean['longitude'],
            'is_aktif' => $clean['is_aktif'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        saveAudit('business_relation_sites', $id, 'Create', '', DB::table('business_relation_sites')->where('id_site', $id)->get()->toJson());

        return $id;
    }

    /** Hanya dipakai untuk mencocokkan NPWP secara longgar (titik/strip/spasi diabaikan). */
    private function normNpwp(string $v): string
    {
        return preg_replace('/[^0-9A-Za-z]/', '', $v);
    }
}
