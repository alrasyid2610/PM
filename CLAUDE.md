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

### Aturan lainnya:
- Selalu gunakan **Bahasa Indonesia** dalam semua respons
- Perubahan struktur database → berikan **query SQL** saja, jangan jalankan sendiri
- Jangan lanjutkan task tanpa arahan yang jelas dari user

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
