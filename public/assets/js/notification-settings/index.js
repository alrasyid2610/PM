let notifEvents = [];

function loadNotifEvents() {
    $.get('/notification-settings/data', function (res) {
        notifEvents = res;
        renderNotifEvents();
    });
}

function recipientLabel(rule) {
    if (rule.recipient_type === 'all') return 'Semua user aktif';
    if (rule.recipient_type === 'permission') return `Akses modul: <code>${escHtml(rule.permission_slug || '-')}</code>`;
    return 'User tertentu';
}

function renderNotifEvents() {
    const $wrap = $('#notifEventList');
    if (!notifEvents.length) {
        $wrap.html('<div class="text-muted">Belum ada event notifikasi yang pernah terpicu.</div>');
        return;
    }

    $wrap.html(notifEvents.map(function (ev) {
        const rulesHtml = (ev.rules || []).map(function (r) {
            return `
            <tr>
                <td>${recipientLabel(r)}</td>
                <td style="font-size:12px;">${escHtml(r.message_template)}</td>
                <td class="text-center">
                    ${r.is_active
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Nonaktif</span>'}
                </td>
                <td class="text-end" style="white-space:nowrap;">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-rule-edit"
                            data-rule='${JSON.stringify(r)}'><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-rule-delete"
                            data-id="${r.id}"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        }).join('') || `<tr><td colspan="4" class="text-muted text-center">Belum ada rule — event ini tidak mengirim notifikasi ke siapa pun.</td></tr>`;

        return `
        <div class="card mb-3" data-event-id="${ev.id}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:10px;">
                    <div style="flex:1;min-width:240px;">
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <input type="text" class="form-control form-control-sm evt-label" value="${escHtml(ev.label)}" style="max-width:260px;font-weight:600;">
                            <code style="font-size:11px;color:#94a3b8;">${escHtml(ev.key)}</code>
                        </div>
                        <textarea class="form-control form-control-sm evt-desc mt-2" rows="1"
                                  placeholder="Deskripsi (opsional)">${escHtml(ev.description || '')}</textarea>
                    </div>
                    <div class="d-flex align-items-center" style="gap:10px;">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input evt-active" ${ev.is_active ? 'checked' : ''}>
                            <label class="form-check-label" style="font-size:12px;">Aktif</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary btn-evt-save">Simpan</button>
                    </div>
                </div>

                <table class="table table-sm mt-3 mb-0">
                    <thead>
                        <tr style="font-size:11px;color:#64748b;">
                            <th>Penerima</th>
                            <th>Template Pesan</th>
                            <th class="text-center" style="width:90px;">Status</th>
                            <th style="width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>${rulesHtml}</tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-primary btn-rule-add mt-2">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Rule
                </button>
            </div>
        </div>`;
    }).join(''));
}

$(function () {
    if (!$('#notifEventList').length) return;

    loadNotifEvents();

    $('#ruleUsers').select2({
        width: '100%',
        placeholder: 'Pilih user',
        ajax: {
            url: '/users/select2',
            dataType: 'json',
            delay: 200,
            data: (p) => ({ q: p.term || '' }),
            processResults: (d) => ({ results: d }),
            cache: true,
        },
        dropdownParent: $('#ruleModal'),
    });

    $('#ruleRecipientType').on('change', function () {
        const v = $(this).val();
        $('#ruleSlugWrap').toggle(v === 'permission');
        $('#ruleUsersWrap').toggle(v === 'users');
    });

    $(document).on('click', '.btn-evt-save', function () {
        const $card = $(this).closest('.card');
        const id = $card.data('event-id');
        $.ajax({
            url: `/notification-settings/events/${id}`,
            method: 'PUT',
            data: {
                _token: window.csrfToken,
                label: $card.find('.evt-label').val(),
                description: $card.find('.evt-desc').val(),
                is_active: $card.find('.evt-active').is(':checked') ? 1 : 0,
            },
            success: function () {
                Notify.success('Event diperbarui');
                loadNotifEvents();
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan event');
            },
        });
    });

    // Chip field yang tersedia untuk event ybs — diklik untuk disisipkan ke
    // Template Pesan (bukan diketik manual), diambil dari notifEvents yang
    // sudah dimuat (available_fields dicatat otomatis tiap event dipicu).
    function renderRuleFieldChips(eventId) {
        const ev = notifEvents.find((e) => e.id === eventId);
        const fields = (ev && ev.available_fields) || [];
        if (!fields.length) {
            $('#ruleFieldChips').html('<span class="text-muted">Belum ada data field tercatat untuk event ini — event perlu terpicu minimal 1x dulu.</span>');
            return;
        }
        $('#ruleFieldChips').html(
            '<span class="text-muted me-1">Field tersedia:</span> ' +
            fields.map((f) => `<button type="button" class="btn btn-sm btn-outline-secondary chip-field py-0 px-2 me-1 mb-1" data-field="${f}" style="font-size:11px;">@{{${f}}}</button>`).join('')
        );
    }

    $(document).on('click', '.chip-field', function () {
        const $ta = $('#ruleTemplate');
        const el = $ta.get(0);
        const text = `{{${$(this).data('field')}}}`;
        const start = el.selectionStart ?? el.value.length;
        const end = el.selectionEnd ?? el.value.length;
        el.value = el.value.slice(0, start) + text + el.value.slice(end);
        el.focus();
        el.selectionStart = el.selectionEnd = start + text.length;
    });

    $(document).on('click', '.btn-rule-add', function () {
        const eventId = $(this).closest('.card').data('event-id');
        $('#ruleForm')[0].reset();
        $('#ruleId').val('');
        $('#ruleEventId').val(eventId);
        $('#ruleUsers').val(null).trigger('change');
        $('#ruleRecipientType').val('permission').trigger('change');
        renderRuleFieldChips(eventId);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ruleModal')).show();
    });

    $(document).on('click', '.btn-rule-edit', function () {
        const r = $(this).data('rule');
        $('#ruleId').val(r.id);
        $('#ruleEventId').val(r.notification_event_id);
        $('#ruleRecipientType').val(r.recipient_type).trigger('change');
        $('#ruleSlug').val(r.permission_slug || '');
        $('#ruleTemplate').val(r.message_template);
        $('#ruleActive').prop('checked', !!r.is_active);
        renderRuleFieldChips(r.notification_event_id);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ruleModal')).show();
    });

    $(document).on('click', '.btn-rule-delete', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus rule ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc2626',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: `/notification-settings/rules/${id}`,
                method: 'DELETE',
                data: { _token: window.csrfToken },
                success: function () {
                    Notify.success('Rule dihapus');
                    loadNotifEvents();
                },
            });
        });
    });

    $('#ruleForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#ruleId').val();
        const eventId = $('#ruleEventId').val();
        const payload = {
            _token: window.csrfToken,
            recipient_type: $('#ruleRecipientType').val(),
            permission_slug: $('#ruleSlug').val(),
            message_template: $('#ruleTemplate').val(),
            is_active: $('#ruleActive').is(':checked') ? 1 : 0,
            user_ids: $('#ruleUsers').val() || [],
        };

        $.ajax({
            url: id ? `/notification-settings/rules/${id}` : `/notification-settings/events/${eventId}/rules`,
            method: id ? 'PUT' : 'POST',
            data: payload,
            success: function () {
                Notify.success('Rule tersimpan');
                bootstrap.Modal.getInstance(document.getElementById('ruleModal')).hide();
                loadNotifEvents();
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan rule');
            },
        });
    });
});
