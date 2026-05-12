// let selectedRow = {
//     no_so: null,
//     id_so: null,
// };

// $(document).ready(function () {
//     initDataTable(tableId);

//     $(tableId)
//         .find("tbody")
//         .on("click", "tr", function () {
//             if ($(event.target).closest("button").length) return;

//             console.log("row clicked, show detail tab");
//             const data = table.row(this).data();
//             if (!data) return;

//             console.log("Selected Row Data:", data);
//             selectedRow.no_so = data.no_so;
//             selectedRow.id_so = data.id_so;
//             // console.log("Selected SO Number:", selectedRow.no_so);

//             loadDetail(data.id_so);

//             $(tableId + " tr").removeClass("table-active");
//             $(this).addClass("table-active");

//             // pindah ke tab Detail
//             const detailTab = new bootstrap.Tab(
//                 document.querySelector("#detail-tab"),
//             );
//             detailTab.show();
//         });
// });

// function loadDetail(id_so) {
//     $("#detailContent").html("Loading...");

//     $.get(window.route.detail + id_so + "/detail", function (res) {
//         console.log(res);

//         $("#detailContent").html(`
//                 <form class="row g-3" id="detailForm">

//                     <div class="col-md-12">
//                         <div class="d-flex justify-content-between align-items-center mb-2">
//                             <h3>Sales Order</h3>
//                             <div class="btn-group">
//                                 <button
//                                     class="btn btn-warning btn-sm btn-edit-context"
//                                     data-br="${res.id_br}"
//                                     data-site="${res.id_site}"
//                                     title="Edit Business Relation">
//                                     <i class="fa-solid fa-pen"></i>
//                                 </button>
//                             </div>

//                         </div>

//                         <div class="row mb-4">
//                             <input type="hidden" name="_token" value="${window.route.csrf}">

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label required">Tanggal SO</label>
//                                 <input type="date" name="tanggal_so" class="form-control disabled" value="${res.tanggal_so}" required>
//                             </div>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label required">No SO</label>
//                                 <p class="text-muted form-control">${res.no_so}</p>
//                             </div>

//                             <div class="col-md-12 col-lg-6 mb-3">
//                                 <label class="form-label required">Judul Order</label>
//                                 <input type="text" name="judul_order" class="form-control disabled" value="${res.judul_order}" required>
//                             </div>

//                             <h6 class="fw-bold mb-1">PO</h6>
//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">Tidak Ada PO</label>
//                                 <select name="tidak_ada_po" class="form-select disabled">
//                                     <option value="1">Ada PO</option>
//                                     <option value="0">Tidak Ada PO</option>
//                                 </select>
//                             </div>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">Tanggal PO</label>
//                                 <input type="date" name="tanggal_po" class="form-control disabled" value="${res.tanggal_po}">
//                             </div>

//                             <div class="col-md-12 col-lg-6 mb-3">
//                                 <label class="form-label">No PO</label>
//                                 <input type="text" name="no_po" class="form-control disabled" value="${res.no_po}">
//                             </div>

//                             <h6 class="fw-bold mb-1">Delivery</h6>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">Tanggal Mulai</label>
//                                 <input type="date" name="tanggal_mulai" class="form-control disabled" value="${res.tanggal_mulai}">
//                             </div>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">Tanggal Selesai</label>
//                                 <input type="date" name="tanggal_selesai" class="form-control disabled" value="${res.tanggal_selesai}">
//                             </div>

//                             <div class="col-md-12 col-lg-6 mb-3">
//                                 <label class="form-label">Office</label>
//                                 <select name="id_office" class="form-select disabled">
//                                     <option value="">Pilih Office</option>
//                                     <option value="1">Pramatek Jakarta</option>
//                                     <option value="2">Pramatek Bandung</option>
//                                 </select>
//                             </div>

//                             <h6 class="fw-bold mb-1">Pelanggan</h6>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label required">Pelanggan</label>
//                                 <select name="id_pelanggan" id="id_pelanggan" class="form-select disabled" required>
//                                     <option value="">Pilih Pelanggan</option>
//                                 </select>
//                             </div>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label">Site</label>
//                                 <select name="id_site_pelanggan" id="id_site_pelanggan" class="form-select disabled">
//                                     <option value="">Pilih Site</option>
//                                 </select>
//                             </div>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label">PIC</label>
//                                 <select name="id_pic_pelanggan" id="id_pic_pelanggan" class="form-select disabled">
//                                     <option value="">Pilih PIC</option>
//                                 </select>
//                             </div>

//                             <!-- ================= Delivery ================= -->
//                             <h6 class="fw-bold mb-1">Delivery</h6>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label">Pelanggan</label>
//                                 <select name="id_pelanggan_delivery" id="id_pelanggan_delivery" class="form-select disabled" required>
//                                     <option value="">Pilih Pelanggan</option>
//                                 </select>
//                             </div>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label">Site</label>
//                                 <select name="id_site_pelanggan_delivery" id="id_site_pelanggan_delivery" class="form-select disabled">
//                                     <option value="">Pilih Site</option>
//                                 </select>
//                             </div>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label">PIC</label>
//                                 <select name="id_pic_pelanggan_delivery" id="id_pic_pelanggan_delivery" class="form-select disabled">
//                                     <option value="">Pilih PIC</option>
//                                 </select>
//                             </div>

