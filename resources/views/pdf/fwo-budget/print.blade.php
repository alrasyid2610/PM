@extends('pdf.layouts.document')

@section('title', 'Serah Terima Budget')

@section('content')
<style>
    body { font-size: 12px; font-family: Arial, sans-serif; color: #1e293b; }
    .page { padding: 24px 32px; }
    h2 { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
    .sub-title { text-align: center; font-size: 11px; color: #64748b; margin-bottom: 20px; }
    .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
    .info-table td { padding: 3px 6px; vertical-align: top; font-size: 11px; }
    .info-table td.label { width: 160px; font-weight: 600; color: #475569; }
    .info-table td.colon { width: 12px; }
    .budget-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .budget-table th { background: #1e3a5f; color: #fff; font-size: 11px; padding: 7px 10px; text-align: left; }
    .budget-table td { padding: 7px 10px; font-size: 11px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .budget-table tr:nth-child(even) td { background: #f8fafc; }
    .budget-table tfoot td { font-weight: bold; border-top: 2px solid #1e3a5f; background: #f1f5f9; }
    .text-right { text-align: right; }
    .ttd-area { margin-top: 40px; }
    .ttd-table { width: 100%; border-collapse: collapse; }
    .ttd-table td { width: 50%; padding: 0 16px; vertical-align: top; text-align: center; font-size: 11px; }
    .ttd-box { border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px; min-height: 90px; margin-bottom: 6px; }
</style>

<div class="page">
    <h2>Serah Terima Anggaran</h2>
    <p class="sub-title">{{ $budget->no_fwo }} — {{ $budget->label }}</p>

    <table class="info-table">
        <tr>
            <td class="label">No. FWO</td>
            <td class="colon">:</td>
            <td>{{ $budget->no_fwo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">No. WO</td>
            <td class="colon">:</td>
            <td>{{ $budget->no_wo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Judul Pekerjaan</td>
            <td class="colon">:</td>
            <td>{{ $budget->fwo_judul ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Budget Plan</td>
            <td class="colon">:</td>
            <td><b>{{ $budget->label }}</b></td>
        </tr>
        @if($budget->keterangan)
        <tr>
            <td class="label">Keterangan</td>
            <td class="colon">:</td>
            <td>{{ $budget->keterangan }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Tanggal Cetak</td>
            <td class="colon">:</td>
            <td>{{ now()->locale('id')->isoFormat('D MMMM Y') }}</td>
        </tr>
    </table>

    <table class="budget-table">
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th>Kode</th>
                <th>Uraian Anggaran</th>
                <th>Keterangan</th>
                <th class="text-right" style="min-width:120px;">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            <tr>
                <td style="text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $item->kode_account }}</td>
                <td>{{ $item->nama_account }}</td>
                <td style="color:#64748b;">{{ $item->keterangan ?? '-' }}</td>
                <td class="text-right" style="font-weight:600;">
                    {{ number_format($item->nominal_budget, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;padding-right:10px;">Total Anggaran</td>
                <td class="text-right" style="font-size:13px;">
                    Rp {{ number_format($total, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="ttd-area">
        <table class="ttd-table">
            <tr>
                <td>
                    <p style="margin-bottom:6px;">Yang Menyerahkan,</p>
                    <div class="ttd-box"></div>
                    <p style="margin-top:4px;">( __________________________ )</p>
                    <p style="font-size:10px;color:#64748b;">Admin / Finance</p>
                </td>
                <td>
                    <p style="margin-bottom:6px;">Yang Menerima,</p>
                    <div class="ttd-box"></div>
                    <p style="margin-top:4px;">( __________________________ )</p>
                    <p style="font-size:10px;color:#64748b;">Tim Lapangan</p>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
