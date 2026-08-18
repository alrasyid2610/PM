@extends('layouts.app')

@section('page-title', 'Profile Saya')
@section('page-descrip', 'Kelola data diri, password, dan foto profil Anda')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Profile</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="40" cy="30" r="12" stroke="white" stroke-width="3"/>
        <path d="M16 66c0-13.255 10.745-24 24-24s24 10.745 24 24" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <div class="row g-3">

        {{-- FOTO PROFIL --}}
        <div class="col-12">
            <x-section-card icon="fa-image" color="icon-blue" title="Foto Profil" subtitle="Foto yang tampil di navbar">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <img id="profileAvatarPreview"
                             src="{{ $user->avatar ? asset('storage/' . $user->avatar) : asset('assets/images/avatar/avatar-s-1.png') }}"
                             alt="avatar" style="width:84px;height:84px;border-radius:50%;object-fit:cover;border:1px solid #e2e8f0;">
                    </div>
                    <div class="col">
                        <input type="file" id="profileAvatarInput" data-no-disable>
                        <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2MB.</div>
                    </div>
                    <div class="col-12">
                        <button type="button" id="btnSaveAvatar" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Foto
                        </button>
                    </div>
                </div>
            </x-section-card>
        </div>

        {{-- DATA DIRI --}}
        <div class="col-12">
            <x-section-card icon="fa-id-card" color="icon-navy" title="Data Diri" subtitle="Nama dan email akun Anda">
                <form id="profileDataForm" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Nama</label>
                        <input type="text" name="name" class="form-control" value="{{ $user->name }}" required maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required maxlength="255">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Diri
                        </button>
                    </div>
                </form>
            </x-section-card>
        </div>

        {{-- GANTI PASSWORD --}}
        <div class="col-12">
            <x-section-card icon="fa-lock" color="icon-amber" title="Ganti Password" subtitle="Masukkan password saat ini untuk konfirmasi">
                <form id="profilePasswordForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Password Baru</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-key me-1"></i> Ubah Password
                        </button>
                    </div>
                </form>
            </x-section-card>
        </div>

    </div>
</section>
@endsection

@section('custom-script')
<script>
    let profileAvatarPond = null;

    $(document).ready(function () {
        profileAvatarPond = createFileUploader('#profileAvatarInput', {
            allowMultiple: false,
            acceptedFileTypes: ['image/jpeg', 'image/png', 'image/webp'],
            labelIdle: 'Drag & Drop foto atau <span class="filepond--label-action">Browse</span>',
        });
    });

    // ── Simpan Data Diri ────────────────────────────────────────────────────────
    $('#profileDataForm').on('submit', function (e) {
        e.preventDefault();
        const $btn  = $(this).find('button[type="submit"]');
        const $form = $(this);

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('profile.update') }}",
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        })
            .done(function (res) {
                Notify.success(res.message || 'Data diri berhasil diperbarui');
            })
            .fail(function (xhr) {
                const json = xhr.responseJSON;
                const msg = json?.message || (json?.errors ? Object.values(json.errors)[0][0] : 'Gagal menyimpan data diri');
                Notify.error(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Diri');
            });
    });

    // ── Ganti Password ──────────────────────────────────────────────────────────
    $('#profilePasswordForm').on('submit', function (e) {
        e.preventDefault();
        const $btn  = $(this).find('button[type="submit"]');
        const $form = $(this);

        if ($form.find('[name="password"]').val() !== $form.find('[name="password_confirmation"]').val()) {
            Notify.error('Konfirmasi password baru tidak cocok');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('profile.update-password') }}",
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        })
            .done(function (res) {
                Notify.success(res.message || 'Password berhasil diubah');
                $form[0].reset();
            })
            .fail(function (xhr) {
                const json = xhr.responseJSON;
                const msg = json?.message || (json?.errors ? Object.values(json.errors)[0][0] : 'Gagal mengubah password');
                Notify.error(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-key me-1"></i> Ubah Password');
            });
    });

    // ── Simpan Foto Profil ──────────────────────────────────────────────────────
    $('#btnSaveAvatar').on('click', function () {
        const files = profileAvatarPond ? profileAvatarPond.getFiles() : [];
        if (!files.length) {
            Notify.warning('Pilih foto terlebih dahulu');
            return;
        }

        const $btn = $(this);
        const fd   = new FormData();
        fd.append('_token', "{{ csrf_token() }}");
        fd.append('avatar', files[0].file);

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('profile.update-avatar') }}",
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        })
            .done(function (res) {
                Notify.success(res.message || 'Foto profil berhasil diperbarui');
                if (res.avatar_url) {
                    $('#profileAvatarPreview').attr('src', res.avatar_url + '?t=' + Date.now());
                    $('.avatar img').attr('src', res.avatar_url + '?t=' + Date.now());
                }
                profileAvatarPond.removeFiles();
            })
            .fail(function (xhr) {
                const json = xhr.responseJSON;
                const msg = json?.message || (json?.errors ? Object.values(json.errors)[0][0] : 'Gagal menyimpan foto profil');
                Notify.error(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Foto');
            });
    });
</script>
@endsection
