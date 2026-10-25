<?php

namespace App\Support\DataImport;

use Illuminate\Support\Facades\DB;

/**
 * Import Sampling Point (tabel `brs_sampling_points`) untuk 1 Site, jenis ENV atau WE.
 * Aturan sama dengan input manual `BrsSamplingPointController::store()`: kode & nama wajib, kode unik per site
 * (di antara yang belum dihapus, lintas ENV/WE), koordinat opsional tapi harus berpasangan, gedung/ruangan/lantai
 * hanya untuk WE. Kode yang sudah ada di site itu dilewati (bukan diperbarui).
 */
class SamplingPointImport implements ImportDefinition
{
    public function __construct(private string $jenis) {}

    public function key(): string { return 'sampling-' . $this->jenis; }

    public function label(): string { return $this->jenis === 'env' ? 'Sampling ENV (Titik Sampling Lingkungan)' : 'Sampling WE (Titik Sampling Lingkungan Kerja)'; }

    public function note(): ?string { return 'Titik sampling diimpor ke 1 Site yang dipilih. Kode yang sudah ada di site itu dilewati.'; }

    public function columns(): array
    {
        $cols = [
            ['key' => 'kode', 'label' => 'Kode', 'required' => true, 'width' => 18, 'type' => 'String (255)'],
            ['key' => 'nama', 'label' => 'Nama', 'required' => true, 'width' => 34, 'type' => 'String (255)'],
            ['key' => 'latitude', 'label' => 'Latitude', 'required' => false, 'width' => 16, 'type' => 'Desimal / DMS (-90 s/d 90)'],
            ['key' => 'longitude', 'label' => 'Longitude', 'required' => false, 'width' => 16, 'type' => 'Desimal / DMS (-180 s/d 180)'],
        ];
        if ($this->jenis === 'we') {
            $cols[] = ['key' => 'gedung', 'label' => 'Gedung', 'required' => false, 'width' => 20, 'type' => 'String (255)'];
            $cols[] = ['key' => 'ruangan', 'label' => 'Ruangan', 'required' => false, 'width' => 20, 'type' => 'String (255)'];
            $cols[] = ['key' => 'lantai', 'label' => 'Lantai', 'required' => false, 'width' => 12, 'type' => 'String (50)'];
        }
        $cols[] = ['key' => 'keterangan', 'label' => 'Keterangan', 'required' => false, 'width' => 34, 'type' => 'Teks bebas'];
        $cols[] = ['key' => 'aktif', 'label' => 'Aktif', 'required' => false, 'width' => 12, 'type' => 'Ya / Tidak'];
        return $cols;
    }

    public function rules(): array
    {
        $r = [
            'Kolom bertanda * (Kode, Nama) wajib diisi.',
            'Kode harus unik di dalam 1 site (ENV dan WE dianggap satu daftar). Kode yang sudah ada di site ini atau ganda di dalam file akan DILEWATI, bukan menimpa data lama.',
            'Latitude dan Longitude boleh dikosongkan, tapi kalau salah satu diisi, pasangannya wajib diisi. Latitude -90 s/d 90, Longitude -180 s/d 180.',
            'Format koordinat boleh DESIMAL (-6.2088 atau -6,2088) atau DERAJAT-MENIT-DETIK (6°12\'31.7"S, 6 12 31.7 S, 106°13\'8.6"E) — otomatis dikonversi ke desimal saat disimpan. Arah S/W (atau LS/BB) berarti negatif; Latitude memakai N/S (LU/LS), Longitude memakai E/W (BT/BB).',
            'Kolom Aktif: isi Ya atau Tidak. Kosong dianggap Ya.',
            'Sheet "Data": baris 1 = nama kolom, baris 2 = tipe data, baris 3 = contoh. Ketiga baris itu JANGAN dihapus atau diubah — isi data mulai dari baris 4 ke bawah.',
            'Jangan mengubah sheet "Info" dan jangan mengganti nama sheet "Data" — file dicocokkan dengan Site yang dipilih.',
            'Maksimal 2.000 baris per file.',
        ];
        if ($this->jenis === 'we') {
            array_splice($r, 3, 0, ['Gedung, Ruangan, dan Lantai hanya untuk Sampling WE (Lantai maks. 50 karakter).']);
        }
        return $r;
    }

    public function example(): array
    {
        return $this->jenis === 'we'
            ? ['SP-001', 'Ruang Produksi 1', -6.2088, 106.8456, 'Gedung Utama', 'Produksi', '2', 'Dekat mesin 3', 'Ya']
            : ['SP-001', 'Titik Utara Pagar', -6.2088, 106.8456, 'Dekat gerbang', 'Ya'];
    }