//                             <!-- ================= PAYMENT ================= -->
//                             <h6 class="fw-bold mb-1">Payment</h6>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label">Pelanggan</label>
//                                 <select name="id_pelanggan_payment" id="id_pelanggan_payment" class="form-select disabled" required>
//                                     <option value="">Pilih Pelanggan</option>
//                                 </select>
//                             </div>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label">Site</label>
//                                 <select name="id_site_pelanggan_payment" id="id_site_pelanggan_payment" class="form-select disabled">
//                                     <option value="">Pilih Site</option>
//                                 </select>
//                             </div>

//                             <div class="col-md-12 col-lg-4 mb-3">
//                                 <label class="form-label">PIC</label>
//                                 <select name="id_pic_pelanggan_payment" id="id_pic_pelanggan_payment" class="form-select disabled">
//                                     <option value="">Pilih PIC</option>
//                                 </select>
//                             </div>

//                             <!-- ================= PIC INTERNAL ================= -->
//                             <h6 class="fw-bold mb-1">PIC</h6>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">PIC Input</label>
//                                 <input type="text" name="pic_input" class="form-control disabled" value="${res.pic_input}">
//                             </div>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">PIC Order</label>
//                                 <input type="text" name="pic_order" class="form-control disabled" value="${res.pic_order}">
//                             </div>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">Marketing Internal</label>
//                                 <input type="text" name="pic_marketing_internal" class="form-control disabled" value="${res.pic_marketing_internal}">
//                             </div>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">Marketing Eksternal</label>
//                                 <input type="text" name="pic_marketing_eksternal" class="form-control disabled" value="${res.pic_marketing_eksternal}">
//                             </div>

//                             <!-- ================= STATUS ================= -->
//                             <h6 class="fw-bold mb-1">Status</h6>

//                             <div class="col-md-12 col-lg-3 mb-3">
//                                 <label class="form-label">Status</label>
//                                 <input type="text" name="status" class="form-control disabled" value="${res.status}">
//                             </div>

//                             <div class="col-md-12 col-lg-9 mb-3">
//                                 <label class="form-label">Keterangan Status</label>
//                                 <textarea name="keterangan_status" class="form-control disabled" rows="2">${res.keterangan_status ?? ""}</textarea>
//                             </div>

//                             <div class="col-md-12 col-lg-12 mb-3">
//                                 <label class="form-label">Keterangan</label>
//                                 <textarea name="keterangan" class="form-control disabled" rows="3">${res.keterangan ?? ""}</textarea>
//                             </div>

//                         </div>

//                     </div>

//                 </form>
//             `);

//         $("#detailContent")
//             .find("input, select, textarea")
//             .prop("disabled", true);

//         loadPelangganDetails(res);

//         $("#id_pelanggan").on("select2:select", function (e) {
//             var data = e.params.data;
//             console.log("Data pelanggan yang dipilih:", data);
//             console.log(data.id);

//             $("select[name='id_pelanggan_delivery']")
//                 .val(data.id)
//                 .trigger("change");

//             $("select[name='id_pelanggan_payment']")
//                 .val(data.id)
//                 .trigger("change");

//             console.log(
//                 $("select[name='id_pelanggan_delivery']").find(
//                     "option[value='" + data.id + "']",
//                 ).length,
//             );

//             $.ajax({
//                 url: "/api/get-contact-site/" + data.id, // Kirim ID site yang dipilih
//                 method: "GET",
//                 success: function (response) {
//                     console.log(
//                         "Data kontak pelanggan berhasil dimuat:",
//                         response,
//                     );
//                     $("#id_pic_pelanggan")
//                         .empty()
//                         .append('<option value="">Pilih PIC</option>'); // Reset options PIC pelanggan
//                     $.each(response, function (index, contact) {
//                         $("#id_pic_pelanggan").append(
//                             new Option(contact.nama_pic, contact.id_contact),
//                         );
//                     });

//                     $("#id_pic_pelanggan").select2({
//                         placeholder: "Pilih PIC",
//                         allowClear: true,
//                     });

//                     // Lakukan sesuatu dengan data kontak, misalnya tampilkan di form
//                 },
//                 error: function (xhr) {
//                     Notify.error("Gagal memuat kontak pelanggan");
//                 },
//             });
//         });

//         $(".btn-edit-context").on("click", function (e) {
//             e.preventDefault();
//             const $btn = $(this);
//             const isEditing = $btn.hasClass("editing");

//             if (!isEditing) {
//                 // Switch to edit mode
//                 $("#detailContent")
//                     .find("input, select, textarea")
//                     .prop("disabled", false);

//                 $("#detailContent")
//                     .find("input, select, textarea")
//                     .removeClass("disabled");

//                 $btn.addClass("editing")
//                     .removeClass("btn-warning")
//                     .addClass("btn-secondary")
//                     .html('<i class="fa-solid fa-times"></i>');

//                 // Add save button
//                 $btn.after(`
//                 <button class="btn btn-success btn-sm btn-save-context ms-2" title="Simpan">
//                 <i class="fa-solid fa-check"></i>
//                 </button>
//             `);

//                 // SUBMIT FORM

//                 $(".btn-save-context").on("click", function (e) {
//                     e.preventDefault();
//                     // Save logic here
//                     console.log("Save clicked");
//                     submitForm();
//                 });
//             } else {
//                 // Cancel edit mode
//                 $("#detailContent")
//                     .find("input, select, textarea")
//                     .prop("disabled", true);

//                 $("#detailContent")
//                     .find("input, select, textarea")
//                     .addClass("disabled");

//                 $btn.removeClass("editing")
//                     .addClass("btn-warning")
//                     .removeClass("btn-secondary")
//                     .html('<i class="fa-solid fa-pen"></i>');

