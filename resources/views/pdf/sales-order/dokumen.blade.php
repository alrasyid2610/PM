@extends('pdf.layouts.document')

@section('title', 'Sales Order ' . $so->no_so)


@section('styles')
    .tbd { background: #fde047; color: #713f12; font-weight: 700; padding: 0 4px; border-radius: 2px; }
    .tbd-note { font-size: 10px; color: #92400e; }
    .doc-title { display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 4px 0; margin-bottom: 14px; }
    .doc-title h1 { font-size: 16px; font-weight: 700; }
    .doc-title .meta { font-size: 11px; line-height: 1.3; }
    .doc-title .meta td { padding: 0 0 0 14px; }
    h2.bab, h3.sub { break-after: avoid; page-break-after: avoid; }
    h2.bab  { font-size: 13px; font-weight: 700; color: #1f3864; margin: 14px 0 4px; }
    h3.sub  { font-size: 12px; font-weight: 700; color: #2f6fd6; margin: 12px 0 6px; }
    .bab-no { display: inline-block; width: 38px; }
    .info-table .label { width: 120px; }
    .grp { margin-bottom: 10px; }
    .tbl { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 8px; }
    .tbl th { border: 1px solid #333; padding: 3px 6px; text-align: center; font-weight: 700; }
    .tbl td { border: 1px solid #333; padding: 3px 6px; vertical-align: top; }
    .tbl .c { text-align: center; }
    .wo-head { font-weight: 700; margin: 8px 0 4px; }
    .keep { page-break-inside: avoid; }
    ul.dot { margin: 0 0 0 16px; padding: 0; }
@endsection

@section('content')
@php
    $tgl  = fn ($d) => $d ? \Carbon\Carbon::parse($d)->translatedFormat('j F Y') : '-';
    $tglS = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '-';
    $v    = fn ($x) => filled($x) ? e($x) : '-';
    $tbd  = fn ($catatan = '') => new \Illuminate\Support\HtmlString('<span class="tbd">??</span>' . ($catatan ? ' <span class="tbd-note">' . e($catatan) . '</span>' : ''));

    $pelanggan = brDisplayName($so->pel_entitas, $so->pel_nama);
    $delivery  = brDisplayName($so->del_entitas, $so->del_nama);
    $payment   = brDisplayName($so->pay_entitas, $so->pay_nama);

    $mktInt = $users->get($so->pic_marketing_internal);
    $picIn  = $users->get($so->pic_input);
    $picOrd = $users->get($so->pic_order);
    $mktEks = $users->get($so->pic_marketing_eksternal);
@endphp
<div class="page">

    <div class="doc-title">
        <h1>Sales Order</h1>
        <table class="meta">
            <tr><td style="font-style:italic;font-weight:700;text-align:right;">No.</td><td style="font-weight:700;">{{ $so->no_so }}</td></tr>
            <tr><td style="font-style:italic;font-weight:700;text-align:right;">Tanggal</td><td style="font-weight:700;">{{ $tgl($so->tanggal_so) }}</td></tr>
        </table>
    </div>

    {{-- ========== 1 PELANGGAN ========== --}}
    <h2 class="bab"><span class="bab-no">1</span>Pelanggan</h2>
    <h3 class="sub"><span class="bab-no">1.1</span>Purchase Order</h3>

    <table class="info-table grp">
        <tr><td class="label">ID Pelanggan</td><td class="colon">:</td><td class="value"><strong>{{ $pelanggan ?: '-' }}</strong></td></tr>
        <tr><td class="label">Alamat</td><td class="colon">:</td><td class="value">{{ $so->pel_alamat ?: '-' }}</td></tr>
    </table>
    <table class="info-table grp">
        <tr><td class="label">No. PO</td><td class="colon">:</td><td class="value">{{ $so->no_po ?: '(Tidak ada PO)' }}</td></tr>
        <tr><td class="label">Judul PO</td><td class="colon">:</td><td class="value">{{ $so->judul_order ?: '-' }}</td></tr>
        <tr><td class="label">Tanggal PO</td><td class="colon">:</td><td class="value">{{ $tgl($so->tanggal_po) }}</td></tr>
        <tr><td class="label">U.P. PO</td><td class="colon">:</td><td class="value">{{ $so->pel_pic ?: '-' }}</td></tr>
        <tr><td class="label">No. Telepon</td><td class="colon">:</td><td class="value">{{ $so->pel_pic_telp ?: '-' }}</td></tr>
        <tr><td class="label">Email</td><td class="colon">:</td><td class="value">{{ $so->pel_pic_email ?: '-' }}</td></tr>
    </table>
    <table class="info-table grp">
        <tr>
            <td class="label">Pekerjaan</td><td class="colon">:</td>
            <td class="value">
                @forelse($wos as $i => $wo)
                    {{ $wos->count() > 1 ? ($i + 1) . '. ' : '' }}{{ $wo->judul_pekerjaan }}<br>
                @empty
                    -
                @endforelse
            </td>
        </tr>
        <tr><td class="label">Masa Pekerjaan</td><td class="colon">:</td><td class="value">{{ $tbd('belum ada di sistem (contoh: 12 hari kerja sesudah sampel diterima)') }}</td></tr>
        <tr><td class="label">Mulai</td><td class="colon">:</td><td class="value">{{ $tglS($so->tanggal_mulai) }}</td></tr>
        <tr><td class="label">Selesai</td><td class="colon">:</td><td class="value">{{ $tglS($so->tanggal_selesai) }}</td></tr>
        <tr><td class="label">Keterangan</td><td class="colon">:</td><td class="value">{!! filled($so->keterangan) ? nl2br(e($so->keterangan)) : '-' !!}</td></tr>
    </table>
    <table class="info-table grp">
        <tr><td class="label">No. Penawaran</td><td class="colon">:</td><td class="value">{{ $so->no_sq ?: '-' }}</td></tr>
        <tr><td class="label">Tgl. Penawaran</td><td class="colon">:</td><td class="value">{{ $tgl($so->tanggal_sq) }}</td></tr>
        <tr><td class="label">U.P. Penawaran</td><td class="colon">:</td><td class="value">{{ $so->sq_pic ?: '-' }}</td></tr>
    </table>

    <h3 class="sub keep"><span class="bab-no">1.2</span>Kontrak</h3>
    <table class="info-table grp keep">
        <tr><td class="label">No. Kontrak</td><td class="colon">:</td><td class="value">{{ $so->no_contract ?: '-' }}</td></tr>
        <tr><td class="label">Tanggal Kontrak</td><td class="colon">:</td><td class="value">{{ $tgl($so->tanggal_kontrak) }}</td></tr>
        <tr><td class="label">Masa Berlaku</td><td class="colon">:</td><td class="value">{{ $so->kontrak_mulai ? $tglS($so->kontrak_mulai) . ' s/d ' . $tglS($so->kontrak_selesai) : '-' }}</td></tr>
        <tr><td class="label">Keterangan</td><td class="colon">:</td><td class="value">{{ $so->kontrak_catatan ?: '-' }}</td></tr>
    </table>
    <table class="info-table grp keep">
        <tr><td class="label">U.P. Pelanggan</td><td class="colon">:</td><td class="value">{{ $so->pel_pic ?: '-' }}</td></tr>
        <tr><td class="label">No. Telepon</td><td class="colon">:</td><td class="value">{{ $so->pel_pic_telp ?: '-' }}</td></tr>
        <tr><td class="label">Email</td><td class="colon">:</td><td class="value">{{ $so->pel_pic_email ?: '-' }}</td></tr>
    </table>
    <table class="info-table grp keep">
        <tr><td class="label">U.P. Pramatek</td><td class="colon">:</td><td class="value">{{ $mktInt->name ?? '-' }}</td></tr>
        <tr><td class="label">No. Telepon</td><td class="colon">:</td><td class="value">{{ $mktInt->no_hp ?? '-' }}</td></tr>
        <tr><td class="label">Email</td><td class="colon">:</td><td class="value">{{ $mktInt->email ?? '-' }}</td></tr>
    </table>

    <div class="keep">
        <h3 class="sub"><span class="bab-no">1.3</span>Penanggung Jawab Pekerjaan</h3>
        <table class="tbl">
            <thead><tr><th>Personel Penghubung</th><th>Nama</th><th>Telepon</th><th>Email</th></tr></thead>
            <tbody>
                @foreach([['PIC Input', $picIn], ['PIC Order', $picOrd], ['Marketing Internal', $mktInt], ['Marketing Eksternal', $mktEks]] as [$peran, $u])
                    @if($u)
                        <tr><td>{{ $peran }}</td><td>{{ $u->name }}</td><td>{{ $u->no_hp ?: '-' }}</td><td>{{ $u->email ?: '-' }}</td></tr>
                    @endif
                @endforeach
                @foreach(['Admin 1', 'Admin 2', 'Admin 3'] as $adm)
                    <tr><td>{{ $adm }}</td><td>&nbsp;</td><td></td><td></td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ========== 2 PEKERJAAN ========== --}}
    <h2 class="bab"><span class="bab-no">2</span>Pekerjaan</h2>
    <h3 class="sub"><span class="bab-no">2.1</span>Output Pekerjaan</h3>
    <table class="info-table grp">
        <tr><td class="label">ID Pelanggan</td><td class="colon">:</td><td class="value"><strong>{{ $delivery ?: $pelanggan }}</strong></td></tr>
        <tr><td class="label">Alamat</td><td class="colon">:</td><td class="value">{{ $so->del_alamat ?: '-' }}</td></tr>
    </table>
    <table class="tbl">
        <thead><tr><th style="width:24%">Item</th><th style="width:30%">Judul Laporan</th><th style="width:22%">Alamat</th><th>Keterangan</th></tr></thead>
        <tbody>
            @forelse($outputs as $o)
                <tr>
                    <td>{{ $o->item }}</td>
                    <td>{{ $o->judul ?: '-' }}</td>
                    <td class="c">{{ $so->del_alamat ?: '-' }}</td>
                    <td>{{ $o->keterangan ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="c">Belum ada output pekerjaan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 class="sub keep"><span class="bab-no">2.2</span>Jangka Waktu Pekerjaan</h3>
    <table class="info-table grp keep">
        <tr><td class="label">Mulai Kegiatan</td><td class="colon">:</td><td class="value">{{ $tglS($so->tanggal_mulai) }}</td></tr>
        <tr><td class="label">Selesai Kegiatan</td><td class="colon">:</td><td class="value">{{ $tglS($so->tanggal_selesai) }}</td></tr>
    </table>
    <table class="tbl keep">
        <thead><tr><th>Agenda</th><th style="width:16%">Tenggat</th><th style="width:26%">Keterangan</th></tr></thead>
        <tbody>
            <tr><td colspan="3" class="c">{{ $tbd('tabel agenda & tenggat (sampling, registrasi sampel, analisis, draft, finalisasi, dst.) belum ada di sistem — tentukan sumbernya') }}</td></tr>
        </tbody>
    </table>

    <h3 class="sub keep"><span class="bab-no">2.3</span>Agenda Sampling</h3>
    @forelse($fwos as $f)
        <div class="keep grp">
            @if($wos->count() > 1)
                <div class="wo-head">{{ optional($wos->firstWhere('id_wo', $f->id_wo))->no_wo }} — {{ $f->no_fwo }}</div>
            @endif
            <table class="info-table">
                <tr><td class="label">Lokasi</td><td class="colon">:</td><td class="value"><strong>{{ $f->site ?: '-' }}</strong><br>{{ $f->alamat }}</td></tr>
                <tr><td class="label">Mulai</td><td class="colon">:</td><td class="value">{{ $tgl($f->tanggal_mulai) }}</td></tr>
                <tr><td class="label">Selesai</td><td class="colon">:</td><td class="value">{{ $tgl($f->tanggal_selesai) }}</td></tr>
                <tr><td class="label">Keterangan</td><td class="colon">:</td><td class="value">{{ $f->keterangan ?: '-' }}</td></tr>
                <tr><td class="label">U.P. Pelanggan</td><td class="colon">:</td><td class="value">{{ $f->nama_pic ?: '-' }}</td></tr>
                <tr><td class="label">No. Telepon</td><td class="colon">:</td><td class="value">{{ $f->nomor_telepon_pic ?: '-' }}</td></tr>
                <tr><td class="label">Email</td><td class="colon">:</td><td class="value">{{ $f->email_pic ?: '-' }}</td></tr>
                <tr><td class="label">U.P. Sampling</td><td class="colon">:</td><td class="value">{{ $f->penyelia ?: '-' }}</td></tr>
                <tr><td class="label">No. Telepon</td><td class="colon">:</td><td class="value">{{ $f->penyelia_hp ?: '-' }}</td></tr>
                <tr><td class="label">Email</td><td class="colon">:</td><td class="value">{{ $f->penyelia_email ?: '-' }}</td></tr>
                <tr><td class="label">Anggota Tim</td><td class="colon">:</td><td class="value">{{ ($tim[$f->id_fwo] ?? collect())->pluck('nama')->implode(', ') ?: '-' }}</td></tr>
            </table>
        </div>
    @empty
        <p>Belum ada FWO untuk SO ini.</p>
    @endforelse

    <h3 class="sub keep"><span class="bab-no">2.4</span>Pekerjaan Sampling</h3>
    @foreach($wos as $wo)
        <div class="keep">
            <table class="info-table grp">
                <tr><td class="label">ID Pelanggan</td><td class="colon">:</td><td class="value"><strong>{{ $wo->site ?: '-' }}</strong></td></tr>
                <tr><td class="label">Lokasi</td><td class="colon">:</td><td class="value">{{ $wo->alamat ?: '-' }}</td></tr>
                <tr><td class="label">Judul Pekerjaan</td><td class="colon">:</td><td class="value">{{ $wo->no_wo }} — {{ $wo->judul_pekerjaan }}</td></tr>
            </table>
            <table class="tbl">
                <thead><tr><th>Deskripsi Item</th><th style="width:30%">Regulasi</th><th style="width:10%">Jumlah</th><th style="width:10%">Unit</th></tr></thead>
                <tbody>
                    @foreach(($boq[$wo->id_wo] ?? collect()) as $b)
                        <tr>
                            <td><strong><u>{{ $b->matriks ?: '-' }}</u></strong><br>{{ $b->testing_point }}</td>
                            <td class="c">{{ trim(($b->standard_nomor ?? '') . ' ' . ($b->standard_judul ?? '')) ?: '-' }}</td>
                            <td class="c">{{ rtrim(rtrim(number_format((float) $b->qty, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="c">{{ $b->satuan ?: '-' }}</td>
                        </tr>
                    @endforeach
                    @foreach(($tambahan[$wo->id_wo] ?? collect()) as $t)
                        <tr>
                            <td>{{ $t->nama_item }}</td>
                            <td class="c">-</td>
                            <td class="c">{{ rtrim(rtrim(number_format((float) $t->qty, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="c">{{ $t->satuan ?: '-' }}</td>
                        </tr>
                    @endforeach
                    @if(($boq[$wo->id_wo] ?? collect())->isEmpty() && ($tambahan[$wo->id_wo] ?? collect())->isEmpty())
                        <tr><td colspan="4" class="c">Belum ada item pekerjaan.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach

    {{-- ========== 3 PENAGIHAN ========== --}}
    <h2 class="bab"><span class="bab-no">3</span>Penagihan</h2>
    <table class="info-table grp">
        <tr><td class="label">Nama</td><td class="colon">:</td><td class="value">{{ $payment ?: '-' }}</td></tr>
        <tr><td class="label">NPWP</td><td class="colon">:</td><td class="value">{{ $so->pay_npwp ?: '-' }}</td></tr>
        <tr><td class="label">ID NPWP</td><td class="colon">:</td><td class="value">{{ $tbd('belum ada di sistem') }}</td></tr>
        <tr><td class="label">Alamat NPWP</td><td class="colon">:</td><td class="value">{{ $so->pay_npwp_alamat ?: '-' }}</td></tr>
    </table>
    <table class="info-table grp">
        <tr>
            <td class="label">Termin</td><td class="colon">:</td>
            <td class="value">
                @forelse($termins as $t)
                    {{ $loop->iteration }}. {{ $t->nama ?: $t->no_termin }}{{ $t->is_dp ? ' (DP)' : '' }}{{ $t->persentase !== null ? ' — ' . rtrim(rtrim(number_format((float) $t->persentase, 2, ',', '.'), '0'), ',') . '%' : '' }}{{ $t->tanggal ? ' — ' . $tglS($t->tanggal) : '' }}<br>
                @empty
                    {{ $so->cara_pembayaran ?: '-' }}
                @endforelse
            </td>
        </tr>
        <tr><td class="label">Cara Pembayaran</td><td class="colon">:</td><td class="value">{{ $so->cara_pembayaran ?: '-' }}</td></tr>
        <tr><td class="label">Kelengkapan Data</td><td class="colon">:</td><td class="value">{{ $tbd('belum ada di sistem (dokumen yang harus dilampirkan saat penagihan)') }}</td></tr>
        <tr>
            <td class="label">Rek. Pembayaran</td><td class="colon">:</td>
            <td class="value">
                @forelse($rekening as $r)
                    {{ \App\Support\SqKeterangan::formatRekening($r) }}{{ $r->is_ppn ? ' (Rekening PPN)' : '' }}{{ $r->is_utama ? ' (Utama)' : '' }}<br>
                @empty
                    -
                @endforelse
                {{ $tbd('rekening mana yang dicetak: semua aktif / hanya utama / pilihan per SO') }}
            </td>
        </tr>
    </table>
    <table class="info-table grp keep">
        <tr><td class="label">U.P. Invoice</td><td class="colon">:</td><td class="value">{{ $so->pay_pic ?: '-' }}</td></tr>
        <tr><td class="label">Alamat Pengiriman</td><td class="colon">:</td><td class="value">{{ $so->pay_alamat ?: '-' }}</td></tr>
        <tr><td class="label">No. Telepon</td><td class="colon">:</td><td class="value">{{ $so->pay_pic_telp ?: '-' }}</td></tr>
        <tr><td class="label">Email</td><td class="colon">:</td><td class="value">{{ $so->pay_pic_email ?: '-' }}</td></tr>
    </table>
    <table class="info-table grp keep">
        <tr><td class="label">U.P. PPh 23</td><td class="colon">:</td><td class="value">&nbsp;</td></tr>
        <tr><td class="label">No. Telepon</td><td class="colon">:</td><td class="value">&nbsp;</td></tr>
        <tr><td class="label">Email</td><td class="colon">:</td><td class="value">&nbsp;</td></tr>
    </table>

    {{-- ========== 4 DISTRIBUSI DOKUMEN ========== --}}
    <h2 class="bab keep" style="margin-top:22px;"><span class="bab-no">4</span>Distribusi Dokumen Hasil Pekerjaan</h2>
    <div class="keep">
        <h3 class="sub"><span class="bab-no">4.1</span>Daftar Personel Penghubung Pelanggan</h3>
        <table class="tbl">
            <thead><tr><th>Nama</th><th style="width:16%">Jabatan</th><th>Email</th><th style="width:18%">Telpon</th></tr></thead>
            <tbody>
                @forelse($kontak as $k)
                    <tr><td class="c">{{ $k['nama'] }}</td><td class="c">{{ $k['jabatan'] ?: '-' }}</td><td class="c">{{ $k['email'] ?: '-' }}</td><td class="c">{{ $k['telp'] ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="c">-</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="keep">
        <h3 class="sub"><span class="bab-no">4.2</span>Daftar Dokumen dan Penerima Hasil Pekerjaan {{ $tbd('diturunkan dari Output Pekerjaan; format final belum ditentukan') }}</h3>
        <table class="tbl">
            <thead><tr><th style="width:28%">Dokumen</th><th style="width:24%">PIC</th><th>Lokasi</th></tr></thead>
            <tbody>
                @forelse($outputs as $o)
                    <tr>
                        <td>{{ $o->item }}</td>
                        <td>{{ $so->del_pic ?: '-' }}</td>
                        <td>
                            @if($o->link) Email/Drive/Cloud
                            @else <strong>{{ $delivery ?: $pelanggan }}</strong><br>{{ $so->del_alamat ?: '-' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="c">-</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
