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