//                 $(".btn-save-context").remove();
//             }
//         });
//     });
// }

// function loadPelangganDetails(data) {
//     // Console log untuk memastikan fungsi dipanggil
//     console.log("Memuat data pelanggan...");

//     $.ajax({
//         url: "/api/get-data-br",
//         method: "GET",
//         success: function (response) {
//             dataPelanggan = response;
//             console.log(
//                 "Data pelanggan busines relation berhasil dimuat dan select2 diisi.",
//             );
//             console.log("init select2");
//             // Populate select2 for pelanggan
//             $.each(dataPelanggan, function (index, item) {
//                 $("select[name='id_pelanggan']").append(
//                     new Option(item.text, item.id),
//                 );
//             });

//             $.each(dataPelanggan, function (index, item) {
//                 $("select[name='id_pelanggan_delivery']").append(
//                     new Option(item.text, item.id),
//                 );
//             });

//             $.each(dataPelanggan, function (index, item) {
//                 $("select[name='id_pelanggan_payment']").append(
//                     new Option(item.text, item.id),
//                 );
//             });

//             // Initialize select2 for delivery and payment
//             // Baru init select2 TANPA data:
//             $("select[name='id_pelanggan']").select2({
//                 placeholder: "Pilih Pelanggan",
//                 allowClear: true,
//             });

//             $("select[name='id_pelanggan_delivery']").select2({
//                 placeholder: "Pilih Pelanggan",
//                 allowClear: true,
//             });

//             $("select[name='id_pelanggan_payment']").select2({
//                 placeholder: "Pilih Pelanggan",
//                 allowClear: true,
//             });

//             $("#id_pelanggan").val(data.id_pelanggan).trigger("change");
//             $("#id_pelanggan_delivery")
//                 .val(data.id_pelanggan_delivery)
//                 .trigger("change");
//             $("#id_pelanggan_payment")
//                 .val(data.id_pelanggan_payment)
//                 .trigger("change");

//             console.log(
//                 "Select2 berhasil diinisialisasi dengan data pelanggan.",
//             );
//         },
//         error: function (xhr) {
//             Notify.error("Gagal memuat detail pelanggan");
//         },
//     });

//     $.ajax({
//         url: "/api/get-data-site",
//         method: "GET",
//         success: function (response) {
//             dataPelanggan = response;
//             console.log("Data pelanggan berhasil dimuat dan select2 diisi.");

//             console.log("init select2", response);

//             // Populate select2 for pelanggan
//             // Populate select2 for pelanggan
//             $.each(dataPelanggan, function (index, item) {
//                 $("select[name='id_site_pelanggan']").append(
//                     new Option(item.nama_lokasi, item.id_site),
//                 );
//             });

//             $.each(dataPelanggan, function (index, item) {
//                 $("select[name='id_site_pelanggan_delivery']").append(
//                     new Option(item.nama_lokasi, item.id_site),
//                 );
//             });

//             $.each(dataPelanggan, function (index, item) {
//                 $("select[name='id_site_pelanggan_payment']").append(
//                     new Option(item.nama_lokasi, item.id_site),
//                 );
//             });

//             // Initialize select2 for delivery and payment
//             // Baru init select2 TANPA data:
//             $("select[name='id_site_pelanggan']").select2({
//                 placeholder: "Pilih Pelanggan",
//                 allowClear: true,
//             });

//             $("select[name='id_site_pelanggan_delivery']").select2({
//                 placeholder: "Pilih Pelanggan",
//                 allowClear: true,
//             });

//             $("select[name='id_site_pelanggan_payment']").select2({
//                 placeholder: "Pilih Pelanggan",
//                 allowClear: true,
//             });

//             $("#id_site_pelanggan")
//                 .val(data.id_site_pelanggan)
//                 .trigger("change");
//             $("#id_site_pelanggan_delivery")
//                 .val(data.id_site_pelanggan_delivery)
//                 .trigger("change");
//             $("#id_site_pelanggan_payment")
//                 .val(data.id_site_pelanggan_payment)
//                 .trigger("change");

//             console.log(
//                 "Select2 berhasil diinisialisasi dengan data pelanggan.",
//             );
//         },
//         error: function (xhr) {
//             Notify.error("Gagal memuat detail pelanggan");
//         },
//     });
// }

// function submitForm(e) {
//     const formData = $("#detailForm").serialize();
//     console.log("Form data to submit:", formData);
//     Notify.confirm("Simpan Data?", function () {
//         $.ajax({
//             url: window.route.update + selectedRow.id_so,
//             method: "PUT",
//             data: formData,
//             success: function (response) {
//                 Notify.success("Data berhasil diperbarui");
//                 loadDetail(selectedRow.id_so);
//                 // $("#modalEdit").modal("hide");
//                 // reloadTable();
//             },
//             error: function (xhr) {
//                 Notify.error("Gagal memperbarui data");
//             },
//         });
//     });
// }

let page;
let currentWosData = null;
let woViewMode     = localStorage.getItem('wo_progress_view') || 'card';

function loadWoProgress(id_so, onDone) {
    $('#woProgressContent').html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>'
    );

    $.get(window.route.woProgress + id_so + '/wo-progress', function (wos) {
        currentWosData = wos;
        $('#woBadgeCount').text(wos ? wos.length : 0);
        renderWoProgressView(wos);
        if (onDone) onDone();
    }).fail(function () {
        currentWosData = null;
        $('#woBadgeCount').text('!');
        $('#woProgressContent').html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>'
        );
        if (onDone) onDone();
    });
}

