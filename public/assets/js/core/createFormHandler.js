function submitCreateForm(options) {
    const formId = options.formId || "#createForm";
    const url = options.url || window.route?.store;
    const redirect = options.redirect || null;
    const filepond = options.filepond || null;
    const confirmMessage = options.confirmMessage || "Simpan Data?";
    const successMessage = options.successMessage || "Data berhasil disimpan";
    const onSuccess = options.onSuccess || null;
    const onError = options.onError || null;

    $(formId).on("submit", function (e) {
        e.preventDefault();

        const form = this;
        const hasFile = filepond !== null;

        Notify.confirm(confirmMessage, function () {
            let data;
            let ajaxOptions = {
                url: url,
                method: "POST",
            };

            if (hasFile) {
                data = new FormData(form);

                let pondInstance = null;

                if (typeof filepond === "string") {
                    const el = document.querySelector(filepond);
                    if (el) pondInstance = FilePond.find(el);
                } else if (
                    filepond &&
                    typeof filepond.getFiles === "function"
                ) {
                    pondInstance = filepond;
                }

                if (pondInstance) {
                    pondInstance.getFiles().forEach((fileItem) => {
                        data.append("attachments[]", fileItem.file);
                    });
                }

                ajaxOptions.data = data;
                ajaxOptions.processData = false;
                ajaxOptions.contentType = false;
            } else {
                ajaxOptions.data = $(form).serialize();
            }

            ajaxOptions.success = function (res) {
                Notify.success(res.message || successMessage);

                if (onSuccess) {
                    onSuccess(res);
                } else if (redirect) {
                    window.location.href = redirect;
                }
            };

            ajaxOptions.error = function (xhr) {
                if (onError) {
                    onError(xhr);
                    return;
                }

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors ?? {};
                    const msg = Object.values(errors)
                        .map((e) => e[0])
                        .join("<br>");
                    Notify.error(msg || "Validasi gagal");
                } else {
                    Notify.error(
                        xhr.responseJSON?.message ?? "Gagal menyimpan data",
                    );
                }
            };

            $.ajax(ajaxOptions);
        });
    });
}