    public function contexts(): array
    {
        return [['key' => 'id_site', 'label' => 'Site', 'source' => 'sites']];
    }

    public function resolveContext(array $input): array|string
    {
        $idSite = (int) ($input['id_site'] ?? 0);
        $row = $idSite ? DB::table('business_relation_sites as s')
            ->join('business_relations as br', 'br.id_br', '=', 's.id_br')
            ->leftJoin('entitas as e', 'e.id_entitas', '=', 'br.id_entitas')
            ->where('s.id_site', $idSite)->whereNull('s.deleted_at')
            ->first(['s.id_site', 's.nama_lokasi', 'br.nama as nama_br', 'e.nama as entitas']) : null;
        if (!$row) return 'Site tidak ditemukan.';

        return [
            'values' => ['id_site' => (int) $row->id_site],
            'info'   => ['Perusahaan' => brDisplayName($row->entitas, $row->nama_br), 'Site' => $row->nama_lokasi],
        ];
    }

    public function prepare(array $context): array
    {
        $ada = DB::table('brs_sampling_points')->where('id_site', $context['id_site'])->whereNull('deleted_at')
            ->pluck('kode')->map(fn($k) => mb_strtolower(trim((string) $k)))->flip()->all();

        return ['ada' => $ada, 'baris_kode' => []];
    }

    public function summaryLabels(): array { return ['Kode', 'Nama']; }

    public function summary(array $raw): array
    {
        return [trim((string) ($raw['kode'] ?? '')), trim((string) ($raw['nama'] ?? ''))];
    }

    public function validate(array $raw, array &$ctx): array
    {
        $kode = $this->text($raw['kode'] ?? null);
        $nama = $this->text($raw['nama'] ?? null);

        $gagal = fn(string $m) => ['status' => 'error', 'message' => $m, 'clean' => []];

        if ($kode === '') return $gagal('Kode wajib diisi.');
        if (mb_strlen($kode) > 255) return $gagal('Kode maksimal 255 karakter.');
        if ($nama === '') return $gagal('Nama wajib diisi.');
        if (mb_strlen($nama) > 255) return $gagal('Nama maksimal 255 karakter.');

        $lat = $this->koordinat($raw['latitude'] ?? null, 'lat', $latErr);
        $lng = $this->koordinat($raw['longitude'] ?? null, 'lng', $lngErr);
        if ($latErr) return $gagal('Latitude: ' . $latErr . ' Pakai desimal (-6.2088) atau derajat (6°12\'31.7"S).');
        if ($lngErr) return $gagal('Longitude: ' . $lngErr . ' Pakai desimal (106.8456) atau derajat (106°13\'8.6"E).');
        if (($lat === null) !== ($lng === null)) return $gagal('Latitude dan Longitude harus diisi berpasangan.');
        if ($lat !== null && ($lat < -90 || $lat > 90)) return $gagal('Latitude harus antara -90 dan 90.');
        if ($lng !== null && ($lng < -180 || $lng > 180)) return $gagal('Longitude harus antara -180 dan 180.');

        $gedung = $ruangan = $lantai = null;
        if ($this->jenis === 'we') {
            $gedung  = $this->text($raw['gedung'] ?? null) ?: null;
            $ruangan = $this->text($raw['ruangan'] ?? null) ?: null;
            $lantai  = $this->text($raw['lantai'] ?? null) ?: null;
            if ($gedung !== null && mb_strlen($gedung) > 255) return $gagal('Gedung maksimal 255 karakter.');
            if ($ruangan !== null && mb_strlen($ruangan) > 255) return $gagal('Ruangan maksimal 255 karakter.');
            if ($lantai !== null && mb_strlen($lantai) > 50) return $gagal('Lantai maksimal 50 karakter.');
        }

        $aktifTeks = mb_strtolower($this->text($raw['aktif'] ?? null));
        if ($aktifTeks === '' || in_array($aktifTeks, ['ya', 'y', 'aktif', '1', 'true'], true)) {
            $aktif = 1;
        } elseif (in_array($aktifTeks, ['tidak', 'tdk', 't', 'nonaktif', 'non-aktif', 'tidak aktif', '0', 'false'], true)) {
            $aktif = 0;
        } else {
            return $gagal('Kolom Aktif harus diisi Ya atau Tidak.');
        }

        // Duplikat: dengan data yang sudah ada di site, atau dengan baris lain di file ini
        $kunci = mb_strtolower($kode);
        if (isset($ctx['ada'][$kunci])) {
            return ['status' => 'skip', 'message' => 'Kode sudah ada di site ini.', 'clean' => []];
        }
        if (isset($ctx['baris_kode'][$kunci])) {
            return ['status' => 'skip', 'message' => 'Kode ganda di dalam file (sudah dipakai baris ' . $ctx['baris_kode'][$kunci] . ').', 'clean' => []];
        }
        $ctx['baris_kode'][$kunci] = $ctx['_baris'] ?? '?';

        return ['status' => 'valid', 'message' => null, 'clean' => [
            'kode' => $kode, 'nama' => $nama, 'latitude' => $lat, 'longitude' => $lng,
            'gedung' => $gedung, 'ruangan' => $ruangan, 'lantai' => $lantai,
            'keterangan' => $this->text($raw['keterangan'] ?? null) ?: null, 'is_aktif' => $aktif,
        ]];
    }

