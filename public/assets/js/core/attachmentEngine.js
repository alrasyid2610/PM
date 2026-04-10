function createFileUploader(selector, options = {}) {
    const defaultOptions = {
        allowMultiple: true,

        acceptedFileTypes: [
            "image/*",
            "application/pdf",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        ],

        labelIdle:
            'Drag & Drop file atau <span class="filepond--label-action">Browse</span>',
    };

    const config = { ...defaultOptions, ...options };

    const element = document.querySelector(selector);

    if (!element) return null;

    return FilePond.create(element, config);
}

function destroyUploader(instance) {
    if (instance) {
        instance.destroy();
    }
}
