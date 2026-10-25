// Lonceng notifikasi di navbar — Fase 1: data dimuat saat halaman dibuka dan
// saat dropdown diklik, BELUM ada polling otomatis (lihat Modules/... di
// Obsidian untuk rencana Fase 2/3). Hanya aktif kalau elemen #notifBell ada
// (dirender cuma untuk user yang sudah login, lihat layouts/navbar.blade.php).

function notifTimeAgo(dateStr) {
    const diff = Math.max(0, (Date.now() - new Date(dateStr.replace(' ', 'T'))) / 1000);
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
    if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' hari lalu';
    return new Date(dateStr.replace(' ', 'T')).toLocaleDateString('id-ID');
}

function notifRenderBadge(count) {
    const $b = $('#notifBadge');
    if (count > 0) {
        $b.text(count > 99 ? '99+' : count).show();
    } else {
        $b.hide();
    }
}

function notifRenderList(items) {
    const $list = $('#notifList');
    if (!items.length) {
        $list.html('<li class="list-group-item"><small class="text-muted">Belum ada notifikasi</small></li>');
        return;
    }

    $list.html(items.map(function (n) {
        const unread = !n.read_at;
        return `
        <li class="list-group-item notif-item ${unread ? 'notif-unread' : ''}"
            data-id="${n.id}" data-url="${n.url ?? ''}"
            style="cursor:pointer;${unread ? 'background:#eff6ff;' : ''}">
            <div class="d-flex justify-content-between align-items-start" style="gap:8px;">
                <div style="min-width:0;">
                    <div style="font-size:12.5px;font-weight:${unread ? '600' : '400'};color:#1e293b;">${escHtml(n.title)}</div>
                    ${n.body ? `<div style="font-size:11.5px;color:#64748b;margin-top:2px;">${escHtml(n.body)}</div>` : ''}
                    <div style="font-size:10.5px;color:#94a3b8;margin-top:3px;">${notifTimeAgo(n.created_at)}</div>
                </div>
                <button type="button" class="btn btn-sm notif-delete" title="Hapus"
                        style="padding:2px 6px;color:#94a3b8;">
                    <i class="fa-solid fa-xmark" style="font-size:11px;"></i>
                </button>
            </div>
        </li>`;
    }).join(''));
}

function notifLoad() {
    if (!$('#notifBell').length) return;
    $.get('/notifications', function (res) {
        notifRenderBadge(res.unread);
        notifRenderList(res.items);
    });
}

$(function () {
    if (!$('#notifBell').length) return;

    notifLoad();

    // Reload tiap dropdown dibuka, supaya tidak basi kalau user diam lama di 1 halaman.
    $('#notifBellToggle').on('shown.bs.dropdown', notifLoad);
    // Bootstrap mentrigger 'shown.bs.dropdown' di parent li, bukan toggle-nya sendiri
    $('#notifBell').on('shown.bs.dropdown', notifLoad);

    $(document).on('click', '.notif-item', function (e) {
        if ($(e.target).closest('.notif-delete').length) return;
        const id  = $(this).data('id');
        const url = $(this).data('url');
        $.post(`/notifications/${id}/read`, { _token: window.csrfToken }, function () {
            notifLoad();
            if (url) window.location.href = url;
        });
    });

    $(document).on('click', '.notif-delete', function (e) {
        e.stopPropagation();
        const id = $(this).closest('.notif-item').data('id');
        $.ajax({
            url: `/notifications/${id}`,
            method: 'DELETE',
            data: { _token: window.csrfToken },
            success: notifLoad,
        });
    });

    $('#notifMarkAllRead').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $.post('/notifications/mark-all-read', { _token: window.csrfToken }, notifLoad);
    });
});