    public function insert(array $clean, array $context): int
    {
        $id = DB::table('brs_sampling_points')->insertGetId([
            'id_site'    => $context['id_site'],
            'jenis'      => $this->jenis,
            'kode'       => $clean['kode'],
            'nama'       => $clean['nama'],
            'latitude'   => $clean['latitude'],
            'longitude'  => $clean['longitude'],
            'gedung'     => $clean['gedung'],
            'ruangan'    => $clean['ruangan'],
            'lantai'     => $clean['lantai'],
            'keterangan' => $clean['keterangan'],
            'is_aktif'   => $clean['is_aktif'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        saveAudit('brs_sampling_points', $id, 'Create', '', DB::table('brs_sampling_points')->where('id_sp', $id)->get()->toJson());

        return $id;
    }

    /** Sel Excel → teks rapi. Angka bulat (mis. kode 1 yang terbaca 1.0) tidak diberi ".0". */
    private function text($v): string
    {
        if ($v === null) return '';
        if (is_float($v) && floor($v) == $v && abs($v) < 1e15) return (string) (int) $v;
        return trim((string) $v);
    }

    /**
     * Koordinat → desimal (8 digit). Menerima desimal ("-6.2088", "-6,2088", angka Excel) atau derajat-menit-detik
     * ("6°12'31.7\"S", "6 12 31.7 S", "106°13'8.6\"E", "-6°12'31.7\"") dengan arah N/S/E/W atau LU/LS/BT/BB;
     * S/W (LS/BB) berarti negatif. Kosong → null tanpa error; tak terbaca → null + $err berisi alasan.
     */
    private function koordinat($v, string $jenis, ?string &$err): ?float
    {
        $err = null;
        if ($v === null || trim((string) $v) === '') return null;
        if (is_int($v) || is_float($v)) return round((float) $v, 8);

        $s = trim((string) $v);
        // Samakan simbol derajat/menit/detik "cantik" hasil copy dari web atau Excel
        $s = str_replace(['º', '˚', '′', '’', '‘', '`', '´', '″', '”', '“'], ['°', '°', "'", "'", "'", "'", "'", '"', '"', '"'], $s);
        $s = str_replace("''", '"', $s);

        $arah = null;
        $pola = '/(?<![A-Za-z])(LU|LS|BT|BB|N|S|E|W)(?![A-Za-z])/i';
        if (preg_match($pola, $s, $m)) {
            $arah = strtoupper($m[1]);
            $s = trim(preg_replace($pola, ' ', $s, 1));
        }
        if ($arah !== null) {
            $cocok = $jenis === 'lat' ? in_array($arah, ['N', 'S', 'LU', 'LS'], true) : in_array($arah, ['E', 'W', 'BT', 'BB'], true);
            if (!$cocok) {
                $err = $jenis === 'lat' ? 'arah harus U/S (N/S), bukan ' . $arah . '.' : 'arah harus T/B (E/W), bukan ' . $arah . '.';
                return null;
            }
        }
        $negArah = in_array($arah, ['S', 'LS', 'W', 'BB'], true);

        // Sisa harus hanya angka dan pemisah derajat/menit/detik, maksimal 3 angka (derajat, menit, detik)
        if (!preg_match_all('/\d+(?:[.,]\d+)?/', $s, $t) || preg_match('/[^\d\s.,°\'"+\-]/u', $s) || count($t[0]) > 3) {
            $err = 'format tidak dikenali.';
            return null;
        }
        $angka = array_map(fn($x) => (float) str_replace(',', '.', $x), $t[0]);
        [$d, $mnt, $dt] = [$angka[0], $angka[1] ?? 0.0, $angka[2] ?? 0.0];
        if (count($angka) > 1 && ($mnt >= 60 || $dt >= 60)) {
            $err = 'menit dan detik harus di bawah 60.';
            return null;
        }

        $nilai = $d + $mnt / 60 + $dt / 3600;
        // Minus di depan ATAU arah S/W sama-sama berarti negatif (keduanya sekaligus tidak saling meniadakan)
        if (preg_match('/^\s*-/', $s) === 1 || $negArah) $nilai = -$nilai;
        return round($nilai, 8);
    }
}
