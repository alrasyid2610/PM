function submitCrudForm(options) {
    const formId = options.formId || "#detailForm";
    const id = options.id;
    const url = options.url || window.route.update;
    const reload = options.reload;
    const filepondInstance = options.filepond || null;

    let form = document.querySelector(formId);

    let formData = new FormData(form);

    let currentForms = getAllFormsData();
    let isFormChanged =
        JSON.stringify(window.initialForms) !== JSON.stringify(currentForms);

    // 🔹 dynamic table
    let currentItems = getDynamicTableData();

    let isItemsChanged = false;

    if (
        currentItems.length > 0 ||
        (window.initialItems && window.initialItems.length > 0)
    ) {
        let changes = compareItems(window.initialItems || [], currentItems);

        isItemsChanged =
            changes.inserted.length > 0 ||
            changes.updated.length > 0 ||
            changes.deleted.length > 0;
    }

    let isAttachmentChanged = filepondInstance && filepondInstance.getFiles().length > 0;

    if (!isFormChanged && !isItemsChanged && !isAttachmentChanged) {
        Notify.warning("Tidak ada perubahan data");
        return;
    }

    // handle filepond
    if (filepondInstance) {
        filepondInstance.getFiles().forEach((fileItem) => {
            formData.append("attachments[]", fileItem.file);
        });
    }

    Notify.confirm("Simpan Data?", function () {
        $.ajax({
            url: url + id,
            method: "POST",
            data: formData,

            processData: false,
            contentType: false,

            success: function (response) {
                Notify.success("Data berhasil diperbarui");

                if (reload) {
                    reload(id);
                }
            },

            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON) {
                    const json = xhr.responseJSON;
                    let html = '';
                    if (json.errors) {
                        const msgs = Object.values(json.errors).map(e => Array.isArray(e) ? e[0] : e);
                        html = '<ul style="text-align:left;margin:8px 0 0;padding-left:20px;">' +
                            msgs.map(m => `<li>${m}</li>`).join('') + '</ul>';
                    } else if (json.message) {
                        html = json.message;
                    }
                    Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: html });
                } else {
                    Notify.error("Gagal memperbarui data");
                }
            },
        });
    });
}
