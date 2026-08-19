let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_personnel",
        renderForm: renderForm,
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Personnel?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    setTimeout(function () { window.location.href = window.location.pathname; }, 1000);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                },
            });
        });
    });

    // ── Buatkan Akun Login ──────────────────────────────────────────────────────
    $(document).on('click', '.btn-create-account', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Buatkan Akun Login',
            html:
                '<input id="swalAccEmail" type="email" class="swal2-input" placeholder="Email">' +
                '<input id="swalAccPassword" type="password" class="swal2-input" placeholder="Password (min 8 karakter)">',
            showCancelButton: true,
            confirmButtonText: 'Buat Akun',
            cancelButtonText: 'Batal',
            preConfirm: function () {
                const email = document.getElementById('swalAccEmail').value.trim();
                const password = document.getElementById('swalAccPassword').value;
                if (!email) { Swal.showValidationMessage('Email wajib diisi'); return false; }
                if (!password || password.length < 8) { Swal.showValidationMessage('Password minimal 8 karakter'); return false; }
                return { email, password };
            },
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: window.route.createAccount + id + '/account',
                method: 'POST',
                data: { _token: window.route.csrf, email: result.value.email, password: result.value.password },
                success: function (res) {
                    Notify.success(res.message || 'Akun berhasil dibuat');
                    page.loadDetail(id);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal membuat akun');
                },
            });
        });
    });

    // ── Cabut Akses ─────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-revoke-account', function () {
        const id = $(this).data('id');
        Notify.confirm('Cabut akses login personnel ini?', function () {
            $.ajax({
                url: window.route.revokeAccount + id + '/account',
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Akses berhasil dicabut');
                    page.loadDetail(id);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal mencabut akses');
                },
            });
        });
    });
});
