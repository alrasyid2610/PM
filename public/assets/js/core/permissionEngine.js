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
 * Terapkan Akses Tab ke halaman detail (WO, SO, FWO). Tab yang tidak diizinkan: tombolnya dihapus
 * dan panelnya dihapus — kecuali panel Informasi (memuat form record) yang cuma disembunyikan dan
 * tombol Edit dimatikan supaya form tidak ikut hilang. Tab pertama yang diizinkan otomatis dibuka;
 * tidak ada tab sama sekali → pesan akses. Penjagaan data sebenarnya di server (TAB_ROUTES).
 *
 * @param {object} cfg
 *   nav         selector <ul> tab (mis. '#woDetailTabs')
 *   map         { '#tabPane': 'slug-tab' }
 *   infoTarget  target panel Informasi (form record), opsional
 *   infoActions selector wadah aksi tab Informasi, opsional
 *   label       nama record untuk pesan (mis. 'WO')
 */
function applyTabAccess(cfg) {
    const $nav = $(cfg.nav);
    if (!$nav.length) return;

    Object.keys(cfg.map).forEach(function (target) {
        if (canTab(cfg.map[target])) return;
        $nav.find('button[data-bs-target="' + target + '"]').closest('li').remove();
        if (target === cfg.infoTarget) {
            $(target).removeClass('active show').addClass('d-none');
            if (cfg.infoActions) $(cfg.infoActions).addClass('d-none');
            $('.btn-edit-context').addClass('d-none'); // form di panel Informasi tidak tampil → edit dimatikan
        } else {
            $(target).remove();
        }
    });

    const $buttons = $nav.find('button[data-bs-toggle="tab"]');
    if (!$buttons.length) {
        $nav.closest('.pm-tab-card').find('.tab-content').first().removeClass('d-none').html(
            '<div class="text-center text-muted py-5"><i class="fa-solid fa-lock fa-2x d-block mb-2 opacity-50"></i>' +
            'Anda tidak punya akses ke tab mana pun pada ' + (cfg.label || 'halaman') + ' ini.</div>'
        );
        $('.btn-edit-context').addClass('d-none');
        return;
    }
    if (!$buttons.filter('.active').length) {
        // Informasi disembunyikan / tidak diizinkan → buka tab pertama yang diizinkan
        $nav.closest('.pm-tab-card').find('.tab-pane.active, .tab-pane.show').removeClass('active show');
        bootstrap.Tab.getOrCreateInstance($buttons.get(0)).show();
    }
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
