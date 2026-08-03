@extends('layouts.app')

@section('page-title', 'UI Guideline')

@section('content')
<style>
/* ── Layout ─────────────────────────────────────────────────────────── */
.ug-wrap { display: flex; gap: 0; min-height: 80vh; }

.ug-sidebar {
    width: 200px;
    flex-shrink: 0;
    position: sticky;
    top: 70px;
    height: calc(100vh - 80px);
    overflow-y: auto;
    padding: 12px 0;
    border-right: 1px solid #e5e7eb;
    background: #fff;
    border-radius: 10px 0 0 10px;
}
.ug-sidebar a {
    display: block;
    padding: 5px 16px;
    font-size: 12px;
    color: #6b7280;
    text-decoration: none;
    transition: all .15s;
    border-left: 2px solid transparent;
}
.ug-sidebar a:hover { color: #111827; background: #f9fafb; }
.ug-sidebar a.ug-active { color: var(--primary-700); font-weight: 600; border-left-color: var(--primary-500); background: var(--primary-50); }
.ug-sidebar-section {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .07em; color: #9ca3af; padding: 10px 16px 3px;
}

.ug-main {
    flex: 1; padding: 20px 32px;
    background: #fff; border-radius: 0 10px 10px 0;
    min-width: 0;
}

/* ── Section ─────────────────────────────────────────────────────────── */
.ug-section { margin-bottom: 52px; scroll-margin-top: 90px; }
.ug-section-title {
    font-size: 17px; font-weight: 700; color: #111827;
    letter-spacing: -.01em; margin-bottom: 2px;
}
.ug-section-sub { font-size: 12px; color: #9ca3af; margin-bottom: 14px; }
.ug-divider { height: 1px; background: #f3f4f6; margin-bottom: 14px; }

.ug-card {
    border: 1px solid #e5e7eb; border-radius: 10px;
    padding: 18px; margin-bottom: 12px; overflow: hidden;
}
.ug-card-label {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .06em; color: #9ca3af; margin-bottom: 14px;
}

/* ── Code Block ──────────────────────────────────────────────────────── */
.ug-code {
    background: #0f172a; border-radius: 8px;
    padding: 14px 18px; margin-top: 14px;
    font-family: 'Consolas','Courier New',monospace;
    font-size: 12px; line-height: 1.75; overflow-x: auto;
    color: #94a3b8;
}
.ug-code .kw  { color: #7dd3fc; }
.ug-code .str { color: #86efac; }
.ug-code .fn  { color: #fbbf24; }
.ug-code .cn  { color: #f472b6; }
.ug-code .cm  { color: #475569; font-style: italic; }

/* ── Spec Table ──────────────────────────────────────────────────────── */
.ug-spec { width: 100%; border-collapse: collapse; font-size: 12px; }
.ug-spec th, .ug-spec td {
    text-align: left; padding: 7px 12px;
    border-bottom: 1px solid #f3f4f6; vertical-align: top;
}
.ug-spec th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; font-size: 11px; }
.ug-spec code { background: #f1f5f9; color: #0f172a; padding: 1px 5px; border-radius: 4px; font-size: 11px; }

/* ── Rule List ───────────────────────────────────────────────────────── */
.ug-rules { list-style: none; display: flex; flex-direction: column; gap: 7px; }
.ug-rules li { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; }
.ug-rules li::before { content: '✓'; color: #10b981; font-weight: 700; flex-shrink: 0; margin-top: 1px; }
.ug-rules li.no::before { content: '✕'; color: #ef4444; }

/* ── Swatch ──────────────────────────────────────────────────────────── */
.ug-swatch-row { display: flex; flex-wrap: wrap; gap: 10px; }
.ug-swatch { display: flex; flex-direction: column; align-items: center; gap: 5px; }
.ug-swatch-box { width: 44px; height: 44px; border-radius: 8px; border: 1px solid rgba(0,0,0,.07); }
.ug-swatch-label { font-size: 10px; color: #9ca3af; text-align: center; line-height: 1.3; }

/* ── Demo spacing ────────────────────────────────────────────────────── */
.ug-demo-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 14px; }
.ug-badge-row { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 10px; }
.ug-form-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 14px; }
.ug-form-col { flex: 1; min-width: 160px; }

/* ── Modal preview ───────────────────────────────────────────────────── */
.ug-modal-preview {
    border: 1px solid #e5e7eb; border-radius: 10px;
    overflow: hidden; max-width: 460px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
}
.ug-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 18px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;
}
.ug-modal-title { font-size: 14px; font-weight: 600; color: #111827; }
.ug-modal-body { padding: 18px; }
.ug-modal-footer {
    padding: 10px 18px; border-top: 1px solid #e5e7eb;
    display: flex; justify-content: flex-end; gap: 8px; background: #f9fafb;
}

/* ── Action bar preview ──────────────────────────────────────────────── */
.ug-actionbar-preview {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 16px; border: 1px solid #eaecf0; border-radius: 10px;
    background: #fff;
}
.ug-detail-number { font-size: 18px; font-weight: 700; color: var(--primary-700); letter-spacing: -.02em; }
.ug-detail-date { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.ug-detail-tags { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 6px; }

/* ── Section card preview ────────────────────────────────────────────── */
.ug-sc-preview {
    border: 0.5px solid #c3c3c3; border-radius: 12px;
    overflow: hidden; background: #fff;
}
.ug-sc-header {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; background: #fafafa; border-bottom: 0.5px solid #f3f4f6;
}
.ug-sc-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 13px;
}
.ug-sc-body { padding: 14px 16px; }

/* ── KPI preview ─────────────────────────────────────────────────────── */
.ug-kpi-row { display: flex; gap: 8px; flex-wrap: wrap; }
.ug-kpi-card {
    flex: 1; min-width: 110px;
    background: #fff; border: 1px solid var(--primary-200);
    border-radius: 10px; padding: 10px 14px;
    display: flex; align-items: center; gap: 10px;
}
.ug-kpi-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: #fff; font-size: 12px;
}
.ug-kpi-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; }
.ug-kpi-value { font-size: 15px; font-weight: 700; color: var(--primary-700); line-height: 1.2; }
.ug-kpi-sub { font-size: 11px; color: #64748b; }

/* ── Nav tab preview ─────────────────────────────────────────────────── */
.ug-nav-demo { display: flex; gap: 6px; margin-bottom: 14px; }
.ug-nav-btn {
    border: 0.5px solid #d1d5db; border-radius: 6px;
    padding: 7px 16px; font-size: 13px; font-weight: 500;
    color: #6b7280; background: #fff; cursor: pointer; transition: all .15s;
}
.ug-nav-btn:hover { background: #f3f4f6; }
.ug-nav-btn.active {
    background: var(--primary-700); border-color: var(--primary-700); color: #fff;
    box-shadow: 0 2px 8px rgba(var(--primary-700-rgb),.25);
}

/* ── pm-search preview ───────────────────────────────────────────────── */
/* (reuse class yang ada di main.css) */

/* ── pm-table preview ────────────────────────────────────────────────── */
/* (reuse class yang ada di main.css) */

/* ── Select2 demo fix ────────────────────────────────────────────────── */
.ug-select2-wrap .select2-container { width: 100% !important; }
</style>

<div class="ug-wrap">

    {{-- Sidebar nav --}}
    <nav class="ug-sidebar" id="ugSidebar">
        <div class="ug-sidebar-section">Fondasi</div>
        <a href="#ug-colors">Warna & Token</a>
        <a href="#ug-typography">Tipografi</a>
        <div class="ug-sidebar-section">Komponen</div>
        <a href="#ug-buttons">Tombol</a>
        <a href="#ug-badges">Badge & Status</a>
        <a href="#ug-forms">Form Elements</a>
        <a href="#ug-select2">Select2</a>
        <a href="#ug-tables">Tabel</a>
        <a href="#ug-search">Search Box</a>
        <a href="#ug-nav-tabs">Nav Tabs</a>
        <div class="ug-sidebar-section">Layout</div>
        <a href="#ug-page-header">Page Header</a>
        <a href="#ug-action-bar">Action Bar</a>
        <a href="#ug-section-card">Section Card</a>
        <a href="#ug-kpi">KPI Cards</a>
        <a href="#ug-modal">Modal</a>
    </nav>

    {{-- Main content --}}
    <div class="ug-main">

        {{-- ─── COLORS ─────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-colors">
            <div class="ug-section-title">Warna &amp; Token</div>
            <div class="ug-section-sub">Semua warna pakai CSS variable dari <code>:root</code>. Jangan hardcode hex — selalu pakai token.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-card-label">Primary — Brand Blue</div>
                <div class="ug-swatch-row">
                    @foreach([['950','#0a1628'],['900','#0d2146'],['850','#0f2d5a'],['800','#1a3a6e'],['700 (core)','#18386b'],['500 (accent)','#1a5fbe'],['400 (highlight)','#4a9eff'],['200','#bcd0f8'],['100','#e8f0fe'],['50','#eef2f9']] as [$name,$hex])
                    <div class="ug-swatch">
                        <div class="ug-swatch-box" style="background:{{$hex}};"></div>
                        <div class="ug-swatch-label">{{$name}}<br><code style="font-size:9px;">{{$hex}}</code></div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="ug-card">
                <div class="ug-card-label">Semantik (Status)</div>
                <div class="ug-swatch-row">
                    @foreach([['Success','#10b981'],['Warning','#f59e0b'],['Danger','#ef4444'],['Purple','#8b5cf6'],['Teal','#0d9488'],['Gray','#6b7280']] as [$name,$hex])
                    <div class="ug-swatch">
                        <div class="ug-swatch-box" style="background:{{$hex}};"></div>
                        <div class="ug-swatch-label">{{$name}}<br><code style="font-size:9px;">{{$hex}}</code></div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="ug-card">
                <div class="ug-card-label">Contoh Penggunaan Token</div>
                <div class="ug-code">
<span class="cm">/* ✓ Benar — pakai token */</span>
<span class="cn">color</span>: <span class="fn">var</span>(<span class="str">--primary-700</span>);
<span class="cn">background</span>: <span class="fn">var</span>(<span class="str">--primary-100</span>);

<span class="cm">/* ✕ Salah — hardcode hex */</span>
<span class="cn">color</span>: <span class="str">#18386b</span>;
                </div>
            </div>
        </section>

        {{-- ─── TYPOGRAPHY ──────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-typography">
            <div class="ug-section-title">Tipografi</div>
            <div class="ug-section-sub">Font stack mengikuti OS system font. Tidak ada webfont eksternal yang di-load.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <table class="ug-spec">
                    <thead><tr><th>Elemen</th><th>Size</th><th>Weight</th><th>Color</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        <tr><td><code>.page-header-title</code></td><td>15px</td><td>600</td><td>#111827</td><td>Judul di page-header-bar</td></tr>
                        <tr><td><code>.detail-number</code></td><td>18px</td><td>700</td><td>--primary-700</td><td>No. SO/WO/FWO/BRS</td></tr>
                        <tr><td><code>.detail-section-title</code></td><td>13px</td><td>600</td><td>#111827</td><td>Header section card</td></tr>
                        <tr><td><code>.form-label</code> (di detail)</td><td>12px</td><td>500</td><td>#6b7280</td><td>Label field form</td></tr>
                        <tr><td>Input value</td><td>13px</td><td>400</td><td>#374151</td><td>Nilai field form</td></tr>
                        <tr><td><code>.pm-table th</code></td><td>11px</td><td>600</td><td>#64748b</td><td>UPPERCASE, letter-spacing</td></tr>
                        <tr><td><code>.pm-table td</code></td><td>13px</td><td>400</td><td>#374151</td><td>—</td></tr>
                        <tr><td><code>.pm-badge</code></td><td>11px</td><td>600</td><td>per variant</td><td>—</td></tr>
                        <tr><td><code>.detail-date</code></td><td>12px</td><td>400</td><td>#9ca3af</td><td>Tanggal / muted</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ─── BUTTONS ─────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-buttons">
            <div class="ug-section-title">Tombol (Button)</div>
            <div class="ug-section-sub">Setiap tombol punya kelas khusus. Jangan mix dengan Bootstrap <code>.btn .btn-primary</code> untuk aksi di action bar atau baris tabel.</div>
            <div class="ug-divider"></div>

            <div class="ug-card">
                <div class="ug-card-label">Action Bar Buttons</div>
                <div class="ug-demo-row">
                    <button class="btn-action-secondary"><i class="fa-solid fa-rotate-right"></i></button>
                    <button class="btn-action-edit editing"><i class="fa-solid fa-xmark"></i> Batal</button>
                    <button class="btn-action-save"><i class="fa-solid fa-check"></i> Simpan</button>
                    <button class="btn-action-danger"><i class="fa-solid fa-trash"></i> Hapus</button>
                    <button class="btn-action-edit"><i class="fa-solid fa-pen"></i> Edit</button>
                    <button class="btn-action-more dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                </div>
                <table class="ug-spec">
                    <thead><tr><th>Kelas</th><th>Icon FA</th><th>Tampilan</th><th>Fungsi</th></tr></thead>
                    <tbody>
                        <tr><td><code>.btn-action-secondary</code></td><td><code>fa-rotate-right</code></td><td>Abu-abu, bisa hanya icon</td><td>Refresh / aksi sekunder</td></tr>
                        <tr><td><code>.btn-action-edit.editing</code></td><td><code>fa-xmark</code></td><td>Abu-abu, teks Batal</td><td>Keluar mode edit</td></tr>
                        <tr><td><code>.btn-action-save</code></td><td><code>fa-check</code></td><td>Hijau gradient</td><td>Simpan perubahan</td></tr>
                        <tr><td><code>.btn-action-danger</code></td><td><code>fa-trash</code></td><td>Merah outline</td><td>Hapus record</td></tr>
                        <tr><td><code>.btn-action-edit</code></td><td><code>fa-pen</code></td><td>Amber/orange outline</td><td>Masuk mode edit</td></tr>
                        <tr><td><code>.btn-action-more</code></td><td><code>fa-ellipsis-vertical</code></td><td>Abu-abu, 30×30px</td><td>Dropdown aksi tambahan (⋮)</td></tr>
                    </tbody>
                </table>
                <div class="ug-code">
<span class="cm">// formGroup.actionBar() menghasilkan tombol ini otomatis
// Edit button:</span>
<span class="fn">formGroup</span>.<span class="kw">editButton</span>(<span class="str">'Edit Data'</span>)

<span class="cm">// Delete button (dari parameter deleteId):</span>
&lt;<span class="kw">button</span> <span class="cn">class</span>=<span class="str">"btn-action-danger btn-delete-record"</span> <span class="cn">data-id</span>=<span class="str">"${id}"</span>&gt;
  &lt;<span class="kw">i</span> <span class="cn">class</span>=<span class="str">"fa-solid fa-trash"</span>&gt;&lt;/<span class="kw">i</span>&gt; Hapus
&lt;/<span class="kw">button</span>&gt;
                </div>
            </div>

            <div class="ug-card">
                <div class="ug-card-label">Tombol di Baris Tabel (pm-table)</div>
                <div class="ug-demo-row">
                    <button class="btn-plan-icon"><i class="fa-solid fa-pen"></i> Edit</button>
                    <button class="btn-plan-icon" style="border-color:#fca5a5;color:#b91c1c;background:#fef2f2;"><i class="fa-solid fa-trash"></i> Hapus</button>
                    <button class="btn-plan-verify"><i class="fa-solid fa-check"></i> Verifikasi</button>
                </div>
                <table class="ug-spec">
                    <thead><tr><th>Kelas</th><th>Fungsi</th></tr></thead>
                    <tbody>
                        <tr><td><code>.btn-plan-icon</code></td><td>Edit / aksi umum di baris tabel inline</td></tr>
                        <tr><td><code>.btn-plan-icon</code> + override warna merah</td><td>Hapus di baris tabel</td></tr>
                        <tr><td><code>.btn-plan-verify</code></td><td>Verifikasi / approve</td></tr>
                    </tbody>
                </table>
                <div class="ug-code">
<span class="cm">// Selalu 2 tombol langsung, BUKAN dropdown ⋮</span>
&lt;<span class="kw">button</span> <span class="cn">class</span>=<span class="str">"btn-plan-icon btn-sp-edit"</span> <span class="cn">data-id</span>=<span class="str">"${r.id}"</span>&gt;
  &lt;<span class="kw">i</span> <span class="cn">class</span>=<span class="str">"fa-solid fa-pen"</span>&gt;&lt;/<span class="kw">i</span>&gt; Edit
&lt;/<span class="kw">button</span>&gt;
&lt;<span class="kw">button</span> <span class="cn">class</span>=<span class="str">"btn-plan-icon btn-sp-delete"</span>
  <span class="cn">style</span>=<span class="str">"border-color:#fca5a5;color:#b91c1c;background:#fef2f2;"</span>
  <span class="cn">data-id</span>=<span class="str">"${r.id}"</span>&gt;
  &lt;<span class="kw">i</span> <span class="cn">class</span>=<span class="str">"fa-solid fa-trash"</span>&gt;&lt;/<span class="kw">i</span>&gt; Hapus
&lt;/<span class="kw">button</span>&gt;
                </div>
            </div>
        </section>

        {{-- ─── BADGES ──────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-badges">
            <div class="ug-section-title">Badge &amp; Status</div>
            <div class="ug-section-sub"><code>.pm-badge</code> untuk tag dokumen di action bar. <code>.detail-status-inline</code> untuk status record di header.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-card-label">pm-badge — Variant Warna</div>
                <div class="ug-badge-row">
                    <span class="pm-badge pm-badge--blue"><i class="fa-solid fa-file-contract"></i> SO-2024-001</span>
                    <span class="pm-badge pm-badge--purple"><i class="fa-solid fa-briefcase"></i> WO-2024-003</span>
                    <span class="pm-badge pm-badge--amber"><i class="fa-solid fa-hard-hat"></i> FWO-2024-007</span>
                    <span class="pm-badge pm-badge--teal"><i class="fa-solid fa-location-dot"></i> Site Jakarta</span>
                    <span class="pm-badge pm-badge--green"><i class="fa-solid fa-check"></i> Selesai</span>
                    <span class="pm-badge pm-badge--gray">Cabang</span>
                    <span class="pm-badge pm-badge--red"><i class="fa-solid fa-xmark"></i> Ditolak</span>
                </div>
                <div class="ug-badge-row mt-2">
                    <span class="pm-badge pm-badge--planned">Planned</span>
                    <span class="pm-badge pm-badge--proses">Proses</span>
                    <span class="pm-badge pm-badge--selesai">Selesai</span>
                    <span class="pm-badge pm-badge--completed">Completed</span>
                </div>
                <table class="ug-spec" style="margin-top:12px;">
                    <thead><tr><th>Kelas</th><th>Warna</th><th>Dipakai untuk</th></tr></thead>
                    <tbody>
                        <tr><td><code>pm-badge--blue</code></td><td>Biru</td><td>SO / WO</td></tr>
                        <tr><td><code>pm-badge--purple</code></td><td>Ungu</td><td>WO / FWO</td></tr>
                        <tr><td><code>pm-badge--amber</code></td><td>Amber</td><td>FWO / planned</td></tr>
                        <tr><td><code>pm-badge--teal</code></td><td>Teal</td><td>Site / lokasi</td></tr>
                        <tr><td><code>pm-badge--green</code></td><td>Hijau</td><td>Status positif / selesai</td></tr>
                        <tr><td><code>pm-badge--gray</code></td><td>Abu</td><td>Info netral (cabang, dll)</td></tr>
                        <tr><td><code>pm-badge--red</code></td><td>Merah</td><td>Status negatif / ditolak</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="ug-card">
                <div class="ug-card-label">detail-status-inline — Status Pill (header detail)</div>
                <div class="ug-badge-row">
                    <span class="detail-status-inline detail-status-active">Aktif</span>
                    <span class="detail-status-inline detail-status-draft">Draft</span>
                    <span class="detail-status-inline detail-status-proses">On Progress</span>
                    <span class="detail-status-inline detail-status-pending">Pending</span>
                    <span class="detail-status-inline detail-status-selesai">Selesai</span>
                    <span class="detail-status-inline detail-status-cancel">Cancel</span>
                    <span class="detail-status-inline detail-status-done">Done</span>
                </div>
                <div class="ug-code">
<span class="cm">// Di parameter statusBadge dalam formGroup.actionBar()</span>
statusBadge: <span class="str">`&lt;span class="detail-status-inline detail-status-proses"&gt;On Progress&lt;/span&gt;`</span>
                </div>
            </div>
        </section>

        {{-- ─── FORMS ───────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-forms">
            <div class="ug-section-title">Form Elements</div>
            <div class="ug-section-sub">Semua form di dalam <code>.detail-section-body</code>. Selalu gunakan <code>formGroup.*</code> helper. State: <b>disabled</b> (view) dan <b>enabled</b> (edit).</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-card-label">Text Input</div>
                <div class="ug-form-row">
                    <div class="ug-form-col detail-section-body" style="padding:0">
                        <label class="form-label required">Nama (edit, required)</label>
                        <input type="text" class="form-control" value="PT. Contoh Jaya">
                    </div>
                    <div class="ug-form-col detail-section-body" style="padding:0">
                        <label class="form-label">Nama (disabled / view)</label>
                        <input type="text" class="form-control disabled" value="PT. Contoh Jaya" disabled>
                    </div>
                    <div class="ug-form-col detail-section-body" style="padding:0">
                        <label class="form-label">Nilai Kontrak (angka ribuan)</label>
                        <input type="text" class="form-control input-num-mask input-num-int" value="1500000">
                    </div>
                </div>
                <div class="ug-code">
<span class="fn">formGroup</span>.<span class="kw">text</span>(<span class="str">"nama"</span>, <span class="str">"Nama"</span>, res.nama, <span class="cn">true</span>, { className: <span class="str">"col-md-6"</span> })
<span class="cm">// Angka ribuan — tambahkan inputClass:</span>
<span class="fn">formGroup</span>.<span class="kw">text</span>(<span class="str">"nilai"</span>, <span class="str">"Nilai (Rp)"</span>, res.nilai, <span class="cn">false</span>, {
  className: <span class="str">"col-md-4"</span>, inputClass: <span class="str">"input-num-mask input-num-int"</span>
})
                </div>
            </div>
            <div class="ug-card">
                <div class="ug-card-label">Textarea</div>
                <div class="detail-section-body" style="padding:0">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea class="form-control" rows="3" placeholder="Tulis alamat lengkap..."></textarea>
                </div>
                <div class="ug-code">
<span class="fn">formGroup</span>.<span class="kw">textarea</span>(<span class="str">"alamat"</span>, <span class="str">"Alamat"</span>, res.alamat, { className: <span class="str">"col-md-12"</span> })
                </div>
            </div>
            <div class="ug-card">
                <div class="ug-card-label">Date Picker (Flatpickr)</div>
                <div class="ug-form-row">
                    <div class="ug-form-col detail-section-body" style="padding:0">
                        <label class="form-label">Tanggal (fp-date)</label>
                        <input type="text" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="ug-form-col detail-section-body" style="padding:0">
                        <label class="form-label">Tanggal &amp; Waktu (fp-datetime)</label>
                        <input type="text" class="form-control fp-datetime" placeholder="Pilih tanggal & waktu" autocomplete="off">
                    </div>
                </div>
                <div class="ug-code">
<span class="fn">formGroup</span>.<span class="kw">date</span>(<span class="str">"tanggal_mulai"</span>, <span class="str">"Tanggal Mulai"</span>, res.tanggal_mulai, <span class="cn">false</span>, { className: <span class="str">"col-md-4"</span> })
<span class="cm">// ⚠ Tanggal selesai tidak boleh lebih kecil dari tanggal mulai — validasi di JS sebelum submit</span>
                </div>
            </div>
            <div class="ug-card">
                <div class="ug-card-label">Checkbox / Toggle Switch</div>
                <div class="detail-section-body" style="padding:0">
                    <label class="form-label">Status</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ugChk1" checked>
                        <label class="form-check-label" for="ugChk1">Kantor Pusat</label>
                    </div>
                </div>
                <div class="ug-code">
<span class="fn">formGroup</span>.<span class="kw">checkbox</span>(<span class="str">"is_kantor_pusat"</span>, <span class="str">"Kantor Pusat"</span>, res.is_kantor_pusat, {
  className: <span class="str">"col-md-2"</span>, checkLabel: <span class="str">"Kantor Pusat"</span>
})
                </div>
            </div>
        </section>

        {{-- ─── SELECT2 ─────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-select2">
            <div class="ug-section-title">Select2</div>
            <div class="ug-section-sub">Semua dropdown pakai Select2. Mode: <b>static</b> (load semua upfront) atau <b>ajax</b> (load dari API, cached). Jangan gunakan ajax per-ketikan untuk data internal.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-card-label">Static Select (opsi tetap)</div>
                <div class="detail-section-body ug-select2-wrap" style="padding:0;margin-bottom:14px;">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="ugSelect1">
                        <option value="">-- Pilih Status --</option>
                        <option value="aktif" selected>Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>
                <div class="ug-card-label">Ajax / Load-All-Upfront</div>
                <div class="detail-section-body ug-select2-wrap" style="padding:0;margin-bottom:14px;">
                    <label class="form-label">Pelanggan</label>
                    <select class="form-select" id="ugSelect2">
                        <option value="">-- Pilih Pelanggan --</option>
                        <option value="1" selected>PT. Contoh Jaya</option>
                        <option value="2">CV. Maju Bersama</option>
                        <option value="3">PT. Sukses Makmur</option>
                    </select>
                </div>
                <div class="ug-card-label">Inline Table Select (width:resolve)</div>
                <div class="detail-section-body ug-select2-wrap" style="padding:0">
                    <select class="form-select" id="ugSelect3" style="width:260px;">
                        <option value="">-- Pilih Titik Lokasi --</option>
                        <option value="A">SP-001 – Ruang Produksi</option>
                        <option value="B">SP-002 – Ruang Penyimpanan</option>
                    </select>
                </div>
                <div class="ug-code">
<span class="cm">// Static — pakai formGroup.select() dengan mode default</span>
<span class="fn">formGroup</span>.<span class="kw">select</span>(<span class="str">"status"</span>, <span class="str">"Status"</span>, res.status, [
  { value: <span class="str">'aktif'</span>,       label: <span class="str">'Aktif'</span> },
  { value: <span class="str">'tidak_aktif'</span>, label: <span class="str">'Tidak Aktif'</span> },
])

<span class="cm">// Ajax (load-all-upfront + cache)</span>
<span class="fn">formGroup</span>.<span class="kw">select</span>(<span class="str">"id_br"</span>, <span class="str">"Pelanggan"</span>, res.id_br, [], {
  mode: <span class="str">"ajax"</span>, url: <span class="str">"/api/business-relations"</span>,
  showAll: <span class="cn">true</span>, allowClear: <span class="cn">true</span>
})

<span class="cm">// Inline table — init manual dengan width:'resolve'</span>
$(<span class="str">'.sample-titik'</span>).select2({ width: <span class="str">'resolve'</span>, dropdownParent: $table });
                </div>
            </div>
            <div class="ug-card">
                <div class="ug-card-label">Aturan Select2</div>
                <ul class="ug-rules">
                    <li>Semua <code>.form-select-dynamic</code> diinit otomatis oleh <code>initDynamicSelect()</code>.</li>
                    <li>Untuk select di dalam modal, sertakan <code>dropdownParent: $('#namaModal')</code>.</li>
                    <li>Untuk inline tabel, gunakan <code>width:'resolve'</code> agar ikut lebar kolom.</li>
                    <li class="no">Jangan pakai <code>ajax</code> dengan <code>delay</code> untuk data internal — gunakan <code>showAll:true</code> agar di-cache sekali load.</li>
                    <li class="no">Jangan init Select2 tanpa destroy jika elemen sudah pernah diinit (dynamic row).</li>
                </ul>
            </div>
        </section>

        {{-- ─── TABLES ──────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-tables">
            <div class="ug-section-title">Tabel</div>
            <div class="ug-section-sub">Dua jenis: <b>DataTable</b> (halaman index, server-side) dan <b>pm-table</b> (tabel di dalam tab/section card).</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-card-label">pm-table (tabel di dalam tab)</div>
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th style="width:36px;text-align:center;">#</th>
                            <th>No. Karyawan</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-search="emp-001 budi santoso">
                            <td style="text-align:center;color:#9ca3af;font-size:12px;">1</td>
                            <td>EMP-001</td>
                            <td>Budi Santoso</td>
                            <td><span class="detail-status-inline detail-status-active">Aktif</span></td>
                            <td style="text-align:right;">
                                <button class="btn-plan-icon"><i class="fa-solid fa-pen"></i> Edit</button>
                                <button class="btn-plan-icon ms-1" style="border-color:#fca5a5;color:#b91c1c;background:#fef2f2;"><i class="fa-solid fa-trash"></i> Hapus</button>
                            </td>
                        </tr>
                        <tr data-search="emp-002 siti rahayu">
                            <td style="text-align:center;color:#9ca3af;font-size:12px;">2</td>
                            <td>EMP-002</td>
                            <td>Siti Rahayu</td>
                            <td><span class="detail-status-inline detail-status-active">Aktif</span></td>
                            <td style="text-align:right;">
                                <button class="btn-plan-icon"><i class="fa-solid fa-pen"></i> Edit</button>
                                <button class="btn-plan-icon ms-1" style="border-color:#fca5a5;color:#b91c1c;background:#fef2f2;"><i class="fa-solid fa-trash"></i> Hapus</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="ug-code">
<span class="cm">// Struktur pm-table wajib</span>
rows.map((r, i) =&gt; <span class="str">`
&lt;tr data-search="${r.no_karyawan} ${r.nama}"&gt;
  &lt;td style="text-align:center;color:#9ca3af;font-size:12px;"&gt;${i+1}&lt;/td&gt;
  &lt;td&gt;${r.no_karyawan}&lt;/td&gt;
  &lt;td&gt;${r.nama}&lt;/td&gt;
  &lt;td style="text-align:right;"&gt;
    &lt;button class="btn-plan-icon btn-edit" data-id="${r.id}"&gt;
      &lt;i class="fa-solid fa-pen"&gt;&lt;/i&gt; Edit
    &lt;/button&gt;
    &lt;button class="btn-plan-icon btn-delete" style="border-color:#fca5a5;color:#b91c1c;background:#fef2f2;" data-id="${r.id}"&gt;
      &lt;i class="fa-solid fa-trash"&gt;&lt;/i&gt; Hapus
    &lt;/button&gt;
  &lt;/td&gt;
&lt;/tr&gt;
`</span>).join(<span class="str">''</span>)
                </div>
            </div>
            <div class="ug-card">
                <div class="ug-card-label">Aturan pm-table</div>
                <ul class="ug-rules">
                    <li>Kolom pertama: nomor urut <code>i+1</code>, warna <code>#9ca3af</code>, center, lebar ~36px.</li>
                    <li>Kolom terakhir: Aksi, rata kanan (<code>text-align:right</code>).</li>
                    <li>Setiap <code>&lt;tr&gt;</code> wajib punya <code>data-search</code> berisi semua kata kunci yang bisa dicari.</li>
                    <li>Tombol aksi selalu <b>2 tombol langsung</b>: Edit dan Hapus. Tidak pakai dropdown ⋮.</li>
                    <li class="no">Jangan gunakan <code>DataTable()</code> untuk pm-table — ini bukan server-side table.</li>
                </ul>
            </div>
        </section>

        {{-- ─── SEARCH ──────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-search">
            <div class="ug-section-title">Search Box (pm-search)</div>
            <div class="ug-section-sub">Selalu ada di atas <code>pm-table</code> dalam setiap tab. Filter client-side berdasarkan atribut <code>data-search</code>.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="pm-search" id="ugSearchDemo" style="margin-bottom:14px;">
                    <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" placeholder="Cari nama, no karyawan...">
                    <button class="pm-search-clear"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="ug-code">
<span class="cm">// HTML</span>
&lt;<span class="kw">div</span> <span class="cn">class</span>=<span class="str">"pm-search"</span> <span class="cn">id</span>=<span class="str">"search-mp"</span>&gt;
  &lt;<span class="kw">span</span> <span class="cn">class</span>=<span class="str">"pm-search-icon"</span>&gt;&lt;<span class="kw">i</span> <span class="cn">class</span>=<span class="str">"fa-solid fa-magnifying-glass"</span>&gt;&lt;/<span class="kw">i</span>&gt;&lt;/<span class="kw">span</span>&gt;
  &lt;<span class="kw">input</span> <span class="cn">type</span>=<span class="str">"text"</span> <span class="cn">placeholder</span>=<span class="str">"Cari..."</span>&gt;
  &lt;<span class="kw">button</span> <span class="cn">class</span>=<span class="str">"pm-search-clear"</span>&gt;&lt;<span class="kw">i</span> <span class="cn">class</span>=<span class="str">"fa-solid fa-xmark"</span>&gt;&lt;/<span class="kw">i</span>&gt;&lt;/<span class="kw">button</span>&gt;
&lt;/<span class="kw">div</span>&gt;

<span class="cm">// JS filter</span>
$(<span class="str">'#search-mp input'</span>).on(<span class="str">'input'</span>, <span class="cn">function</span>() {
  <span class="kw">const</span> q = $(this).val().toLowerCase().trim();
  $table.find(<span class="str">'tbody tr'</span>).each(<span class="cn">function</span>() {
    $(this).toggle($(this).data(<span class="str">'search'</span>).toLowerCase().includes(q));
  });
});
$(<span class="str">'#search-mp .pm-search-clear'</span>).on(<span class="str">'click'</span>, () =&gt;
  $(<span class="str">'#search-mp input'</span>).val(<span class="str">''</span>).trigger(<span class="str">'input'</span>)
);
                </div>
            </div>
        </section>

        {{-- ─── NAV TABS ────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-nav-tabs">
            <div class="ug-section-title">Nav Tabs</div>
            <div class="ug-section-sub">Style button (bukan underline). Aktif = latar biru navy. Dirender sebagai Bootstrap <code>.nav .nav-tabs</code>.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <ul class="nav nav-tabs" style="margin-bottom:14px;">
                    <li class="nav-item"><a class="nav-link active" href="#">Informasi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Sampling ENV</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Sampling WE</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Man Power</a></li>
                </ul>
                <div class="ug-code">
&lt;<span class="kw">ul</span> <span class="cn">class</span>=<span class="str">"nav nav-tabs"</span> <span class="cn">role</span>=<span class="str">"tablist"</span>&gt;
  &lt;<span class="kw">li</span> <span class="cn">class</span>=<span class="str">"nav-item"</span>&gt;
    &lt;<span class="kw">a</span> <span class="cn">class</span>=<span class="str">"nav-link active"</span> <span class="cn">data-bs-toggle</span>=<span class="str">"tab"</span> <span class="cn">href</span>=<span class="str">"#tabInfo"</span>&gt;Informasi&lt;/<span class="kw">a</span>&gt;
  &lt;/<span class="kw">li</span>&gt;
&lt;/<span class="kw">ul</span>&gt;
                </div>
            </div>
        </section>

        {{-- ─── PAGE HEADER ─────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-page-header">
            <div class="ug-section-title">Page Header</div>
            <div class="ug-section-sub">Setiap halaman punya dua layer: <b>banner</b> gradient (di layout app.blade.php) dan <b>header bar</b> (judul + tombol + tabs).</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-card-label">Page Header Bar</div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="font-size:15px;font-weight:600;color:#111827;">Business Relations</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <button class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah</button>
                        <ul class="nav nav-tabs mb-0">
                            <li class="nav-item"><a class="nav-link active" href="#">Semua</a></li>
                            <li class="nav-item"><a class="nav-link" href="#">Pelanggan</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── ACTION BAR ──────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-action-bar">
            <div class="ug-section-title">Action Bar (Detail Page)</div>
            <div class="ug-section-sub">Sticky bar di atas halaman detail. Dirender oleh <code>formGroup.actionBar()</code>.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-card-label">Contoh — FWO dengan SO + WO badges</div>
                <div class="ug-actionbar-preview">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="detail-number">FWO-2024-001</span>
                            <span class="detail-status-inline detail-status-proses">On Progress</span>
                        </div>
                        <div class="detail-date">Dibuat 01 Jan 2024 &nbsp;·&nbsp; Diupdate 15 Jul 2024</div>
                        <div class="ug-detail-tags">
                            <span class="pm-badge pm-badge--blue"><i class="fa-solid fa-file-contract"></i> SO-2024-005</span>
                            <span class="pm-badge pm-badge--purple"><i class="fa-solid fa-briefcase"></i> WO-2024-003</span>
                            <span class="pm-badge pm-badge--teal"><i class="fa-solid fa-location-dot"></i> Gedung A – Jakarta</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;transform:translateY(-20px);">
                        <button class="btn-action-secondary"><i class="fa-solid fa-rotate-right"></i></button>
                        <button class="btn-action-edit editing"><i class="fa-solid fa-xmark"></i> Batal</button>
                        <button class="btn-action-save"><i class="fa-solid fa-check"></i> Simpan</button>
                        <button class="btn-action-danger"><i class="fa-solid fa-trash"></i> Hapus</button>
                    </div>
                </div>
                <div class="ug-code">
<span class="fn">formGroup</span>.<span class="kw">actionBar</span>({
  number:      res.no_fwo,
  createdAt:   res.created_at,
  updatedAt:   res.updated_at,
  statusBadge: <span class="str">`&lt;span class="detail-status-inline detail-status-proses"&gt;On Progress&lt;/span&gt;`</span>,
  tags: soBadge + woBadge + siteBadge,  <span class="cm">// urutan: SO → WO → Site</span>
  editText:  <span class="str">'Edit'</span>,
  deleteId:  res.id_fwo,
  deleteText:<span class="str">'Hapus'</span>,
})
<span class="cm">
// Urutan tags (dari kiri ke kanan):
// Dokumen induk dulu → SO (blue) → WO (purple) → FWO (amber)
// Lalu konteks → Site (teal) → Kantor Pusat/Cabang (gray)</span>
                </div>
            </div>
        </section>

        {{-- ─── SECTION CARD ────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-section-card">
            <div class="ug-section-title">Section Card</div>
            <div class="ug-section-sub">Container utama untuk mengelompokkan form fields. Dirender oleh <code>formGroup.sectionCard()</code>.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-card-label">Contoh Section Card</div>
                <div class="ug-sc-preview">
                    <div class="ug-sc-header">
                        <div class="ug-sc-icon icon-navy"><i class="fa-solid fa-building"></i></div>
                        <div style="font-size:13px;font-weight:600;color:#111827;">Informasi Perusahaan</div>
                        <div style="font-size:11px;color:#9ca3af;margin-left:auto;">Data umum</div>
                        <button class="btn-action-edit ms-2"><i class="fa-solid fa-pen"></i> Edit</button>
                    </div>
                    <div class="ug-sc-body detail-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Nama</label>
                                <input type="text" class="form-control" value="PT. Contoh Jaya">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <div style="padding-top:4px;"><span class="detail-status-inline detail-status-active">Aktif</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <table class="ug-spec" style="margin-top:14px;">
                    <thead><tr><th>Color class</th><th>Tampilan</th><th>Dipakai untuk</th></tr></thead>
                    <tbody>
                        <tr><td><code>icon-navy</code></td><td><span class="ug-sc-icon icon-navy" style="display:inline-flex;width:24px;height:24px;border-radius:6px;"><i class="fa-solid fa-building" style="font-size:11px;"></i></span></td><td>Informasi utama (default)</td></tr>
                        <tr><td><code>icon-blue</code></td><td><span class="ug-sc-icon icon-blue" style="display:inline-flex;width:24px;height:24px;border-radius:6px;"><i class="fa-solid fa-info" style="font-size:11px;"></i></span></td><td>Info tambahan / referensi</td></tr>
                        <tr><td><code>icon-green</code></td><td><span class="ug-sc-icon icon-green" style="display:inline-flex;width:24px;height:24px;border-radius:6px;"><i class="fa-solid fa-check" style="font-size:11px;"></i></span></td><td>Selesai / konfirmasi</td></tr>
                        <tr><td><code>icon-amber</code></td><td><span class="ug-sc-icon icon-amber" style="display:inline-flex;width:24px;height:24px;border-radius:6px;"><i class="fa-solid fa-triangle-exclamation" style="font-size:11px;"></i></span></td><td>Peringatan / anggaran</td></tr>
                        <tr><td><code>icon-purple</code></td><td><span class="ug-sc-icon icon-purple" style="display:inline-flex;width:24px;height:24px;border-radius:6px;"><i class="fa-solid fa-flask" style="font-size:11px;"></i></span></td><td>Lab / ilmiah</td></tr>
                    </tbody>
                </table>
                <div class="ug-code">
<span class="fn">formGroup</span>.<span class="kw">sectionCard</span>(
  { icon: <span class="str">'fa-building'</span>, color: <span class="str">'icon-navy'</span>,
    title: <span class="str">'Informasi Perusahaan'</span>, subtitle: <span class="str">'Data umum'</span>,
    editTitle: <span class="str">'Edit'</span> },
  <span class="str">`&lt;div class="row g-3"&gt;
    ${formGroup.text('nama', 'Nama', res.nama, true, { className: 'col-md-6' })}
  &lt;/div&gt;`</span>
)
                </div>
            </div>
        </section>

        {{-- ─── KPI ─────────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-kpi">
            <div class="ug-section-title">KPI Cards</div>
            <div class="ug-section-sub">Ringkasan angka penting di bagian atas halaman detail. Gunakan <code>.pm-kpi-row</code> dan <code>.pm-kpi-card</code>.</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="pm-kpi-row">
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-icon" style="background:var(--primary-500);"><i class="fa-solid fa-file-contract"></i></div>
                        <div>
                            <div class="pm-kpi-label">Total WO</div>
                            <div class="pm-kpi-value">12</div>
                            <div class="pm-kpi-sub">dari 1 SO</div>
                        </div>
                    </div>
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-icon" style="background:#10b981;"><i class="fa-solid fa-check"></i></div>
                        <div>
                            <div class="pm-kpi-label">Selesai</div>
                            <div class="pm-kpi-value">8</div>
                            <div class="pm-kpi-sub">66.7%</div>
                        </div>
                    </div>
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-icon" style="background:#f59e0b;"><i class="fa-solid fa-bolt"></i></div>
                        <div>
                            <div class="pm-kpi-label">Berjalan</div>
                            <div class="pm-kpi-value">3</div>
                            <div class="pm-kpi-sub">on progress</div>
                        </div>
                    </div>
                    <div class="pm-kpi-card">
                        <div class="pm-kpi-icon" style="background:#6b7280;"><i class="fa-solid fa-clock"></i></div>
                        <div>
                            <div class="pm-kpi-label">Belum Mulai</div>
                            <div class="pm-kpi-value">1</div>
                            <div class="pm-kpi-sub">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── MODAL ───────────────────────────────────────────────────── --}}
        <section class="ug-section" id="ug-modal">
            <div class="ug-section-title">Modal</div>
            <div class="ug-section-sub">Bootstrap modal standar. Footer: Batal (kiri) + Simpan (kanan).</div>
            <div class="ug-divider"></div>
            <div class="ug-card">
                <div class="ug-demo-row" style="margin-bottom:16px;">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ugModalDemo">
                        <i class="fa-solid fa-eye"></i> Lihat Contoh Modal
                    </button>
                </div>
                <div class="ug-card-label">Aturan Modal</div>
                <ul class="ug-rules">
                    <li>Footer selalu: <code>.btn-action-secondary</code> "Batal" (kiri) — <code>.btn-action-save</code> "Simpan" (kanan).</li>
                    <li>Label required diberi <code>class="required"</code> pada label — tampil <code>*</code> merah otomatis dari CSS.</li>
                    <li>Setelah simpan sukses: tutup modal → reload data → SweetAlert success.</li>
                    <li>Konfirmasi hapus selalu pakai SweetAlert2 <code>Swal.fire()</code>.</li>
                    <li>Select2 dalam modal wajib <code>dropdownParent: $('#namaModal')</code>.</li>
                    <li class="no">Jangan pakai <code>alert()</code> atau <code>confirm()</code> native browser.</li>
                    <li class="no">Jangan reinit FilePond di modal yang sama tanpa <code>.destroy()</code> terlebih dulu.</li>
                </ul>
                <div class="ug-code">
<span class="cm">&lt;!-- Struktur modal standar --&gt;</span>
&lt;<span class="kw">div</span> <span class="cn">class</span>=<span class="str">"modal fade"</span> <span class="cn">id</span>=<span class="str">"spModal"</span>&gt;
  &lt;<span class="kw">div</span> <span class="cn">class</span>=<span class="str">"modal-dialog"</span>&gt;&lt;<span class="kw">div</span> <span class="cn">class</span>=<span class="str">"modal-content"</span>&gt;
    &lt;<span class="kw">div</span> <span class="cn">class</span>=<span class="str">"modal-header"</span>&gt;
      &lt;<span class="kw">h5</span> <span class="cn">class</span>=<span class="str">"modal-title"</span>&gt;Tambah Data&lt;/<span class="kw">h5</span>&gt;
      &lt;<span class="kw">button</span> <span class="cn">class</span>=<span class="str">"btn-close"</span> <span class="cn">data-bs-dismiss</span>=<span class="str">"modal"</span>&gt;&lt;/<span class="kw">button</span>&gt;
    &lt;/<span class="kw">div</span>&gt;
    &lt;<span class="kw">div</span> <span class="cn">class</span>=<span class="str">"modal-body"</span>&gt;
      <span class="cm">&lt;!-- fields --&gt;</span>
    &lt;/<span class="kw">div</span>&gt;
    &lt;<span class="kw">div</span> <span class="cn">class</span>=<span class="str">"modal-footer"</span>&gt;
      &lt;<span class="kw">button</span> <span class="cn">class</span>=<span class="str">"btn-action-secondary"</span> <span class="cn">data-bs-dismiss</span>=<span class="str">"modal"</span>&gt;Batal&lt;/<span class="kw">button</span>&gt;
      &lt;<span class="kw">button</span> <span class="cn">class</span>=<span class="str">"btn-action-save"</span> <span class="cn">id</span>=<span class="str">"spModal-btn-save"</span>&gt;
        &lt;<span class="kw">i</span> <span class="cn">class</span>=<span class="str">"fa-solid fa-check"</span>&gt;&lt;/<span class="kw">i</span>&gt; Simpan
      &lt;/<span class="kw">button</span>&gt;
    &lt;/<span class="kw">div</span>&gt;
  &lt;/<span class="kw">div</span>&gt;&lt;/<span class="kw">div</span>&gt;
&lt;/<span class="kw">div</span>&gt;
                </div>
            </div>
        </section>

    </div>{{-- /.ug-main --}}
</div>{{-- /.ug-wrap --}}

{{-- Modal Demo --}}
<div class="modal fade" id="ugModalDemo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus me-2 text-primary"></i> Tambah Sampling Point</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required">Kode</label>
                        <input type="text" class="form-control" placeholder="mis: SP-001">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Nama</label>
                        <input type="text" class="form-control" placeholder="Nama titik sampling">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="ugModalSelect">
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-action-save"><i class="fa-solid fa-check"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sidebar active spy
    const sections = document.querySelectorAll('.ug-section');
    const links = document.querySelectorAll('#ugSidebar a');
    window.addEventListener('scroll', function () {
        let cur = '';
        sections.forEach(function(s) {
            if (window.scrollY >= s.offsetTop - 100) cur = s.id;
        });
        links.forEach(function(a) {
            a.classList.toggle('ug-active', a.getAttribute('href') === '#' + cur);
        });
    }, { passive: true });

    // Init Select2 demo
    if (typeof $.fn.select2 !== 'undefined') {
        $('#ugSelect1').select2({ width: '100%', placeholder: '-- Pilih Status --' });
        $('#ugSelect2').select2({ width: '100%', placeholder: '-- Pilih Pelanggan --', allowClear: true });
        $('#ugSelect3').select2({ width: '260px', placeholder: '-- Pilih Titik Lokasi --' });
        $('#ugModalSelect').select2({ width: '100%', dropdownParent: $('#ugModalDemo') });
    }

    // Init Flatpickr demo
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.fp-date', { dateFormat: 'Y-m-d', locale: 'id', allowInput: true });
        flatpickr('.fp-datetime', { enableTime: true, dateFormat: 'Y-m-d H:i', locale: 'id', allowInput: true });
    }

    // Init numeric mask demo
    if (typeof initNumericMask !== 'undefined') {
        initNumericMask(document.body);
    }
});
</script>
@endsection
