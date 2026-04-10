function bindEditToggle(options) {
    const container = options.container;
    const onEditStart = options.onEditStart;
    const onEditCancel = options.onEditCancel;
    const onSave = options.onSave;

    $(container)
        .off("click", ".btn-edit-context")
        .on("click", ".btn-edit-context", function (e) {
            e.preventDefault();

            const $btn = $(this);
            const isEditing = $btn.hasClass("editing");
            console.log("Tombol edit diklik", isEditing);

            if (!isEditing) {
                $(container)
                    .find("input, select, textarea")
                    .prop("disabled", false)
                    .removeClass("disabled");

                $(".dynamic-table-wrapper")
                    .find("input, select, textarea, button")
                    .prop("disabled", false);

                $btn.addClass("editing")
                    .removeClass("btn-warning")
                    .addClass("btn-secondary")
                    .html('<i class="fa-solid fa-times"></i>');

                $btn.after(`
                <button class="btn btn-success btn-sm btn-save-context ms-2">
                    <i class="fa-solid fa-check"></i>
                </button>
            `);

                if (onEditStart) onEditStart();
            } else {
                $(container)
                    .find("input, select, textarea")
                    .prop("disabled", true)
                    .addClass("disabled");

                $(".dynamic-table-wrapper")
                    .find("input, select, textarea, button")
                    .prop("disabled", true);

                $btn.removeClass("editing")
                    .addClass("btn-warning")
                    .removeClass("btn-secondary")
                    .html('<i class="fa-solid fa-pen"></i>');

                $(".btn-save-context").remove();

                if (onEditCancel) onEditCancel();
            }
        });

    $(container)
        .off("click", ".btn-save-context")
        .on("click", ".btn-save-context", function (e) {
            e.preventDefault();

            if (onSave) onSave();
        });
}
