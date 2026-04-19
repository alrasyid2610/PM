let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: 'id',
        renderForm: renderForm,
        afterRender: function (res) {
            initPermissionMatrix('#detailContent');
        },
        onSave: function (id) {
            submitGroupForm(id);
        },
    });
});

function submitGroupForm(id) {
    const container = '#detailContent';
    const formData  = new FormData($(container).find('#detailForm')[0]);

    formData.delete('permissions[]');
    formData.set('permissions', JSON.stringify(collectPermissions(container)));

    $.ajax({
        url:         window.route.update + id,
        method:      'POST',
        data:        formData,
        processData: false,
        contentType: false,
        success: function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false })
                    .then(() => page.reloadTable());
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
            }
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
        }
    });
}
