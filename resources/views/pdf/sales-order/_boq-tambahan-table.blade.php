{{-- Partial tabel BOQ Other / BOQ Sampling — struktur boq_tambahan sama
     persis untuk kedua jenis, cuma beda data yang dikirim ($items, $total)
     dari view pemanggil (printout.blade.php). --}}
@php
    $fmtMoney = fn($v) => 'Rp ' . number_format((float) ($v ?? 0), 0, ',', '.');
@endphp
<table class="boq-table wo-boq-tambahan-table">
    <colgroup>
        <col style="width:6%">
        <col style="width:34%">
        <col style="width:8%">
        <col style="width:12%">
        <col style="width:15%">
        <col style="width:15%">
        <col style="width:10%">
    </colgroup>
    <thead>
        <tr>
            <th class="boq-th boq-no">No</th>
            <th class="boq-th">Nama Item</th>
            <th class="boq-th boq-center">Qty</th>
            <th class="boq-th">Satuan</th>
            <th class="boq-th text-right">Harga</th>
            <th class="boq-th text-right">Subtotal</th>
            <th class="boq-th">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $j => $r)
        <tr>
            <td class="boq-td boq-center">{{ $j + 1 }}</td>
            <td class="boq-td">{{ $r->nama_item ?? '-' }}</td>
            <td class="boq-td boq-center">{{ $r->qty }}</td>
            <td class="boq-td">{{ $r->satuan ?? '-' }}</td>
            <td class="boq-td text-right">{{ $fmtMoney($r->harga) }}</td>
            <td class="boq-td text-right">{{ $fmtMoney(($r->qty ?? 0) * ($r->harga ?? 0)) }}</td>
            <td class="boq-td">{{ $r->keterangan ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="boq-td text-right" colspan="5">Total Nilai</td>
            <td class="boq-td text-right">{{ $fmtMoney($total) }}</td>
            <td class="boq-td"></td>
        </tr>
    </tfoot>
</table>
