@extends('layouts.app')

@section('page-title', 'Pengaturan Notifikasi')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>Pengaturan Notifikasi</h1>
    </div>

    <div class="alert alert-info" style="font-size:13px;">
        <i class="fa-solid fa-circle-info me-1"></i>
        Pilih event yang ingin diaktifkan lewat <b>Tambah Event</b> (daftar master kejadian di sistem), lalu atur
        <b>Rule</b>-nya — aturan penerima + template pesan. Satu event bisa punya lebih dari satu rule.
        Penerima bisa berupa akses modul, user tertentu, semua user, atau <b>peran dari data</b> (mis. PIC Order, Penyelia FWO).
        Placeholder di template pesan ditulis <code>@{{nama_field}}</code>.
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-primary btn-sm" id="btnEventAdd">
            <i class="fa-solid fa-plus me-1"></i> Tambah Event
        </button>
    </div>

    <div id="notifEventList"></div>
</section>

{{-- Modal tambah Event dari master --}}
<div class="modal fade" id="eventAddModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Event Notifikasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label required">Event</label>
                <select id="eventAddSelect" class="form-select" style="width:100%;"></select>
                <small class="text-muted d-block mt-2" id="eventAddDesc"></small>
                <div class="text-muted mt-2" id="eventAddEmpty" style="display:none;">Semua event di master sudah ditambahkan.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnEventAddSave">Tambah</button>
            </div>
        </div>
    </div>
</div>

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
                            <option value="roles">Peran dari data (mis. PIC, Penyelia)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="ruleRolesWrap" style="display:none;">
                        <label class="form-label">Peran Penerima</label>
                        <div id="ruleRolesList"></div>
                        <small class="text-muted">Penerima diambil dari data record yang memicu event (hanya user aktif).</small>
                    </div>

                    <div class="mb-3" id="ruleSlugWrap">
                        <label class="form-label">Modul (Slug Menu)</label>
                        <select name="permission_slug" id="ruleSlug" class="form-select">
                            <option value="">— Pilih Modul —</option>
                            @foreach ($menuSlugs->groupBy('group') as $groupName => $items)
                            <optgroup label="{{ $groupName }}">
                                @foreach ($items as $item)
                                <option value="{{ $item['slug'] }}">{{ $item['label'] }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <small class="text-muted">Penerima = user yang punya akses baca ke modul ini.</small>
                    </div>

                    <div class="mb-3" id="ruleUsersWrap" style="display:none;">
                        <label class="form-label">Pilih User</label>
                        <select name="user_ids[]" id="ruleUsers" class="form-select" multiple></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Template Pesan</label>
                        <textarea name="message_template" id="ruleTemplate" class="form-control" rows="2" required
                                  placeholder="mis. SO baru @{{no_so}} dibuat oleh @{{nama_user}}"></textarea>
                        <div id="ruleFieldChips" class="mt-1" style="font-size:11px;"></div>
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
