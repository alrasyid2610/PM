# Aturan Kerja Project PM

## ⚠️ WAJIB DIBACA SEBELUM MULAI — SETIAP TASK, TANPA TERKECUALI

**Sebelum melakukan analisa apapun atau perubahan apapun pada project ini, WAJIB baca dulu referensi project.** Ini bukan opsional dan bukan cuma untuk task besar — berlaku juga untuk pertanyaan kecil, "lanjutan" dari task sebelumnya, atau hal yang terasa sudah familiar dari percakapan. Jangan andalkan ingatan dari sesi sebelumnya — file referensi bisa berubah kapan saja.

Sejak 2026-08-19, referensi ini dipecah jadi 2 lapis:

```
C:\Users\5891\Documents\Obsidian Vault\PM\Project Reference.md   ← index utama + pola/aturan lintas modul
C:\Users\5891\Documents\Obsidian Vault\PM\Modules\*.md            ← detail per modul bisnis (skema tabel + behavior)
```

### Alur yang harus diikuti:

1. **Selalu baca `Project Reference.md` dulu** (index) — walau kelihatannya sudah tahu isinya dari sesi sebelumnya.
2. **Tentukan modul mana yang relevan** dengan topik yang sedang dibahas, lihat tabel [📚 Modul](Project%20Reference.md) di index tersebut.
3. **Baca file modul terkait** di `Modules/` sebelum menganalisa atau mengubah apapun yang menyentuh modul itu — jangan cukup baca index saja kalau topiknya spesifik ke 1 modul.
4. **Cek** apakah topik yang sedang didiskusikan sudah ada di referensi (index atau file modul)
   - **Sudah ada** → langsung analisa berdasarkan data referensi, lanjutkan pekerjaan
   - **Belum ada** → informasikan ke user bahwa topik ini belum ada di referensi, tanyakan apakah perlu ditambahkan
5. **Jika ada perubahan/tambahan** pada referensi → tulis ke file yang tepat (index untuk hal lintas modul, file `Modules/*.md` untuk detail 1 modul) setelah mendapat persetujuan user
6. **Fitur/behavior baru yang selesai dikerjakan** → wajib didokumentasikan ke file yang tepat sebelum task dianggap selesai, bukan opsional. Task belum benar-benar "done" kalau referensinya belum diupdate.
7. **Modul baru yang belum punya file sendiri** → ikuti pola modul lain di `Modules/` (skema tabel + kondisi/behavior + relasi digabung 1 file), lalu tambahkan barisnya ke tabel index di `Project Reference.md`.

### Aturan lainnya:
- Selalu gunakan **Bahasa Indonesia** dalam semua respons
- Perubahan struktur database (kolom/tabel baru ke depannya) → buat **file migration Laravel**, jangan tulis query SQL manual. Skema lama yang sudah ada dibiarkan seperti sekarang (tidak perlu dibuatkan migration retroaktif).
- Migration dijalankan sendiri oleh user baik di local maupun live (`php artisan migrate`) — jangan jalankan `artisan migrate` sendiri di kedua environment tersebut
- Jangan lanjutkan task tanpa arahan yang jelas dari user
- **Setelah memperbaiki bug** → grep/cari pola yang sama di seluruh codebase sebelum menganggap task selesai. Bug yang sama sering muncul berulang di banyak file/module (contoh nyata: field PIC yang salah sumber data ternyata ada di 6+ file berbeda; validasi DP-block Termin ternyata harus diterapkan di 3 jalur input terpisah). Jangan asumsikan cukup 1 tempat.
- **Kalau mengubah relasi/tipe kolom yang sudah dipakai** (misal ganti target FK, ubah tipe data) → selalu informasikan eksplisit ke user dampaknya terhadap data yang sudah tersimpan (data lama kemungkinan jadi tidak valid/kosong dan perlu diperbaiki manual).

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
