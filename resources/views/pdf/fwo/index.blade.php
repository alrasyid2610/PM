@extends('pdf.layouts.document')

@section('title', 'Fieldwork Order')

@section('content')
    <style>
        .boq-table th {
            text-align: center;
        }
    </style>

    {{-- Halaman 1 --}}
    <div class="page">
        <h2 style="text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase;">
            Surat Tugas Pengambilan Sampel
        </h2>
        <p style="text-align: center;">Nomor : {{ $fwo->no_fwo }}</p>

        <p style="margin-top: 16px;">Sehubungan dengan adanya pekerjaan pengambilan sampel dan/atau pengukuran sebagaimana berikut:</p>

        <table class="info-table" style="margin-left: 6px;">
            <tr>
                <td class="label">Nomor Pekerjaan</td>
                <td class="colon">:</td>
                <td class="value">{{ $fwo->no_fwo ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nomor Sales Order</td>
                <td class="colon">:</td>
                <td class="value">{{ $fwo->no_so ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nomor PO/SPK</td>
                <td class="colon">:</td>
                <td class="value">{{ $fwo->no_po ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">ID Pelanggan</td>
                <td class="colon">:</td>
                <td class="value">{{ $fwo->nama_lokasi_wo ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat Pelanggan</td>
                <td class="colon">:</td>
                <td class="value">{{ $fwo->alamat_lengkap_wo ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Judul Pekerjaan</td>
                <td class="colon">:</td>
                <td class="value">{{ $fwo->judul_pekerjaan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Contact Person</td>
                <td class="colon">:</td>
                <td class="value">
                    @if($fwo->nama_pic)
                        {{ $fwo->nama_pic }}{{ $fwo->nomor_telepon_pic ? ' (' . $fwo->nomor_telepon_pic . ')' : '' }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Lokasi Pekerjaan</td>
                <td class="colon">:</td>
                <td class="value">
                    @if($fwo->nama_lokasi_fwo)
                        <strong>{{ $fwo->nama_lokasi_fwo }}</strong><br>
                        {{ $fwo->alamat_lengkap_fwo }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Tanggal</td>
                <td class="colon">:</td>
                <td class="value">{{ $fwo->tanggal_mulai ? \Carbon\Carbon::parse($fwo->tanggal_mulai)->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Jam Kedatangan</td>
                <td class="colon">:</td>
                <td class="value">{{ $fwo->waktu_kedatangan ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 10px 0 4px;">Maka kami tugaskan personel kami :</td>
            </tr>
            <tr>
                <td class="label">Personel</td>
                <td class="colon">:</td>
                <td class="value">
                    @php $no = 1; @endphp
                    @foreach($personels as $p)
                        @if($p->role !== 'PIC Project')
                            {{ $no++ }}. {{ $p->name }}{{ $p->role ? ' (' . $p->role . ')' : '' }}<br>
                        @endif
                    @endforeach
                    @if($personels->where('role', '!=', 'PIC Project')->isEmpty()) - @endif
                </td>
            </tr>
            <tr>
                <td class="label">Kendaraan</td>
                <td class="colon">:</td>
                <td class="value">-</td>
            </tr>
            <tr>
                <td class="label">PIC Project</td>
                <td class="colon">:</td>
                <td class="value">
                    @php $no = 1; @endphp
                    @foreach($personels as $p)
                        @if($p->role === 'PIC Project')
                            {{ $no++ }}. {{ $p->name }}<br>
                        @endif
                    @endforeach
                    @if($personels->where('role', 'PIC Project')->isEmpty()) - @endif
                </td>
            </tr>
        </table>

        <p>Untuk melakukan pekerjaan pengambilan sampel dan/at au pengukuran sesuai dengan rincian berikut:</p>
        <table class="boq-table">
            <thead>
                <tr>
                    <th class="boq-th boq-no">No</th>
                    <th class="boq-th">Rincian Pekerjaan</th>
                    <th class="boq-th">Regulasi</th>
                    <th class="boq-th boq-center">Jumlah</th>
                    <th class="boq-th boq-center">Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($boqGroups as $i => $group)
                <tr>
                    <td class="boq-td boq-no boq-center">{{ $i + 1 }}</td>
                    <td class="boq-td">
                        <span style="text-decoration: underline; font-weight: bold;">{{ $group->judul_indonesia_tms }}</span><br>
                        <span style="font-size: 11px;">
                            {{ $group->items->pluck('kode')->implode('; ') }}
                        </span>
                    </td>
                    <td class="boq-td boq-center" style="font-size: 11px;">
                        {{ $group->nomor_ts }}<br>
                        {{ $group->nama_tp }}
                    </td>
                    <td class="boq-td boq-center">{{ $group->qty }}</td>
                    <td class="boq-td boq-center">Titik</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 40px; text-align: center; display: inline-block; float: right; margin-right: 40px;">
            <p>Bekasi, {{ \Carbon\Carbon::parse($fwo->tanggal_mulai)->translatedFormat('d F Y') }}</p>
            <p>PT Pramatek Andal Analitika</p>
            <div style="height: 60px;"></div>
            <p><strong><u>Nama Dummy</u></strong></p>
            <p>Jabatan Dummy</p>
        </div>
        <div style="clear: both;"></div>

    </div>
@endsection