function filterWos(wos, term) {
    if (!term) return wos;
    const lower = term.toLowerCase();
    return (wos || []).filter(function (wo) {
        if ((wo.no_wo ?? '').toLowerCase().includes(lower)) return true;
        if (wo.fwos && wo.fwos.some(f => (f.no_fwo ?? '').toLowerCase().includes(lower))) return true;
        return false;
    });
}

function renderWoProgressView(wos) {
    const term     = (($('#woProgressSearch').val()) || '').trim();
    const filtered = filterWos(wos, term);

    // Tampilkan/sembunyikan tombol clear
    $('#btnClearWoSearch').toggleClass('d-none', !term);

    if (!wos || !wos.length) {
        $('#woProgressContent').html(
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            'Belum ada Work Order untuk Sales Order ini</div>'
        );
    } else if (!filtered.length) {
        $('#woProgressContent').html(
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-magnifying-glass fa-2x d-block mb-2 opacity-25"></i>' +
            'Tidak ditemukan hasil untuk <strong>&ldquo;' + escSo(term) + '&rdquo;</strong></div>'
        );
    } else if (woViewMode === 'table') {
        $('#woProgressContent').html(renderWoProgressTable(filtered));
    } else {
        $('#woProgressContent').html(filtered.map(renderWoProgressCard).join(''));
    }
    syncToggleBtn();
}

function syncToggleBtn() {
    const $btn = $('#btnToggleWoView');
    if (woViewMode === 'table') {
        $btn.html('<i class="fa-solid fa-grip"></i>').attr('title', 'Tampilan Kartu');
    } else {
        $btn.html('<i class="fa-solid fa-table-list"></i>').attr('title', 'Tampilan Tabel');
    }
}

