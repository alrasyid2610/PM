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

    console.log(isFormChanged, window.initialForms, currentForms);

    if (!isFormChanged && !isItemsChanged) {
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

            error: function () {
                Notify.error("Gagal memperbarui data");
            },
        });
    });
}
