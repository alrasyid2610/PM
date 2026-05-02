function bindEditToggle(options) {
    const container = options.container;
    const onEditStart = options.onEditStart;
    const onEditCancel = options.onEditCancel;
    const onSave = options.onSave;

    let _snapshot = null;

    function takeSnapshot() {
        _snapshot = [];
        $(container).find("input, select, textarea").not("[data-no-disable]").each(function () {
            const $el = $(this);
            const entry = { el: this };

            if ($el.is('input[type="checkbox"]') || $el.is('input[type="radio"]')) {
                entry.checked = this.checked;
            } else {
                entry.val = $el.val();
            }

            if ($el.is('select') && $el.hasClass('select2-hidden-accessible')) {
                entry.isSelect2 = true;
                entry.selectedText = $el.find('option:selected').text();
            }

            _snapshot.push(entry);
        });
    }

    function restoreSnapshot() {
        if (!_snapshot) return;
        _snapshot.forEach(function (item) {
            const $el = $(item.el);

            if (item.checked !== undefined) {
                $el.prop('checked', item.checked).trigger('change');
            } else if (item.isSelect2) {
                if (item.val) {
                    if ($el.find('option[value="' + item.val + '"]').length === 0) {
                        $el.append(new Option(item.selectedText || item.val, item.val, true, true));
                    }
                    $el.val(item.val);
                } else {
                    $el.val(null);
                }
                $el.trigger('change');
            } else {
                $el.val(item.val);
            }
        });
        _snapshot = null;
    }

    $(container)
        .off("click", ".btn-edit-context")
        .on("click", ".btn-edit-context", function (e) {
            e.preventDefault();

            const $btn = $(this);
            const isEditing = $btn.hasClass("editing");
            const isModern = $btn.hasClass("btn-action-edit");

            if (!isEditing) {
                takeSnapshot();

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
                restoreSnapshot();

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
            _snapshot = null;
            if (onSave) onSave();
        });
}
