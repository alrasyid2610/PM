let menuConfig = [];

async function loadMenuConfig() {
    if (menuConfig.length > 0) return menuConfig;
    const res = await $.getJSON('/api/menus');
    menuConfig = res;
    return menuConfig;
}

function renderPermissionMatrix(permissions) {
    const perms = permissions || {};
    let rows = '';

    menuConfig.forEach(group => {
        rows += `
        <tr class="table-secondary">
            <td class="text-center">
                <input type="checkbox" class="form-check-input select-all-group disabled" data-group="${group.group}" title="Pilih semua ${group.group}">
            </td>
            <td colspan="4" class="fw-semibold ps-2">
                <i class="fa-solid ${group.icon} me-2 opacity-75"></i>${group.group}
            </td>
        </tr>`;

        group.items.forEach(item => {
            const p = perms[item.slug] || {};
            rows += `
            <tr>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input select-all-row disabled" data-slug="${item.slug}" title="Pilih semua aksi">
                </td>
                <td class="ps-3">${item.label}</td>
                <td class="text-center">
                    <input type="checkbox" name="permissions[${item.slug}][can_read]" value="1" class="form-check-input perm-check disabled" data-col="can_read" data-slug="${item.slug}" ${p.can_read ? 'checked' : ''}>
                </td>
                <td class="text-center">
                    <input type="checkbox" name="permissions[${item.slug}][can_create]" value="1" class="form-check-input perm-check disabled" data-col="can_create" data-slug="${item.slug}" ${p.can_create ? 'checked' : ''}>
                </td>
                <td class="text-center">
                    <input type="checkbox" name="permissions[${item.slug}][can_update]" value="1" class="form-check-input perm-check disabled" data-col="can_update" data-slug="${item.slug}" ${p.can_update ? 'checked' : ''}>
                </td>
                <td class="text-center">
                    <input type="checkbox" name="permissions[${item.slug}][can_delete]" value="1" class="form-check-input perm-check disabled" data-col="can_delete" data-slug="${item.slug}" ${p.can_delete ? 'checked' : ''}>
                </td>
            </tr>`;
        });
    });

    return `
    <div class="table-responsive">
        <table class="table table-bordered table-sm permission-matrix mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" width="50">
                        <input type="checkbox" class="form-check-input select-all-global disabled" title="Pilih semua">
                    </th>
                    <th>Menu</th>
                    <th class="text-center" width="90">Read<br><input type="checkbox" class="form-check-input select-all-col disabled mt-1" data-col="can_read"></th>
                    <th class="text-center" width="90">Create<br><input type="checkbox" class="form-check-input select-all-col disabled mt-1" data-col="can_create"></th>
                    <th class="text-center" width="90">Update<br><input type="checkbox" class="form-check-input select-all-col disabled mt-1" data-col="can_update"></th>
                    <th class="text-center" width="90">Delete<br><input type="checkbox" class="form-check-input select-all-col disabled mt-1" data-col="can_delete"></th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

function collectPermissions(container) {
    const result = {};
    menuConfig.forEach(group => {
        group.items.forEach(item => {
            result[item.slug] = {
                can_read:   $(container).find(`input[name="permissions[${item.slug}][can_read]"]`).is(':checked') ? 1 : 0,
                can_create: $(container).find(`input[name="permissions[${item.slug}][can_create]"]`).is(':checked') ? 1 : 0,
                can_update: $(container).find(`input[name="permissions[${item.slug}][can_update]"]`).is(':checked') ? 1 : 0,
                can_delete: $(container).find(`input[name="permissions[${item.slug}][can_delete]"]`).is(':checked') ? 1 : 0,
            };
        });
    });
    return result;
}

function initPermissionMatrix(container) {
    $(container).on('change', '.select-all-global', function () {
        const checked = $(this).is(':checked');
        $(container).find('.perm-check, .select-all-row, .select-all-group, .select-all-col').prop('checked', checked);
    });

    $(container).on('change', '.select-all-col', function () {
        const col = $(this).data('col');
        const checked = $(this).is(':checked');
        $(container).find(`.perm-check[data-col="${col}"]`).prop('checked', checked);
        syncGlobal(container);
    });

    $(container).on('change', '.select-all-group', function () {
        const checked = $(this).is(':checked');
        $(this).closest('tr').nextUntil('tr.table-secondary').find('.perm-check, .select-all-row').prop('checked', checked);
        syncGlobal(container);
    });

    $(container).on('change', '.select-all-row', function () {
        const slug = $(this).data('slug');
        const checked = $(this).is(':checked');
        $(container).find(`.perm-check[data-slug="${slug}"]`).prop('checked', checked);
        syncGlobal(container);
    });

    $(container).on('change', '.perm-check', function () {
        const slug = $(this).data('slug');
        const total = $(container).find(`.perm-check[data-slug="${slug}"]`).length;
        const totalChecked = $(container).find(`.perm-check[data-slug="${slug}"]:checked`).length;
        $(container).find(`.select-all-row[data-slug="${slug}"]`).prop('checked', total === totalChecked);
        syncGlobal(container);
    });
}

function syncGlobal(container) {
    const total = $(container).find('.perm-check').length;
    const totalChecked = $(container).find('.perm-check:checked').length;
    $(container).find('.select-all-global').prop('checked', total === totalChecked);
}

async function renderForm(res) {
    await loadMenuConfig();

    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- SECTION 1: INFO GRUP -->
    ${formGroup.sectionCard(
        { icon: 'fa-layer-group', color: 'icon-navy', title: 'Informasi Grup', subtitle: 'Nama dan deskripsi departemen', editTitle: 'Edit Grup' },
        `<div class="row g-3 form-1">
                    ${formGroup.text("name", "Nama Grup", res.name, true, { className: "col-md-5" })}
                    ${formGroup.text("description", "Deskripsi", res.description || '', false, { className: "col-md-7" })}
                </div>`
    )}

    <!-- SECTION 2: PERMISSION MATRIX -->
    ${formGroup.sectionCard(
        { icon: 'fa-shield-halved', color: 'icon-green', title: 'Hak Akses Menu', subtitle: 'Konfigurasi akses grup per menu dan per aksi' },
        `${renderPermissionMatrix(res.permissions)}`
    )}

</form>`;
}
