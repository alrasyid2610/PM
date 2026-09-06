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

            // Attachment beneran (bukan cuma field kosong) → tampilkan
            // overlay progress upload, supaya user tidak mengira form
            // "hang" saat file besar sedang terkirim ke server.
            let progress = null;
            if (ajaxOptions.data instanceof FormData && formDataHasFile(ajaxOptions.data)) {
                progress = Notify.uploadProgress();
                ajaxOptions.xhr = function () {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function (e) {
                        if (!e.lengthComputable) return;
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progress.update(percent);
                        if (percent >= 100) progress.processing();
                    });
                    return xhr;
                };
            }

            ajaxOptions.success = function (res) {
                if (progress) progress.close();
                Notify.success(res.message || successMessage);

                if (onSuccess) {
                    onSuccess(res);
                } else if (redirect) {
                    window.location.href = redirect;
                }
            };

            ajaxOptions.error = function (xhr) {
                if (progress) progress.close();

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