function renderWoProgressTable(wos) {
    const rows = wos.map(function (wo) {
        const pctStyle    = wo.progress_pct >= 100 ? 'background:#198754;' : wo.progress_pct > 0 ? 'background:#1d4ed8;' : 'background:#6c757d;';
        const pctTxtColor = wo.progress_pct >= 100 ? '#198754' : wo.progress_pct > 0 ? '#1d4ed8' : '#6c757d';
        const amount      = wo.total_boq_amount > 0 ? 'Rp ' + Number(wo.total_boq_amount).toLocaleString('en-US') : '—';
        const subrowId    = 'fwo-sub-' + wo.id_wo;
        const hasFwo      = wo.fwos && wo.fwos.length > 0;

        // ── FWO sub-row content ──────────────────────────────────
        const fwoSubRows = hasFwo ? wo.fwos.map(function (fwo) {
            const tgl = fwo.tanggal_mulai ? fwo.tanggal_mulai.substring(0, 10) : '—';
            return `<tr style="background:#f8fafc;">
                <td style="padding:6px 10px;border-color:#e9ecef;">
                    <a href="/fieldworks?open=${fwo.id_fwo}" target="_blank"
                        class="fw-semibold text-decoration-none" style="color:#7c3aed;font-size:12px;">
                        <i class="fa-solid fa-hard-hat me-1" style="font-size:11px;"></i>${escSo(fwo.no_fwo ?? '—')}
                    </a>
                </td>
                <td style="padding:6px 10px;border-color:#e9ecef;font-size:12px;color:#64748b;">${tgl}</td>
                <td style="padding:6px 10px;border-color:#e9ecef;">
                    <span style="font-size:11px;background:#e9ecef;color:#495057;padding:2px 8px;border-radius:20px;white-space:nowrap;">
                        ${fwo.boq_section_count} item &middot; ${fwo.total_qty} qty
                    </span>
                </td>
                <td style="padding:6px 10px;border-color:#e9ecef;">
                    <a href="/fieldworks?open=${fwo.id_fwo}" target="_blank"
                        class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Buka detail FWO">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </td>
            </tr>`;
        }).join('') : `<tr style="background:#f8fafc;">
            <td colspan="4" class="text-muted text-center" style="padding:8px;font-size:12px;border-color:#e9ecef;">Belum ada FWO</td>
        </tr>`;

        const fwoSubHtml = `<tr id="${subrowId}" style="display:none;">
            <td colspan="6" style="padding:0;border-top:none;">
                <div style="border-top:1px dashed #e2e8f0;margin:0 0 0 28px;">
                    <table class="table table-sm mb-0" style="font-size:12px;">
                        <thead style="background:#f1f5f9;">
                            <tr>
                                <th style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;padding:5px 10px;border-color:#e9ecef;">No FWO</th>
                                <th style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;padding:5px 10px;border-color:#e9ecef;">Tanggal</th>
                                <th style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;padding:5px 10px;border-color:#e9ecef;">Items</th>
                                <th style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;padding:5px 10px;border-color:#e9ecef;"></th>
                            </tr>
                        </thead>
                        <tbody>${fwoSubRows}</tbody>
                    </table>
                </div>
            </td>
        </tr>`;

        const woRow = `<tr class="wo-table-row" data-subrow="${subrowId}" style="cursor:pointer;">
            <td class="align-middle">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chevron-right wo-table-chevron text-muted" style="font-size:10px;transition:transform .2s;"></i>
                    <a href="/work-orders?open=${wo.id_wo}" target="_blank"
                        class="fw-semibold text-decoration-none" style="color:#1d4ed8;"
                        onclick="event.stopPropagation();">
                        ${escSo(wo.no_wo ?? '—')}
                    </a>
                </div>
            </td>
            <td class="align-middle">${escSo(wo.judul_pekerjaan ?? '—')}</td>
            <td class="align-middle">
                <span style="font-size:11px;background:#ede9fe;color:#7c3aed;padding:2px 8px;border-radius:20px;white-space:nowrap;">
                    <i class="fa-solid fa-hard-hat me-1"></i>${wo.fwo_count}
                </span>
            </td>
            <td class="align-middle fw-semibold" style="color:#1d4ed8;white-space:nowrap;">${amount}</td>
            <td class="align-middle" style="min-width:160px;">
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px;">
                        <div class="progress-bar" style="width:${wo.progress_pct}%;${pctStyle}transition:width .4s;"></div>
                    </div>
                    <span style="font-size:11px;font-weight:600;color:${pctTxtColor};white-space:nowrap;">${wo.progress_pct}%</span>
                </div>
                <div class="text-muted" style="font-size:11px;margin-top:2px;">${wo.total_fwo_qty} / ${wo.total_boq_qty} qty</div>
            </td>
            <td class="align-middle" onclick="event.stopPropagation();">
                <div class="d-flex gap-1">
                    <a href="/boq/create?id_wo=${wo.id_wo}" target="_blank"
                        class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:11px;" title="Tambah BOQ">
                        <i class="fa-solid fa-plus" style="font-size:9px;"></i><i class="fa-solid fa-layer-group ms-1"></i>
                    </a>
                    <a href="/fieldworks/create?id_wo=${wo.id_wo}" target="_blank"
                        class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11px;" title="Tambah FWO">
                        <i class="fa-solid fa-plus" style="font-size:9px;"></i><i class="fa-solid fa-hard-hat ms-1"></i>
                    </a>
                </div>
            </td>
        </tr>${fwoSubHtml}`;

        return woRow;
    }).join('');

    return `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th class="text-muted fw-semibold py-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">No WO</th>
                    <th class="text-muted fw-semibold py-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Judul Pekerjaan</th>
                    <th class="text-muted fw-semibold py-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">FWO</th>
                    <th class="text-muted fw-semibold py-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Total BOQ</th>
                    <th class="text-muted fw-semibold py-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Progress</th>
                    <th class="text-muted fw-semibold py-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Aksi</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

function renderWoProgressCard(wo) {
    const pctColor      = wo.progress_pct >= 100 ? 'bg-success' : wo.progress_pct > 0 ? 'bg-primary' : 'bg-secondary';
    const hasBoq        = wo.sections && wo.sections.length > 0;
    const hasFwo        = wo.fwos && wo.fwos.length > 0;
    const collapseId    = 'wo-collapse-' + wo.id_wo;

    const pctBadgeStyle = wo.progress_pct >= 100
        ? 'background:#198754;color:#fff;'
        : wo.progress_pct > 0
            ? 'background:#dbeafe;color:#1d4ed8;'
            : 'background:#e9ecef;color:#495057;';

    // ── BOQ Section rows ────────────────────────────────────────────
    const sectionsHtml = hasBoq ? wo.sections.map(function (sec) {
        const secColor   = sec.progress_pct >= 100 ? 'bg-success' : sec.progress_pct > 0 ? 'bg-primary' : 'bg-secondary';
        const satuan     = sec.satuan ? escSo(sec.satuan) : '';
        const done       = sec.progress_pct >= 100
            ? '<i class="fa-solid fa-circle-check text-success flex-shrink-0 ms-2" style="font-size:13px;"></i>' : '';
        const priceHtml  = sec.harga > 0
            ? `<div style="font-size:11px;color:#64748b;margin-top:2px;">
                <span>Rp ${Number(sec.harga).toLocaleString('en-US')}${satuan ? ' / ' + satuan : ''}</span>
                <span style="margin:0 4px;">×</span>
                <span>${sec.boq_qty}${satuan ? ' ' + satuan : ''}</span>
                <span style="margin:0 4px;">=</span>
                <strong style="color:#1d4ed8;">Rp ${Number(sec.total_amount).toLocaleString('en-US')}</strong>
               </div>`
            : '';
        return `<div class="d-flex align-items-center gap-2 py-2" style="border-bottom:1px solid #f1f5f9;">
            <div class="flex-grow-1" style="min-width:0;">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="small fw-semibold">${escSo(sec.point_name)}</div>
                        ${priceHtml}
                    </div>
                    <span class="small text-muted flex-shrink-0 ms-2" style="padding-top:1px;">${sec.fwo_qty} / ${sec.boq_qty}${satuan ? ' ' + satuan : ''}</span>
                </div>
                <div class="progress" style="height:5px;">
                    <div class="progress-bar ${secColor}" style="width:${sec.progress_pct}%;transition:width .4s;"></div>
                </div>
            </div>${done}
        </div>`;
    }).join('') : '<div class="text-muted small py-2 text-center">Belum ada item BOQ</div>';

    // ── FWO list rows ───────────────────────────────────────────────
    const fwoListHtml = hasFwo ? wo.fwos.map(function (fwo) {
        const tgl = fwo.tanggal_mulai
            ? fwo.tanggal_mulai.substring(0, 10)
            : '—';
        return `<div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid #f1f5f9;">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-hard-hat" style="color:#7c3aed;font-size:12px;"></i>
                <a href="/fieldworks?open=${fwo.id_fwo}" target="_blank"
                    class="fw-semibold small text-decoration-none">${escSo(fwo.no_fwo ?? '—')}</a>
                <span class="text-muted small">${tgl}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:11px;background:#e9ecef;color:#495057;padding:3px 8px;border-radius:20px;white-space:nowrap;">
                    ${fwo.boq_section_count} item · ${fwo.total_qty} qty
                </span>
            </div>
        </div>`;
    }).join('') : '<div class="text-muted small py-2 text-center">Belum ada FWO</div>';

    return `<div class="card mb-3">
        <div class="card-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="fa-solid fa-briefcase" style="color:#2563eb;"></i>
                    <span class="fw-semibold">${escSo(wo.no_wo ?? '—')}</span>
                    <span class="text-muted small">${escSo(wo.judul_pekerjaan ?? '—')}</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap">
                    <span style="font-size:11px;background:#ede9fe;color:#7c3aed;padding:3px 8px;border-radius:20px;white-space:nowrap;">
                        <i class="fa-solid fa-hard-hat me-1"></i>${wo.fwo_count} FWO
                    </span>
                    ${wo.total_boq_amount > 0 ? `<span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:3px 8px;border-radius:20px;font-weight:600;white-space:nowrap;" title="Total nilai BOQ">
                        <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(wo.total_boq_amount).toLocaleString('en-US')}
                    </span>` : ''}
                    <span style="font-size:11px;padding:3px 8px;border-radius:20px;font-weight:600;white-space:nowrap;${pctBadgeStyle}">${wo.progress_pct}%</span>
                    <a href="/boq/create?id_wo=${wo.id_wo}" target="_blank"
                        class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:11px;" title="Tambah BOQ Item">
                        <i class="fa-solid fa-plus" style="font-size:9px;"></i><i class="fa-solid fa-layer-group ms-1 me-1"></i> BOQ
                    </a>
                    <a href="/fieldworks/create?id_wo=${wo.id_wo}" target="_blank"
                        class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11px;" title="Tambah FWO">
                        <i class="fa-solid fa-plus" style="font-size:9px;"></i><i class="fa-solid fa-hard-hat ms-1 me-1"></i> FWO
                    </a>
                    <a href="/work-orders?open=${wo.id_wo}" target="_blank"
                        class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Buka detail WO">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-wo-toggle"
                        data-target="${collapseId}" style="font-size:11px;" title="Lihat detail">
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            ${hasBoq ? `<div class="mt-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted">BOQ Progress</span>
                    <span class="small text-muted">${wo.total_fwo_qty} / ${wo.total_boq_qty} qty</span>
                </div>
                <div class="progress" style="height:7px;">
                    <div class="progress-bar ${pctColor}" style="width:${wo.progress_pct}%;transition:width .4s;"></div>
                </div>
            </div>` : ''}
        </div>

        <div id="${collapseId}" style="display:none;">
            <div class="card-body px-3 py-3" style="background:#fafbfc;">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="fw-semibold small text-muted mb-2">
                            <i class="fa-solid fa-layer-group me-1"></i> BOQ Items (${wo.sections.length})
                        </div>
                        ${sectionsHtml}
                    </div>
                    <div class="col-md-6" style="border-left:1px solid #e9ecef;">
                        <div class="fw-semibold small text-muted mb-2">
                            <i class="fa-solid fa-hard-hat me-1"></i> Fieldwork Orders (${wo.fwos.length})
                        </div>
                        ${fwoListHtml}
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

function escSo(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── SO Period Section ─────────────────────────────────────────────────────────

function loadSoPeriods(id_so) {
    var $wrap = $('#soPeriodContent');
    $wrap.html('<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get('/wo-periods/by-so/' + id_so, function (periods) {
        window._soPeriods = periods;
        $wrap.html(renderSoPeriodList(periods, id_so));
    }).fail(function () {
        $wrap.html('<div class="text-muted small text-danger text-center py-2">Gagal memuat data period</div>');
    });
}

function renderSoPeriodList(periods, id_so) {
    if (!periods || !periods.length) {
        return '<div class="text-center text-muted py-3">' +
            '<i class="fa-solid fa-calendar-xmark fa-lg d-block mb-2 opacity-25"></i>' +
            '<span class="small">Belum ada period jadwal untuk SO ini</span></div>';
    }

    return periods.map(function (p) { return renderSoPeriodCard(p, id_so); }).join('');
}

function renderSoPeriodCard(p, id_so) {
    var tglMulai   = p.tanggal_mulai   ? p.tanggal_mulai.substring(0, 7)   : '—';
    var tglSelesai = p.tanggal_selesai ? p.tanggal_selesai.substring(0, 7) : '—';
    var interval   = p.interval_bulan  ? 'tiap ' + p.interval_bulan + ' bulan' : '—';

    var expectedWo = (p.tanggal_mulai && p.tanggal_selesai && p.interval_bulan)
        ? Math.floor(
            (new Date(p.tanggal_selesai) - new Date(p.tanggal_mulai)) / (1000 * 60 * 60 * 24 * 30) / p.interval_bulan
          )
        : null;

    var woListHtml = '';
    if (p.wos && p.wos.length) {
        woListHtml = p.wos.map(function (wo) {
            return '<a href="/work-orders?open=' + wo.id_wo + '" target="_blank" ' +
                'class="d-inline-flex align-items-center gap-1 me-2 mb-1 text-decoration-none" ' +
                'style="font-size:11px;background:var(--primary-100);color:var(--primary-700);' +
                'padding:2px 8px;border-radius:20px;font-weight:600;">' +
                '<i class="fa-solid fa-briefcase" style="font-size:9px;"></i>' +
                escSo(wo.no_wo) + '</a>';
        }).join('');
    } else {
        woListHtml = '<span class="small text-muted">Belum ada WO ter-assign</span>';
    }

    var assignedCount = p.wos ? p.wos.length : 0;
    var countBadge = expectedWo !== null
        ? '<span class="badge ' + (assignedCount >= expectedWo ? 'bg-success' : 'bg-warning text-dark') + ' bg-opacity-10" style="font-size:11px;">' +
            assignedCount + ' / ' + expectedWo + ' WO</span>'
        : '<span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:11px;">' + assignedCount + ' WO</span>';

    return '<div class="card mb-2 so-period-card" data-period-id="' + p.id_period + '" style="border:0.5px solid #e2e8f0;">' +
        '<div class="card-header py-2 px-3 d-flex justify-content-between align-items-center" style="background:#f8fafc;">' +
            '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                '<i class="fa-solid fa-location-dot" style="color:var(--primary-500);font-size:13px;"></i>' +
                '<span class="fw-semibold small">' + escSo(p.nama_site ?? '—') + '</span>' +
                countBadge +
            '</div>' +
            '<div class="d-flex gap-1">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-edit-period" ' +
                    'data-period-id="' + p.id_period + '" data-no-disable style="font-size:11px;" title="Edit period">' +
                    '<i class="fa-solid fa-pen"></i>' +
                '</button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-period" ' +
                    'data-period-id="' + p.id_period + '" data-no-disable style="font-size:11px;" title="Hapus period">' +
                    '<i class="fa-solid fa-trash"></i>' +
                '</button>' +
            '</div>' +
        '</div>' +
        '<div class="card-body px-3 py-2">' +
            '<div class="d-flex flex-wrap gap-3 mb-2">' +
                '<div>' +
                    '<div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Jadwal</div>' +
                    '<div class="small fw-semibold">' + tglMulai + ' s/d ' + tglSelesai + '</div>' +
                '</div>' +
                '<div>' +
                    '<div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Frekuensi</div>' +
                    '<div class="small fw-semibold">' + escSo(interval) + '</div>' +
                '</div>' +
                (p.keterangan ? '<div>' +
                    '<div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Keterangan</div>' +
                    '<div class="small">' + escSo(p.keterangan) + '</div>' +
                '</div>' : '') +
            '</div>' +
            '<div class="d-flex flex-wrap align-items-center gap-1" style="min-height:22px;">' +
                woListHtml +
            '</div>' +
        '</div>' +
    '</div>';
}

function renderSoPeriodForm(id_so, p) {
    var isEdit   = !!p;
    var action   = isEdit ? 'Edit' : 'Tambah';
    var siteId   = p ? p.id_site : '';
    var siteName = p ? (p.nama_site ?? '') : '';

    return '<div id="soPeriodFormWrap" class="p-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">' +
        '<div class="small fw-semibold text-muted mb-3">' +
            '<i class="fa-solid fa-calendar-plus me-1"></i>' + action + ' Period' +
        '</div>' +
        '<div class="row g-2">' +
            '<div class="col-md-12">' +
                '<label class="form-label form-label-sm text-muted mb-1">Lokasi (Site) <span class="text-danger">*</span></label>' +
                '<select id="soPeriodSite" style="width:100%"></select>' +
            '</div>' +
            '<div class="col-md-5">' +
                '<label class="form-label form-label-sm text-muted mb-1">Tanggal Mulai</label>' +
                '<input type="date" id="soPeriodMulai" class="form-control form-control-sm" value="' + (p ? (p.tanggal_mulai ?? '') : '') + '">' +
            '</div>' +
            '<div class="col-md-5">' +
                '<label class="form-label form-label-sm text-muted mb-1">Tanggal Selesai</label>' +
                '<input type="date" id="soPeriodSelesai" class="form-control form-control-sm" value="' + (p ? (p.tanggal_selesai ?? '') : '') + '">' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label form-label-sm text-muted mb-1">Interval (bln)</label>' +
                '<input type="number" id="soPeriodInterval" class="form-control form-control-sm" min="1" placeholder="2" value="' + (p ? (p.interval_bulan ?? '') : '') + '">' +
            '</div>' +
            '<div class="col-md-12">' +
                '<label class="form-label form-label-sm text-muted mb-1">Keterangan</label>' +
                '<input type="text" id="soPeriodKet" class="form-control form-control-sm" placeholder="opsional" value="' + (p ? escSo(p.keterangan ?? '') : '') + '">' +
            '</div>' +
        '</div>' +
        '<div class="d-flex justify-content-end gap-2 mt-3">' +
            '<button type="button" id="btnCancelSoPeriodForm" class="btn btn-outline-secondary btn-sm">Batal</button>' +
            '<button type="button" id="btnSaveSoPeriod" class="btn btn-primary btn-sm" ' +
                'data-period-id="' + (p ? p.id_period : '') + '" data-id-so="' + id_so + '">' +
                '<i class="fa-solid fa-check me-1"></i> Simpan' +
            '</button>' +
        '</div>' +
    '</div>';
}

function initSoPeriodSiteSelect2(siteId, siteName) {
    var $el = $('#soPeriodSite');
    $el.select2({
        dropdownParent: $('#soPeriodFormWrap'),
        placeholder: 'Pilih lokasi...',
        ajax: {
            url: '/business-relations/sites/select2',
            dataType: 'json',
            delay: 200,
            data: function (params) { return { q: params.term }; },
        },
    });
    if (siteId) {
        var opt = new Option(siteName || siteId, siteId, true, true);
        $el.append(opt).trigger('change');
    }
}

function initSoPeriodSection(id_so) {
    // Tambah Period
    $(document).on('click', '#btnTambahPeriodSo', function () {
        if ($('#soPeriodFormWrap').length) return;
        $('#soPeriodContent').prepend(renderSoPeriodForm(id_so, null));
        initSoPeriodSiteSelect2('', '');
    });

    // Edit Period
    $(document).on('click', '.btn-edit-period', function () {
        if ($('#soPeriodFormWrap').length) return;
        var periodId = $(this).data('period-id');
        var $card    = $(this).closest('.so-period-card');
        var periods  = window._soPeriods || [];
        var p        = periods.find(function (x) { return x.id_period == periodId; });
        if (!p) return;
        $card.before(renderSoPeriodForm(id_so, p));
        $card.hide();
        initSoPeriodSiteSelect2(p.id_site, p.nama_site);
        $('#btnCancelSoPeriodForm').data('restore-card', periodId);
    });

    // Batal form
    $(document).on('click', '#btnCancelSoPeriodForm', function () {
        var restoreId = $(this).data('restore-card');
        if (restoreId) { $('.so-period-card[data-period-id="' + restoreId + '"]').show(); }
        $('#soPeriodFormWrap').remove();
    });

    // Simpan (create or update)
    $(document).on('click', '#btnSaveSoPeriod', function () {
        var $btn     = $(this);
        var periodId = $btn.data('period-id');
        var idSo     = $btn.data('id-so');
        var siteId   = $('#soPeriodSite').val();
        if (!siteId) { alert('Lokasi wajib dipilih'); return; }

        var payload = {
            _token:          window.route.csrf,
            id_so:           idSo,
            id_site:         siteId,
            tanggal_mulai:   $('#soPeriodMulai').val()    || null,
            tanggal_selesai: $('#soPeriodSelesai').val()  || null,
            interval_bulan:  $('#soPeriodInterval').val() || null,
            keterangan:      $('#soPeriodKet').val()      || null,
        };

        var url    = periodId ? '/wo-periods/' + periodId : '/wo-periods';
        var method = periodId ? 'PUT' : 'POST';
        if (method === 'PUT') payload._method = 'PUT';

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: url, method: 'POST', data: payload,
            success: function () {
                $('#soPeriodFormWrap').remove();
                $('.so-period-card').show();
                loadSoPeriods(id_so);
            },
            error: function () { alert('Gagal menyimpan period'); },
            complete: function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Simpan'); },
        });
    });

    // Hapus Period
    $(document).on('click', '.btn-delete-period', function () {
        var periodId = $(this).data('period-id');
        if (!confirm('Hapus period ini? WO yang ter-assign akan dilepas dari period.')) return;
        $.ajax({
            url: '/wo-periods/' + periodId,
            method: 'POST',
            data: { _token: window.route.csrf, _method: 'DELETE' },
            success: function () { loadSoPeriods(id_so); },
            error:   function () { alert('Gagal menghapus period'); },
        });
    });
}

