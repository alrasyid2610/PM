/**
 * Cek apakah user punya akses ke menu + action tertentu.
 * @param {string} slug   - menu slug (e.g. 'sales-orders')
 * @param {string} action - 'can_read' | 'can_create' | 'can_update' | 'can_delete'
 */
function can(slug, action) {
    const perms = window.userPermissions || {};
    return !!(perms[slug] && perms[slug][action]);
}

/**
 * Terapkan permission ke UI berdasarkan currentMenuSlug.
 * Menggunakan CSS class pada <body> agar berlaku otomatis ke semua konten
 * yang dirender dinamis (tab, AJAX, panel detail) tanpa perlu re-apply.
 */
function applyPagePermissions() {
    const slug = window.currentMenuSlug;
    if (!slug || !window.userPermissions) return;

    if (!can(slug, 'can_create')) {
        document.body.classList.add('perm-no-create');
    }

    if (!can(slug, 'can_update')) {
        document.body.classList.add('perm-no-update');
    }

    if (!can(slug, 'can_delete')) {
        document.body.classList.add('perm-no-delete');
    }
}

$(document).ready(function () {
    applyPagePermissions();
});
