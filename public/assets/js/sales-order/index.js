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

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_so",
        renderForm: renderForm,
        initSelect: function () {
            $("#detail_kelompok").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });
        },
    });
});
