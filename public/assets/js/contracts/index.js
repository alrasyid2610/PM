let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: 'id_contract',

        renderForm: renderContractForm,

        tableColumns: [
            { data: 'DT_RowIndex',       orderable: false, searchable: false },
            { data: 'no_kontrak' },
            { data: 'nama_pelanggan',    defaultContent: '-' },
            { data: 'tanggal_kontrak',   defaultContent: '-' },
            { data: 'tanggal_mulai',     defaultContent: '-' },
            { data: 'tanggal_selesai',   defaultContent: '-' },
            { data: 'durasi_bulan',      defaultContent: '-', render: (d) => d ? d + ' bln' : '-' },
            { data: 'nilai_kontrak',     defaultContent: '-', render: (d) => d ? 'Rp ' + Number(d).toLocaleString('id-ID') : '-' },
            {
                data: 'status',
                render: function (d) {
                    const map = {
                        draft:   '<span class="badge bg-secondary">Draft</span>',
                        aktif:   '<span class="badge bg-success">Aktif</span>',
                        selesai: '<span class="badge bg-primary">Selesai</span>',
                        batal:   '<span class="badge bg-danger">Batal</span>',
                    };
                    return map[d] ?? d;
                },
            },
            { data: 'nama_pic_pramatek', defaultContent: '-' },
            { data: 'created_at',        defaultContent: '-' },
        ],

        onInit: loadSummary,

        initSelect: function (res) {
            initSelect2Ajax({
                selector: '#detail_id_business_relation',
                url:      window.route.select2BR,
                initId:   res.id_business_relation,
                initText: res.nama_pelanggan,
            });
            initSelect2Ajax({
                selector: '#detail_id_pic_pelanggan',
                url:      window.route.select2Contact,
                initId:   res.id_pic_pelanggan,
                initText: res.nama_pic_pelanggan,
            });
            initSelect2Ajax({
                selector: '#detail_id_pic_pramatek',
                url:      window.route.select2User,
                initId:   res.id_pic_pramatek,
                initText: res.nama_pic_pramatek,
            });
        },

        useAttachment: false,
    });
});


function loadSummary() {
    $.get(window.route.data, function (res) {
        const rows = res.data || [];
        let draft = 0, aktif = 0, selesai = 0;
        rows.forEach(r => {
            if (r.status === 'draft')   draft++;
            if (r.status === 'aktif')   aktif++;
            if (r.status === 'selesai') selesai++;
        });
        $('#totalKontrak').text(rows.length);
        $('#totalDraft').text(draft);
        $('#totalAktif').text(aktif);
        $('#totalSelesai').text(selesai);
    });
}


function initSelect2Ajax({ selector, url, initId, initText }) {
    const $el = $(selector);
    if (!$el.length) return;

    if (initId && initText) {
        $el.append(new Option(initText, initId, true, true));
    }

    $el.select2({
        width: '100%',
        placeholder: '-- Pilih --',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: url,
            delay: 300,
            dataType: 'json',
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
        },
    });
}
