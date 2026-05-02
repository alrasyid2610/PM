let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: 'id',
        renderForm: renderForm,
        initSelect: function () {
            $('select[name="menu_group_id"]', '#detailContent').select2({
                width: '100%',
                dropdownParent: $('#detailContent'),
                placeholder: '-- Tanpa Grup --',
                allowClear: true,
            });
        },
        afterRender: function (res) {
            initPermissionMatrix();
        },
        onSave: function (id) {
            submitUserForm(id);
        },
    });
});

function collectPermissions() {
    const container = '#detailContent';
    const permissions = {};

    menuConfig.forEach(group => {
        group.items.forEach(item => {
            permissions[item.slug] = {
                can_read:   $(`${container} [name="permissions[${item.slug}][can_read]"]`).is(':checked') ? 1 : 0,
                can_create: $(`${container} [name="permissions[${item.slug}][can_create]"]`).is(':checked') ? 1 : 0,
                can_update: $(`${container} [name="permissions[${item.slug}][can_update]"]`).is(':checked') ? 1 : 0,
                can_delete: $(`${container} [name="permissions[${item.slug}][can_delete]"]`).is(':checked') ? 1 : 0,
            };
        });
    });

    return permissions;
}

function submitUserForm(id) {
    const form     = document.querySelector('#detailForm');
    const formData = new FormData(form);

    for (let key of [...formData.keys()]) {
        if (key.startsWith('permissions')) formData.delete(key);
    }
    formData.set('permissions', JSON.stringify(collectPermissions()));

    Notify.confirm('Simpan Data?', function () {
        $.ajax({
            url:         window.route.update + id,
            method:      'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    Notify.success('Data berhasil diperbarui');
                    page.loadDetail(id);
                } else {
                    Notify.error(res.message || 'Gagal menyimpan data');
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    const first  = Object.values(errors)[0]?.[0];
                    Notify.error(first || 'Validasi gagal');
                } else {
                    Notify.error('Gagal menyimpan data');
                }
            },
        });
    });
}
