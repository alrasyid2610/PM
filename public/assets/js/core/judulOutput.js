// ── Judul Output (Output Pekerjaan & Output Other) ─────────────────────────
// Select2 yang HANYA berisi judul AKTIF dari Master Output (menu "Master Output", tabel `output_judul`) untuk tipe
// yang diminta ('pekerjaan' | 'other'). Tidak bisa mengetik judul baru (sejak 2026-11-14) supaya typo tidak masuk;
// judul baru ditambah admin di menu Master Output. Nilai select = id master → kirim sebagai `id_output_judul`.
// Tiap opsi membawa data-lhu (penanda LHU dari master) dipakai saklar LHU di modal Output Pekerjaan.
// Dipakai di halaman WO (Output Pekerjaan & Output Other) dan SO (Output Other level SO).
//
//   idSaatIni / teksSaatIni = judul yang sudah tersimpan di output yang sedang diedit (kosong untuk output baru).
//   Judul itu tetap tampil walau sudah dinonaktifkan di master, supaya output lama bisa diedit tanpa mengganti judulnya.
function initJudulOutputSelect($sel, tipe, $dropdownParent, idSaatIni, teksSaatIni) {
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    $sel.empty().append('<option value=""></option>');

    // Kompatibel mundur: pemanggil lama mengirim teks sebagai argumen ke-4
    if (idSaatIni !== undefined && idSaatIni !== null && idSaatIni !== '' && isNaN(Number(idSaatIni))) {
        teksSaatIni = idSaatIni;
        idSaatIni = null;
    }
    idSaatIni = idSaatIni ? String(idSaatIni) : '';
    teksSaatIni = teksSaatIni || '';

    $sel.select2({
        width: '100%',
        placeholder: 'Pilih judul output',
        allowClear: true,
        dropdownParent: $dropdownParent,
        language: { noResults: function () { return 'Judul tidak ditemukan — minta admin menambahkannya di Master Output'; } },
    });

    const lhuAttr = function (opt, lhu) { $(opt).attr('data-lhu', lhu ? 1 : 0); return opt; };

    // Tampil langsung selagi daftar dimuat (output yang sedang diedit)
    if (idSaatIni && teksSaatIni) {
        $sel.append(lhuAttr(new Option(teksSaatIni, idSaatIni, true, true), 0)).trigger('change.select2');
    }

    $.get('/output-judul/select2', { tipe: tipe }).done(function (rows) {
        $sel.empty().append('<option value=""></option>');
        let ketemu = false;
        rows.forEach(function (r) {
            const pilih = idSaatIni && String(r.id) === idSaatIni;
            if (pilih) ketemu = true;
            $sel.append(lhuAttr(new Option(r.text, r.id, false, pilih), r.is_lhu));
        });

        if (idSaatIni && !ketemu) {
            // Judul output ini sudah dinonaktifkan di master: tetap ditampilkan (bisa disimpan tanpa mengganti judul)
            $sel.append(lhuAttr(new Option(teksSaatIni + ' (nonaktif)', idSaatIni, false, true), 0));
        } else if (!idSaatIni && teksSaatIni) {
            // Data lama tanpa id: cocokkan ke master lewat teks (tidak peka huruf besar-kecil)
            $sel.find('option').each(function () {
                if (this.value && String(this.text).toLowerCase() === String(teksSaatIni).toLowerCase()) {
                    $sel.val(this.value);
                }
            });
        }
        $sel.trigger('change');
    });
}

// Penanda LHU dari master untuk opsi yang sedang terpilih (dipakai saklar LHU di modal Output Pekerjaan)
function judulOutputIsLhu($sel) {
    return Number($sel.find('option:selected').attr('data-lhu') || 0) === 1;
}
