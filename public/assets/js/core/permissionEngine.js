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
 * Akses tab detail (grup "Akses Tab"), mis. canTab('wo-tab-boq'). Versi PHP: userCanTab().
 * Tidak punya satu pun tab dari halaman yang sama → semua tab boleh (kompatibel mundur);
 * punya ≥ 1 → hanya tab yang dicentang. Tersimpan di can_update.
 */
function canTab(slug) {
    const perms = window.userPermissions || {};
    const pos = slug.indexOf('-tab-');
    if (pos < 0) return true;
    const prefix = slug.slice(0, pos + 5);
    const adaPembatas = Object.keys(perms).some((k) => k.startsWith(prefix) && perms[k] && perms[k].can_update);
    return adaPembatas ? !!(perms[slug] && perms[slug].can_update) : true;
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
