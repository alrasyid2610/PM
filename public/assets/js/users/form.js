// Pilihan Status Kepegawaian — harus sama dengan App\Support\StatusKepegawaian
const STATUS_KEPEGAWAIAN_OPTIONS = [
    { value: "hl", label: "HL" },
    { value: "kontrak_gol_1", label: "Kontrak Gol. 1" },
    { value: "kontrak_gol_2", label: "Kontrak Gol. 2" },
    { value: "kontrak_gol_3", label: "Kontrak Gol. 3" },
    { value: "magang", label: "Magang" },
];

let menuConfig  = [];
let groupOptions = [];

async function loadMenuConfig() {
    if (menuConfig.length > 0) return menuConfig;
    const res = await $.getJSON('/api/menus');
    menuConfig = res;
    return menuConfig;
}

async function loadGroupOptions() {
    if (groupOptions.length > 0) return groupOptions;
    const res = await $.getJSON('/api/menu-groups');
    groupOptions = res;
    return groupOptions;
}

function renderGroupSelect(selectedId) {
    const placeholder = '<option value="">-- Tanpa Grup --</option>';
    const opts = groupOptions.map(g =>
        `<option value="${g.id}" ${g.id == selectedId ? 'selected' : ''}>${g.name}</option>`
    ).join('');
    return placeholder + opts;
}

