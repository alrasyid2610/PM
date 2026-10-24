// ── Judul Output (Output Pekerjaan & Output Other) ─────────────────────────
// Select2 yang bisa memilih dari master `output_judul` ATAU mengetik judul baru (tags).
// Yang dikirim & disimpan adalah teks judulnya, bukan id master; judul baru masuk master
// otomatis saat output disimpan (OutputJudul::resolve() di backend).
// Dipakai di halaman WO (Output Pekerjaan & Output Other) dan SO (Output Other level SO).
function initJudulOutputSelect($sel, tipe, $dropdownParent, nilai) {
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    $sel.empty().append('<option value=""></option>');

    const sama = function (a, b) { return String(a).toLowerCase() === String(b).toLowerCase(); };

    $sel.select2({
        width: '100%',
        placeholder: 'Pilih atau ketik judul output',
        allowClear: true,
        tags: true,
        dropdownParent: $dropdownParent,
        createTag: function (params) {
            const t = $.trim(params.term || '');
            if (!t) return null;
            // Judul yang sama (tidak peka huruf besar-kecil) tidak ditawarkan sebagai judul baru
            let ada = false;
            $sel.find('option').each(function () { if (this.value && sama(this.value, t)) ada = true; });
            return ada ? null : { id: t, text: t, isNew: true };
        },
        templateResult: function (d) {
            if (!d.isNew) return d.text;
            return $('<span></span>').append('<i class="fa-solid fa-plus me-1" style="font-size:10px;"></i>').append(document.createTextNode('Tambah judul baru: "' + d.text + '"'));
        },
        language: { noResults: function () { return 'Ketik untuk menambah judul baru'; } },
    });

    $.get('/output-judul/select2', { tipe: tipe }).done(function (rows) {
        rows.forEach(function (r) { $sel.append(new Option(r.text, r.id, false, false)); });
        if (nilai) {
            // Pakai bentuk yang ada di master bila cocok; kalau belum ada (mis. judul lama) tambahkan sebagai opsi
            let cocok = null;
            $sel.find('option').each(function () { if (this.value && sama(this.value, nilai)) cocok = this.value; });
            if (cocok === null) { $sel.append(new Option(nilai, nilai, false, false)); cocok = nilai; }
            $sel.val(cocok).trigger('change');
        }
    });
    if (nilai) $sel.append(new Option(nilai, nilai, true, true)).trigger('change'); // tampil langsung selagi daftar dimuat
}
