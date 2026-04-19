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

            const isModern = $btn.hasClass("btn-action-edit");

            if (!isEditing) {
                $(container)
                    .find("input, select, textarea")
                    .not("[data-no-disable]")
                    .prop("disabled", false)
                    .removeClass("disabled");

                $(".dynamic-table-wrapper")
                    .find("input, select, textarea, button")
                    .prop("disabled", false);

                if (isModern) {
                    $btn.addClass("editing")
                        .html('<i class="fa-solid fa-times"></i> Batal');

                    $btn.after(`
                        <button class="btn-action-save btn-save-context ms-0">
                            <i class="fa-solid fa-check"></i> Simpan
                        </button>
                    `);
                } else {
                    $btn.addClass("editing")
                        .removeClass("btn-warning")
                        .addClass("btn-secondary")
                        .html('<i class="fa-solid fa-times"></i>');

                    $btn.after(`
                        <button class="btn btn-success btn-sm btn-save-context ms-2">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    `);
                }

                if (onEditStart) onEditStart();
            } else {
                $(container)
                    .find("input, select, textarea")
                    .not("[data-no-disable]")
                    .prop("disabled", true)
                    .addClass("disabled");

                $(".dynamic-table-wrapper")
                    .find("input, select, textarea, button")
                    .prop("disabled", true);

                if (isModern) {
                    $btn.removeClass("editing")
                        .html('<i class="fa-solid fa-pen"></i> Edit');
                } else {
                    $btn.removeClass("editing")
                        .addClass("btn-warning")
                        .removeClass("btn-secondary")
                        .html('<i class="fa-solid fa-pen"></i>');
                }

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