$(document).ready(function () {
    // Search WO / FWO
    $(document).on('input', '#woProgressSearch', function () {
        renderWoProgressView(currentWosData);
    });

    // Clear search
    $(document).on('click', '#btnClearWoSearch', function () {
        $('#woProgressSearch').val('');
        renderWoProgressView(currentWosData);
    });

    // Refresh WO Progress
    $(document).on('click', '#btnRefreshWoProgress', function () {
        const soId = $(this).data('so-id');
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        $('#woProgressSearch').val('');
        $('#btnClearWoSearch').addClass('d-none');
        loadWoProgress(soId, function () { $icon.removeClass('fa-spin'); });
    });

    // Toggle card ↔ table view
    $(document).on('click', '#btnToggleWoView', function () {
        woViewMode = woViewMode === 'card' ? 'table' : 'card';
        localStorage.setItem('wo_progress_view', woViewMode);
        renderWoProgressView(currentWosData);
    });

    // Expand/collapse FWO sub-row (table mode)
    $(document).on('click', '.wo-table-row', function () {
        const subrowId = $(this).data('subrow');
        const $sub     = $('#' + subrowId);
        const $chevron = $(this).find('.wo-table-chevron');
        const wasOpen  = $sub.is(':visible');
        $sub.slideToggle(150);
        $chevron.css('transform', wasOpen ? 'rotate(0deg)' : 'rotate(90deg)');
    });

    // Toggle expand/collapse WO detail (card mode)
    $(document).on('click', '.btn-wo-toggle', function () {
        const targetId = $(this).data('target');
        const $target  = $('#' + targetId);
        const $icon    = $(this).find('i');
        $target.slideToggle(200);
        $icon.toggleClass('fa-chevron-down fa-chevron-up');
    });

    page = new CrudPageController({
        primaryKey: "id_so",
        renderForm: renderForm,
        afterLoad: function (res) {
            loadWoProgress(res.id_so);
            // Load period section
            $.get('/wo-periods/by-so/' + res.id_so, function (periods) {
                window._soPeriods = periods;
                $('#soPeriodContent').html(renderSoPeriodList(periods, res.id_so));
            }).fail(function () {
                $('#soPeriodContent').html('<div class="text-muted small text-danger text-center py-2">Gagal memuat data period</div>');
            });
            initSoPeriodSection(res.id_so);
        },
    });
});
