let table;
let brFilter = {
    id: null,
    text: null,
};

let selectedRow = {
    id_site: null,
    id_br: null,
};

$(document).ready(function () {
    const advanceSearch = document.getElementById("advanceSearchForm");
    const toggleBtn = document.getElementById("toggleAdvanceSearch");

    if (!advanceSearch || !toggleBtn) return;

    const bsCollapse = new bootstrap.Collapse(advanceSearch, { toggle: false });
    bsCollapse.hide();
    toggleBtn.textContent = "Show";

    toggleBtn.addEventListener("click", function () {
        const isShown = advanceSearch.classList.contains("show");
        isShown ? bsCollapse.hide() : bsCollapse.show();
        toggleBtn.textContent = isShown ? "Show" : "Hide";
    });

    loadSummary();

    table = $("#businessRelationTable").DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: window.route.data,
            data: function (d) {
                d.filter_type = brFilter.type;
                d.filter_value = brFilter.value;
            },
        },
        columns: [
            { data: "DT_RowIndex", orderable: false, searchable: false },
            { data: "nama" },
            { data: "entitas" },
            { data: "nama_lokasi" },
            { data: "is_kantor_pusat" },
            { data: "alamat_lengkap" },
            { data: "is_aktif" },
            { data: "created_at" },
        ],
    });

    $("#businessRelationTable tbody").on("click", "tr", function () {
        if ($(event.target).closest("button").length) return;

        console.log("row clicked, show detail tab");
        const data = table.row(this).data();
        if (!data) return;

        selectedRow.id_site = data.id_site;
        selectedRow.id_br = data.id_br;

        loadDetail(data.id_site);

        $("#businessRelationTable tr").removeClass("table-active");
        $(this).addClass("table-active");

        // pindah ke tab Detail
        const detailTab = new bootstrap.Tab(
            document.querySelector("#detail-tab"),
        );
        detailTab.show();
    });

    $("#company_id").select2({
        placeholder: "-- Semua --",
        allowClear: true,
        tags: true,
        minimumInputLength: 2,
        ajax: {
            url: window.route.select2,
            dataType: "json",
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
        },
    });
    // simpan nilai SETIAP kali user memilih / mengetik
    $("#company_id").on("change", function () {
        const selected = $(this).select2("data")[0];

        if (selected) {
            brFilter.id = selected.id;
            brFilter.text = selected.text;
        } else {
            brFilter.id = null;
            brFilter.text = null;
        }
    });

    // Edit Button Click
    $(document).on("click", ".btn-edit-site", function () {
        const id = $(this).data("id");

        $.get("/business-relation-sites/" + id, function (res) {
            $("#edit_id_site").val(res.id_site);
            $("#edit_id_br").val(res.id_br);

            $("#edit_nama_lokasi").val(res.nama_lokasi);
            $("#edit_is_kantor_pusat").val(res.is_kantor_pusat);
            $("#edit_is_aktif").val(res.is_aktif);

            $("#edit_alamat_lengkap").val(res.alamat_lengkap);
            $("#edit_provinsi").val(res.provinsi);
            $("#edit_kota_kabupaten").val(res.kota_kabupaten);
            $("#edit_kecamatan").val(res.kecamatan);
            $("#edit_kelurahan").val(res.kelurahan);
            $("#edit_kode_pos").val(res.kode_pos);
            $("#edit_kawasan_bisnis").val(res.kawasan_bisnis);
            $("#edit_gedung").val(res.gedung);
            $("#edit_alamat").val(res.alamat);
            $("#edit_npwp_cabang").val(res.npwp_cabang);

            $("#edit_created_at").val(res.created_at);
            $("#edit_updated_at").val(res.updated_at);

            $("#editBrsModal").modal("show");
        });
    });
});

$("#company_id").on("change", function () {
    const selected = $(this).select2("data")[0];

    if (!selected) {
        brFilter.value = null;
        brFilter.type = null;
        return;
    }

    if (!isNaN(selected.id)) {
        // pilih BR existing
        brFilter.value = selected.id;
        brFilter.type = "id";
    } else {
        // ketik BR baru / bebas
        brFilter.value = selected.text;
        brFilter.type = "text";
    }
});

$("#btn-search").on("click", function () {
    table.ajax.reload();
    loadSummary();
});

$("#businessRelationTable tbody").on("click", "td.dt-control", function () {
    console.log("clicked");
    const tr = $(this).closest("tr");
    const row = table.row(tr);

    if (row.child.isShown()) {
        row.child.hide();
        tr.removeClass("shown");
    } else {
        const url = site + row.data().id_br + "/sites";
        $.get(url, function (res) {
            row.child(formatSites(res)).show();
            tr.addClass("shown");
        });
    }
});

document.getElementById("data-tab").addEventListener("shown.bs.tab", () => {
    $("#detailContent").html("Pilih data pada tab Data untuk melihat detail");
});

$(document).on("click", ".btn-edit", function () {
    const id = $(this).data("id");

    $.get("/business-relations/" + id, function (res) {
        $("#edit_id_br").val(res.id_br);
        $("#edit_nama").val(res.nama);
        $("#edit_entitas").val(res.entitas);
        $("#edit_kepemilikan").val(res.kepemilikan);
        $("#edit_npwp").val(res.npwp);
        $("#edit_npwp_alamat").val(res.npwp_alamat);
        $("#edit_kategori_bisnis").val(res.kategori_bisnis);
        $("#edit_sub_kategori_bisnis").val(res.sub_kategori_bisnis);
        $("#edit_website").val(res.website);
        $("#edit_nomor_telepon").val(res.nomor_telepon);
        $("#edit_is_aktif").val(res.is_aktif);
        $("#edit_created_at").val(res.created_at);
        $("#edit_updated_at").val(res.updated_at);

        $("#editModal").modal("show");
    });
});

