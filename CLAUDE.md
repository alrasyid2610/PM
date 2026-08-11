# Aturan Kerja Project PM

## ⚠️ WAJIB DIBACA SEBELUM MULAI

Sebelum melakukan **analisa apapun** atau **perubahan apapun** pada project ini, kamu HARUS membaca terlebih dahulu file referensi project:

```
C:\Users\5891\Documents\Obsidian Vault\PM\Project Reference.md
```

### Alur yang harus diikuti:

1. **Baca** `Project Reference.md` dari Obsidian vault
2. **Cek** apakah topik yang sedang didiskusikan sudah ada di referensi
   - **Sudah ada** → langsung analisa berdasarkan data referensi, lanjutkan pekerjaan
   - **Belum ada** → informasikan ke user bahwa topik ini belum ada di referensi, tanyakan apakah perlu ditambahkan
3. **Jika ada perubahan/tambahan** pada referensi → tulis langsung ke `Project Reference.md` setelah mendapat persetujuan user
4. **Fitur/behavior baru yang selesai dikerjakan** → wajib didokumentasikan ke `Project Reference.md` sebelum task dianggap selesai, bukan opsional. Task belum benar-benar "done" kalau referensinya belum diupdate.

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
