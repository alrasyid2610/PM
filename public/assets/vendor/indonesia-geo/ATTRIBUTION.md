# Peta provinsi Indonesia (38 provinsi)

File: `indonesia-38-provinces.geojson`

Sumber: "Indonesia GeoJSON & TopoJSON Maps (38 Provinces)" — https://github.com/denyherianto/indonesia-geojson-topojson-maps-with-38-provinces
Lisensi data: **CC BY 4.0** (https://creativecommons.org/licenses/by/4.0/) — wajib mencantumkan atribusi; kode: MIT.
Catatan sumber hulu: batas wilayah dapat berasal dari sumber pihak ketiga (lihat DATA_LICENSE.md di repo asal) — pastikan penggunaan produksi/komersial sesuai ketentuan tersebut.

## Perubahan yang kami lakukan (wajib disebut oleh CC BY)
- Properti diganti menjadi `nama` (nama provinsi di tabel `wilayah`) dan `kode_wilayah` (kode di tabel `wilayah`).
- `KODE_PROV` asli **tidak dipakai**: untuk provinsi Papua baru kodenya salah/duplikat (mis. 91 dipakai Papua Tengah, Papua Selatan, dan Papua Pegunungan). Pencocokan dilakukan lewat NAMA provinsi; "DKI Jakarta" dicocokkan ke "Daerah Khusus Ibukota Jakarta".
- Geometri tidak diubah; JSON hanya diminify.

Library peta: Leaflet 1.9.4 (BSD-2-Clause) di `../leaflet/`.