$(document).on("click", ".btn-delete", function () {
    const id = $(this).data("id");

    Swal.fire({
        title: "Yakin?",
        text: "Data ini akan dihapus permanen",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "/business-relations/" + id,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}",
                },
                success: function () {
                    Swal.fire("Deleted!", "Data berhasil dihapus", "success");
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    Swal.fire(
                        "Gagal",
                        xhr.responseJSON?.message ?? "Terjadi kesalahan",
                        "error",
                    );
                },
            });
        }
    });
});

$("#editForm").on("submit", function (e) {
    e.preventDefault();

    const id = $("#edit_id_br").val();

    $.ajax({
        url: "/business-relations/" + id,
        type: "PUT",
        data: $(this).serialize(),
        success: function (res) {
            Swal.fire("Berhasil", res.message, "success");
            $("#editModal").modal("hide");

            // refresh table tanpa reset pagination
            table.ajax.reload(null, false);
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                let msg = Object.values(errors)
                    .map((e) => e[0])
                    .join("<br>");
                Swal.fire("Validasi gagal", msg, "error");
            } else {
                Swal.fire("Gagal", "Terjadi kesalahan", "error");
            }
        },
    });
});

function loadSummary() {
    $.ajax({
        url: window.route.summary,
        data: {
            filter_type: brFilter.type,
            filter_value: brFilter.value,
        },
        success: function (res) {
            $("#totalKantorPusat").text(res.kantor_pusat);
            $("#totalKantorCabang").text(res.kantor_cabang);
        },
    });
}

function loadDetail(id_site) {
    $("#detailContent").html("Loading...");

    $.get(window.route.site + id_site + "/detail", function (res) {
        console.log(res);
        $("#detailContent").html(`
                <div class="row g-3">

                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3>Business Relation</h3>
                            <button
                                class="btn btn-warning btn-sm btn-edit-context"
                                data-br="${res.id_br}"
                                data-site="${res.id_site}"
                                title="Edit Business Relation">
                                <i class="fa-solid fa-pen"></i>
                            </button>

                           

                        </div>
                        <table class="table table-sm">
                            <tr><th width="300">Nama Perusahaan</th><td>${res.nama_br}</td></tr>
                            <tr><th>Entitas</th><td>${res.entitas}</td></tr>
                            <tr><th>Kepemilikan</th><td>${res.kepemilikan ?? "-"}</td></tr>
                            <tr><th>NPWP</th><td>${res.npwp ?? "-"}</td></tr>
                            <tr><th>Alamat NPWP</th><td>${res.npwp_alamat ?? "-"}</td></tr>
                            <tr><th>Kategori Bisnis</th><td>${res.kategori_bisnis ?? "-"}</td></tr>
                            <tr><th>Sub Kategori Bisnis</th><td>${res.sub_kategori_bisnis ?? "-"}</td></tr>
                            <tr><th>Website</th><td>${res.website ?? "-"}</td></tr>
                            <tr><th>Nomor Telepon</th><td>${res.nomor_telepon ?? "-"}</td></tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    ${res.br_is_aktif == 1 ? "<span class='badge bg-primary'>Aktif</span>" : "<span class='badge bg-secondary'>Non Aktif</span>"}
                                </td>
                            </tr>
                            <tr><th>Created At</th><td>${res.br_created_at ?? "-"}</td></tr>
                            <tr><th>Updated At</th><td>${res.br_updated_at ?? "-"}</td></tr>
                        </table>
                    </div>

                    <div class="col-md-12">
                        <h3>Site</h3>
                        <table class="table table-sm">
                            <tr>
                                <th width="300">Nama</th>
                                <td>${res.nama_lokasi}</td>
                            </tr>
                            <tr>
                                <th>Kantor Pusat</th>
                                <td>${res.is_kantor_pusat == 1 ? "<span class='badge bg-primary'>Ya</span>" : "<span class='badge bg-secondary'>Tidak</span>"}</td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td>${res.alamat_lengkap}</td>
                            </tr>
                            <tr>
                                <th>Provinsi</th>
                                <td>${res.provinsi}</td>
                            </tr>
                            <tr>
                                <th>Kota/Kabupaten</th>
                                <td>${res.kota_kabupaten}</td>
                            </tr>
                            <tr>
                                <th>Kecamatan</th>
                                <td>${res.kecamatan}</td>
                            </tr>
                            <tr>
                                <th>Kelurahan</th>
                                <td>${res.kelurahan}</td>
                            </tr>
                            <tr>
                                <th>Kode Pos</th>
                                <td>${res.kode_pos}</td>
                            </tr>
                            <tr>
                                <th>Kawasan Bisnis</th>
                                <td>${res.kawasan_bisnis ?? "-"}</td>
                            </tr>
                            <tr>
                                <th>Gedung</th>
                                <td>${res.gedung ?? "-"}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    ${res.s_is_aktif === 1 ? "<span class='badge bg-primary'>Aktif</span>" : "<span class='badge bg-secondary'>Non Aktif</span>"}
                                </td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>${res.s_created_at ?? "-"}</td>
                            </tr>
                            <tr>
                                <th>Updated At</th>
                                <td>${res.s_updated_at ?? "-"}</td>
                            </tr>
                        </table>
                    </div>

                </div>
            `);

        $(".btn-edit-context").on("click", function () {
            console.log("edit context clicked");
            $.post(
                "/business-relations/edit-context",
                {
                    _token: window.route.csrf,
                    id_br: $(this).data("br"),
                    id_site: $(this).data("site"),
                },
                function (res) {
                    window.location.href = res.redirect;
                },
            );
        });
    });
}
