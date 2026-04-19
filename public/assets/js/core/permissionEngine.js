/**
 * Cek apakah user punya akses ke menu + action tertentu.
 * @param {string} slug  - menu slug (e.g. 'sales-orders')
 * @param {string} action - 'can_read' | 'can_create' | 'can_update' | 'can_delete'
 */
function can(slug, action) {
    const perms = window.userPermissions || {};
    return !!(perms[slug] && perms[slug][action]);
}

/**
 * Terapkan permission ke UI berdasarkan currentMenuSlug.
 * Dipanggil saat DOM ready dan setiap kali DataTable selesai draw.
 */
function applyPagePermissions() {
    const slug = window.currentMenuSlug;
    if (!slug || !window.userPermissions) return;

    if (!can(slug, 'can_create')) {
        $('a[href$="/create"]').hide();
        $('.btn-add-data, [data-perm="can_create"]').hide();
    }

    if (!can(slug, 'can_update')) {
        $('.btn-edit, .btn-edit-context, [data-perm="can_update"]').hide();
    }

    if (!can(slug, 'can_delete')) {
        $('.btn-delete, [data-perm="can_delete"]').hide();
    }
}

$(document).ready(function () {
    applyPagePermissions();

    // Re-apply setiap kali DataTable re-draw (action buttons dirender ulang)
    $(document).on('draw.dt', function () {
        applyPagePermissions();
    });
});
