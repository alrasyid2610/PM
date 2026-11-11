{{-- Modal Lab Sample Reg (dipindah dari work-order/form.js, 2026-10-31): detail/preview sample, bulk insert, registrasi (header), registrasi dari FWO. --}}
    <!-- MODAL: DETAIL SAMPLE -->
    <div class="modal fade" id="woSampleDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woSampleDetailModalLabel">
                        <i class="fa-solid fa-flask me-2" style="color:#0369a1;"></i>Detail Sample
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <input type="hidden" id="woSampleModal-id">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Jenis Sample</label>
                            <select class="form-select form-select-sm" id="woSampleModal-jenis" data-no-disable>
                                <option value="">-- Pilih --</option>
                                <option value="env">ENV — Lingkungan Hidup</option>
                                <option value="we">WE — Lingkungan Kerja</option>
                                <option value="mp">MP — Makanan & Produk</option>
                                <option value="product">Product — Produk Industri</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">No. Sample</label>
                            <input type="text" class="form-control form-control-sm" id="woSampleModal-no" placeholder="Kode/nomor sample" readonly style="background:#f8fafc;color:#64748b;" data-no-disable>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">No. Reg Lab</label>
                            <input type="text" class="form-control form-control-sm" id="woSampleModal-no-reg-lab" placeholder="Belum diregistrasi" readonly style="background:#f8fafc;color:#64748b;" data-no-disable>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Pengambilan</label>
                            <input type="text" class="form-control form-control-sm fp-date" id="woSampleModal-tanggal" placeholder="Pilih tanggal" autocomplete="off" data-no-disable>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Kondisi Sample</label>
                            <select class="form-select form-select-sm" id="woSampleModal-kondisi" data-no-disable>
                                <option value="">-- Pilih --</option>
                                <option value="baik">Baik</option>
                                <option value="rusak">Rusak</option>
                                <option value="tidak_lengkap">Tidak Lengkap</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Titik / Lokasi</label>
                            <select class="form-select form-select-sm" id="woSampleModal-titik" data-no-disable></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
                            <select class="form-select form-select-sm" id="woSampleModal-status" data-no-disable>
                                <option value="belum_diambil">Belum Diambil</option>
                                <option value="diambil">Diambil</option>
                                <option value="dikirim">Dikirim ke Lab</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Keterangan</label>
                            <textarea class="form-control form-control-sm" id="woSampleModal-keterangan" rows="2" placeholder="Opsional" data-no-disable></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Lampiran</label>
                            <div id="woSampleModal-existing-files" class="mb-2"></div>
                            <input type="file" id="woSampleModal-attachments" multiple data-no-disable>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woSampleModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: BULK FILL SAMPLE -->
    <div class="modal fade" id="woSampleBulkFillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woSampleBulkFillModalLabel">
                        <i class="fa-solid fa-wand-magic-sparkles me-2" style="color:#7c3aed;"></i>Bulk Insert
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <input type="hidden" id="woBulkFillModal-id-boq">
                    <p class="text-muted mb-3" style="font-size:12px;">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Field yang dikosongkan <b>tidak akan diubah</b>. Hanya field yang diisi yang akan di-apply ke semua sample.
                    </p>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Jenis Sample</label>
                            <select class="form-select form-select-sm" id="woBulkFill-jenis" data-no-disable>
                                <option value="">— Tidak diubah —</option>
                                <option value="env">ENV — Lingkungan Hidup</option>
                                <option value="we">WE — Lingkungan Kerja</option>
                                <option value="mp">MP — Makanan & Produk</option>
                                <option value="product">Product — Produk Industri</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Pengambilan</label>
                            <input type="text" class="form-control form-control-sm fp-date" id="woBulkFill-tanggal" placeholder="Pilih tanggal" autocomplete="off" data-no-disable>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
                            <select class="form-select form-select-sm" id="woBulkFill-status" data-no-disable>
                                <option value="">— Tidak diubah —</option>
                                <option value="belum_diambil">Belum Diambil</option>
                                <option value="diambil">Diambil</option>
                                <option value="dikirim">Dikirim ke Lab</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Kondisi Sample</label>
                            <select class="form-select form-select-sm" id="woBulkFill-kondisi" data-no-disable>
                                <option value="">— Tidak diubah —</option>
                                <option value="baik">Baik</option>
                                <option value="rusak">Rusak</option>
                                <option value="tidak_lengkap">Tidak Lengkap</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woSampleBulkFillModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Apply ke Semua Sample
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: HEADER REGISTRASI LAB (dipakai untuk Buat Otomatis / Tambah 1 Sample) -->
    <div class="modal fade" id="woRegHeaderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woRegHeaderModalLabel">
                        <i class="fa-solid fa-clipboard-check me-2" style="color:#0369a1;"></i>Registrasi Sample Diterima
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Registrasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="woRegHeaderModal-tanggal" readonly autocomplete="off" data-no-disable
                                title="Tanggal registrasi otomatis hari ini (tidak bisa diubah)">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Diterima Oleh</label>
                            <select class="form-select form-select-sm" id="woRegHeaderModal-personnel" data-no-disable></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Keterangan</label>
                            <textarea class="form-control form-control-sm" id="woRegHeaderModal-keterangan" rows="2" placeholder="Opsional" data-no-disable></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woRegHeaderModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Handle geser lebar kolom tabel modal Registrasi Sample (pola sama dengan .col-resize-handle di Clone SO) */
        .wo-reg-col-handle { position: absolute; top: 0; right: 0; width: 6px; height: 100%; cursor: col-resize; user-select: none; z-index: 2; }
        .wo-reg-col-handle:hover, .wo-reg-col-handle.resizing { background: rgba(29, 78, 216, 0.35); }
    </style>

    <!-- MODAL: TARIK SAMPLE DARI FWO -->
    <div class="modal fade" id="woRegFromFwoModal" tabindex="-1" aria-hidden="true">
        {{-- Lebar ±95% layar (maks 1600px) supaya kolom Parameter terbaca --}}
        <div class="modal-dialog modal-dialog-scrollable modal-xl" style="--bs-modal-width:min(95vw,1600px);max-width:min(95vw,1600px);">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0">
                        <i class="fa-solid fa-file-import me-2" style="color:#0369a1;"></i>Registrasi Sample dari FWO
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Registrasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="woRegFromFwoModal-tanggal" readonly autocomplete="off" data-no-disable
                                title="Tanggal registrasi otomatis hari ini (tidak bisa diubah)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Diterima Oleh</label>
                            <select class="form-select form-select-sm" id="woRegFromFwoModal-personnel" data-no-disable></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Keterangan</label>
                            <textarea class="form-control form-control-sm" id="woRegFromFwoModal-keterangan" rows="2" placeholder="Opsional" data-no-disable></textarea>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" class="form-control" id="woRegFromFwoModal-search" placeholder="Cari no. sample, jenis, titik, no. FWO..." data-no-disable>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <div class="form-check d-inline-block mb-0">
                                <input class="form-check-input" type="checkbox" id="woRegFromFwoModal-check-all" data-no-disable>
                                <label class="form-check-label" for="woRegFromFwoModal-check-all" style="font-size:12px;">Pilih Semua (Tampil)</label>
                            </div>
                            <div class="d-block text-muted mt-1" id="woRegFromFwoModal-count" style="font-size:12px;"></div>
                        </div>
                    </div>
                    <div id="woRegFromFwoModal-list">
                        <div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data sample dari FWO...</div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woRegFromFwoModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-floppy-disk me-1"></i> Registrasi Sample Terpilih<span id="woRegFromFwoModal-btn-count"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