// Grup "Akses Tab" (permission_mode 'tabs'): baris per halaman berisi chip centang per tab,
// disimpan di can_update. Tidak ada satu pun tab tercentang → semua tab tampil (aturan canTab()).
function renderTabAccessRows(group, perms) {
    const sections = {};
    group.items.forEach(item => {
        const key = item.section || group.group;
        (sections[key] = sections[key] || []).push(item);
    });

    return Object.keys(sections).map(section => {
        const items = sections[section];
        const chips = items.map(item => {
            const p = perms[item.slug] || {};
            return `
            <label class="d-inline-flex align-items-center gap-1 border rounded px-2 py-1 me-1 mb-1" style="font-size:12px;cursor:pointer;">
                <input type="checkbox" name="permissions[${item.slug}][can_update]" value="1" class="form-check-input perm-check disabled mt-0"
                    data-col="can_update" data-slug="${item.slug}" data-tabsection="${section}" ${p.can_update ? 'checked' : ''}>
                <i class="fa-solid ${item.icon || 'fa-table-columns'} opacity-50"></i>${item.label}
            </label>`;
        }).join('');

        return `
        <tr data-search="akses tab ${section.toLowerCase()} ${items.map(i => i.label.toLowerCase()).join(' ')}">
            <td class="text-center">
                <input type="checkbox" class="form-check-input select-all-tabs disabled" data-tabsection="${section}" title="Pilih semua tab ${section}">
            </td>
            <td class="ps-3">
                <div class="fw-semibold">${section}</div>
                <div class="text-muted" style="font-size:11px;">Tab detail yang tampil</div>
            </td>
            <td colspan="4">
                ${chips}
                <div class="text-muted" style="font-size:11px;">Kosongkan semua = semua tab tampil. Centang sebagian = hanya tab itu yang tampil.</div>
            </td>
        </tr>`;
    }).join('');
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

        const isActionGroup = group.permission_mode === 'action';

        // Akses Tab: 1 baris per halaman, tiap tab = chip centang (bukan 1 baris per tab)
        if (group.permission_mode === 'tabs') {
            rows += renderTabAccessRows(group, perms);
            return;
        }

        group.items.forEach(item => {
            const p = perms[item.slug] || {};

            if (isActionGroup) {
                // Aksi granular (bukan menu CRUD) — cuma 1 toggle "Diizinkan",
                // disimpan di kolom can_update supaya tidak perlu skema baru.
                rows += `
                <tr data-search="${item.label.toLowerCase()} ${group.group.toLowerCase()}">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input select-all-row disabled" data-slug="${item.slug}" title="Pilih semua aksi">
                    </td>
                    <td class="ps-3">${item.label}</td>
                    <td colspan="4" class="text-center">
                        <div class="form-check form-switch d-inline-flex align-items-center gap-2 mb-0">
                            <input type="checkbox" name="permissions[${item.slug}][can_update]" value="1" class="form-check-input perm-check disabled" role="switch" data-col="can_update" data-slug="${item.slug}" ${p.can_update ? 'checked' : ''}>
                            <label class="form-check-label text-muted" style="font-size:12px;">Diizinkan</label>
                        </div>
                    </td>
                </tr>`;
                return;
            }

            rows += `
            <tr data-search="${item.label.toLowerCase()} ${group.group.toLowerCase()}">
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
    <div class="mb-3">
        <div class="pm-search">
            <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="perm-search" placeholder="Cari menu..." data-no-disable>
            <button type="button" id="perm-search-clear" class="pm-search-clear d-none" title="Hapus" data-no-disable>
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    </div>
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

async function renderForm(res) {
    await loadMenuConfig();
    await loadGroupOptions();

    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- SECTION 1: INFORMASI USER -->
    ${formGroup.sectionCard(
        { icon: 'fa-user', color: 'icon-navy', title: 'Informasi User', subtitle: 'Data akun pengguna sistem', editTitle: 'Edit User' },
        `<div class="row g-3 form-1">
                    ${formGroup.text("name", "Nama", res.name, true, { className: "col-md-5" })}
                    ${formGroup.text("email", "Email", res.email, true, { className: "col-md-5" })}
                    ${formGroup.text("no_hp", "No. HP", res.no_hp, false, { className: "col-md-3" })}
                    ${formGroup.select("is_active", "Status", res.is_active,
                        [{ value: 1, label: "Aktif" }, { value: 0, label: "Tidak Aktif" }],
                        { className: "col-md-2" }
                    )}
                    ${formGroup.select("status_kepegawaian", "Status Kepegawaian", res.status_kepegawaian,
                        STATUS_KEPEGAWAIAN_OPTIONS,
                        { className: "col-md-3" }
                    )}
                    <div class="col-md-5">
                        <label class="form-label">Grup / Departemen</label>
                        <select name="menu_group_id" class="form-select disabled" disabled>
                            ${renderGroupSelect(res.menu_group_id)}
                        </select>
                    </div>
                    ${formGroup.text("password", "Password Baru", "", false, { className: "col-md-5" })}
                    <div class="col-md-12" data-no-disable>
                        <small class="text-muted">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i>Terakhir login:
                            <b>${res.last_login_at ? String(res.last_login_at).substring(0, 16).replace('T', ' ') : 'belum pernah'}</b>${res.last_login_ip ? ` (IP ${res.last_login_ip})` : ''}
                        </small>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted">Kosongkan Password Baru jika tidak ingin mengubah password. Permission dari grup akan digabung dengan override di bawah.</small>
                    </div>
                </div>`
    )}

    <!-- SECTION 2: OVERRIDE PERMISSION -->
    ${formGroup.sectionCard(
        { icon: 'fa-shield-halved', color: 'icon-green', title: 'Override Permission', subtitle: 'Tambahan akses di luar grup — digabung (OR) dengan permission grup' },
        `${renderPermissionMatrix(res.permissions)}`
    )}

</form>`;
}

function initPermissionMatrix() {
    const container = '#detailContent';

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

    $(container).on('change', '.select-all-tabs', function () {
        const section = $(this).data('tabsection');
        $(container).find(`.perm-check[data-tabsection="${section}"]`).prop('checked', $(this).is(':checked'));
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

    function syncGlobal(container) {
        const total = $(container).find('.perm-check').length;
        const totalChecked = $(container).find('.perm-check:checked').length;
        $(container).find('.select-all-global').prop('checked', total === totalChecked);
    }

    // Search permission matrix
    $(container).on('input', '#perm-search', function () {
        const q = $(this).val().toLowerCase().trim();
        const $rows = $(container).find('.permission-matrix tbody tr');

        $rows.filter('.table-secondary').each(function () {
            const $group = $(this);
            const $items = $group.nextUntil('tr.table-secondary');

            if (!q) {
                $group.show();
                $items.show();
                return;
            }

            const anyVisible = $items.filter(function () {
                return ($(this).data('search') || '').includes(q);
            }).length > 0;

            $group.toggle(anyVisible);
            $items.each(function () {
                $(this).toggle(($(this).data('search') || '').includes(q));
            });
        });

        $('#perm-search-clear').toggleClass('d-none', !q);
    });

    $(container).on('click', '#perm-search-clear', function () {
        $('#perm-search').val('').trigger('input');
    });
}
