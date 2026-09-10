window.Notify = {
    success(message = "Berhasil") {
        return Swal.fire({
            toast: true,
            position: "top-end",
            icon: "success",
            title: "Berhasil",
            text: message,
            timer: 1500,
            showConfirmButton: false,
        });
    },

    // Toast generik, ringkas — dipakai untuk aksi cepat/tidak mengganggu yang
    // bukan submit form (mis. drag-and-drop reorder baris).
    toast(message, icon = "success") {
        return Swal.fire({
            toast: true,
            position: "top-end",
            icon: icon,
            title: message,
            timer: 1200,
            showConfirmButton: false,
        });
    },

    error(message = "Terjadi kesalahan") {
        return Swal.fire({
            icon: "error",
            title: "Gagal",
            text: message,
        });
    },

    warning(message = "Perhatian") {
        return Swal.fire({
            icon: "warning",
            title: "Perhatian",
            text: message,
        });
    },

    info(message = "Informasi") {
        return Swal.fire({
            icon: "info",
            title: "Informasi",
            text: message,
        });
    },

    confirmDelete(title = "Hapus data ini?", callback) {
        return Swal.fire({
            icon: "warning",
            title: title,
            html: `<span style="color:#6b7280;font-size:14px;">Data akan dipindahkan ke arsip dan tidak akan muncul di daftar.</span>`,
            showCancelButton: true,
            confirmButtonText:
                '<i class="fa-solid fa-trash me-1"></i> Ya, Hapus',
            cancelButtonText: "Batal",
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#6b7280",
            reverseButtons: true,
            focusCancel: true,
        }).then((result) => {
            if (result.isConfirmed && typeof callback === "function") {
                callback();
            }
        });
    },

    confirm(
        title = "Simpan Perubahan?",
        // message = "Yakin ingin melanjutkan?",
        callback,
    ) {
        var message = "Yakin ingin melanjutkan?";
        if (title === "Simpan Data?") {
            title = "Simpan Data?";
            message =
                "Pastikan semua data yang Anda masukkan sudah benar sebelum disimpan.";
        }

        return Swal.fire({
            icon: "question",
            title: title,
            text: message,
            showCancelButton: true,
            confirmButtonText: "Ya",
            cancelButtonText: "Batal",
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed && typeof callback === "function") {
                callback();
            }
        });
    },

    // Overlay progress upload — dipakai submitCreateForm/submitCrudForm saat
    // form membawa attachment. Return controller {update, processing, close}
    // supaya kode pemanggil bisa update persentase real-time dari event
    // xhr.upload.progress, lalu pindah ke mode "memproses" begitu upload
    // selesai (100%) tapi server belum sempat merespons.
    uploadProgress(initialTitle = "Mengupload File...") {
        Swal.fire({
            html: `
                <div class="pm-upload-progress">
                    <div class="pm-upload-progress-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="pm-upload-progress-title" id="pmUploadProgressTitle">${initialTitle}</div>
                    <div class="pm-upload-progress-bar-wrap">
                        <div class="pm-upload-progress-bar-fill" id="pmUploadProgressFill"></div>
                    </div>
                    <div class="pm-upload-progress-percent" id="pmUploadProgressPercent">0%</div>
                    <div class="pm-upload-progress-sub">Mohon tidak menutup atau me-refresh halaman ini</div>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: false,
            showCloseButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            width: 380,
        });

        return {
            update(percent) {
                const fill = document.getElementById("pmUploadProgressFill");
                const pct = document.getElementById("pmUploadProgressPercent");
                if (fill) {
                    fill.classList.remove("is-indeterminate");
                    fill.style.width = percent + "%";
                }
                if (pct) pct.textContent = percent + "%";
            },
            processing() {
                const fill = document.getElementById("pmUploadProgressFill");
                const pct = document.getElementById("pmUploadProgressPercent");
                const title = document.getElementById("pmUploadProgressTitle");
                if (fill) fill.classList.add("is-indeterminate");
                if (pct) pct.textContent = "";
                if (title) title.textContent = "Memproses data...";
            },
            close() {
                Swal.close();
            },
        };
    },

    validation(errors) {
        if (!errors) return;

        let msg = Object.values(errors)
            .map((e) => e[0])
            .join("<br>");

        return Swal.fire({
            icon: "error",
            title: "Validasi Gagal",
            html: msg,
        });
    },
};
