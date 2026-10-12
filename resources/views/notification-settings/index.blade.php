@extends('layouts.app')

@section('page-title', 'Pengaturan Notifikasi')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>Pengaturan Notifikasi</h1>
    </div>

    <div class="alert alert-info" style="font-size:13px;">
        <i class="fa-solid fa-circle-info me-1"></i>
        Daftar di bawah ini adalah <b>katalog event</b> notifikasi — baris baru otomatis muncul begitu
        sebuah event pertama kali dipicu dari sistem. Tiap event bisa punya lebih dari satu
        <b>Rule</b> (aturan penerima + template pesan). Placeholder di template pesan ditulis
        <code>{{'{{nama_field}}'}}</code>.
    </div>

    <div id="notifEventList"></div>
</section>

{{-- Modal tambah/edit Rule --}}
<div class="modal fade" id="ruleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="ruleForm">
                <div class="modal-header">
                    <h5 class="modal-title">Rule Notifikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="ruleId">
                    <input type="hidden" name="event_id" id="ruleEventId">

                    <div class="mb-3">
                        <label class="form-label required">Penerima</label>
                        <select name="recipient_type" id="ruleRecipientType" class="form-select" required>
                            <option value="permission">Punya akses modul (slug)</option>
                            <option value="all">Semua user aktif</option>
                            <option value="users">User tertentu</option>
                        </select>
                    </div>

                    <div class="mb-3" id="ruleSlugWrap">
                        <label class="form-label">Slug Menu</label>
                        <input type="text" name="permission_slug" id="ruleSlug" class="form-control"
                               placeholder="mis. fieldworks">
                        <small class="text-muted">Harus sama persis dengan slug menu (lihat <code>config/menus.php</code>).</small>
                    </div>

                    <div class="mb-3" id="ruleUsersWrap" style="display:none;">
                        <label class="form-label">Pilih User</label>
                        <select name="user_ids[]" id="ruleUsers" class="form-select" multiple></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Template Pesan</label>
                        <textarea name="message_template" id="ruleTemplate" class="form-control" rows="2" required
                                  placeholder="mis. SO baru {{no_so}} dibuat oleh {{nama_user}}"></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="ruleActive" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="ruleActive">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('custom-script')
<script src="{{ asset('assets/js/notification-settings/index.js') }}"></script>
@endsection
