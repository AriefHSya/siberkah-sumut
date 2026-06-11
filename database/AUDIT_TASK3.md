# Audit Validasi Bisnis Kritis — Temuan & Perbaikan

Audit dilakukan terhadap: `Pekerjaan.php`, `Reviu.php`, `Verif_kab.php`,
`Verif_prov.php`, `Permohonan.php`, `Penyaluran_kab.php`, `Capaian.php`,
serta model terkait dan `database/schema.sql`.

## Ringkasan Status per Aturan (CLAUDE.md "VALIDASI BISNIS KRITIS")

| # | Aturan | Status sebelum audit | Tindakan |
|---|---|---|---|
| 1 | Batas waktu (`cek_deadline`) HARD BLOCK saat submit | ✅ Sudah benar (`Pekerjaan::submit`) | Tidak diubah |
| 2 | Bertahap wajib `nilai_kontrak > 200jt` | ⚠️ Hanya di `simpan()`, hilang di `update()` | **Diperbaiki** |
| 3 | Belanja pendukung maks 5% nilai BKP, integer math | ⚠️ Pakai float (`* 0.05`) di `simpan()` & `update()` | **Diperbaiki** (pakai `* 20 >`) |
| 4 | SP2D hanya jika verifikasi prov `disetujui` | ✅ Sudah benar (`simpan_sp2d`) | Tidak diubah |
| 5 | Catatan wajib jika `ditolak`/`perlu_perbaikan` | ✅ Sudah benar di semua `putuskan()` | Tidak diubah |

## Temuan & Perbaikan Detail

### 1. `Pekerjaan.php::simpan()` — float precision pada cek 5%
- **File:line**: `application/controllers/Pekerjaan.php` (sekitar baris 171, sebelum edit)
- **Temuan**: `$nilai_pendukung > ($bkp->nilai * 0.05)` rawan presisi float untuk nilai miliaran.
- **Perbaikan**: diganti `($nilai_pendukung * 20) > $bkp->nilai`.

### 2. `Pekerjaan.php::update()` — beberapa guard hilang
- **File:line**: `application/controllers/Pekerjaan.php::update()` (sekitar baris 271-329, sebelum edit)
- **Temuan**:
  - Tidak ada cek status (`draft`/`inspektorat_revisi`/`skpkd_kab_revisi`) — pekerjaan yang sudah lanjut ke tahap berikutnya tetap bisa diedit via POST langsung ke `pekerjaan/update/{id}`.
  - Tidak ada cek kepemilikan/IDOR — OPD lain (atau user kab/kota lain) bisa mengedit pekerjaan milik OPD/kabkota lain dengan menebak ID.
  - Tidak ada validasi `bertahap` wajib `nilai_kontrak > 200jt` (hanya ada di `simpan()`).
  - Cek 5% pendukung pakai float (`* 0.05`).
- **Perbaikan**: menambahkan keempat guard di atas, mencerminkan persis guard yang sudah ada di `edit()`.

### 3. `Reviu.php::putuskan()` — IDOR & transisi status
- **File:line**: `application/controllers/Reviu.php::putuskan()` (sekitar baris 306-313, sebelum edit)
- **Temuan**:
  - Tidak ada guard kabkota (berbeda dengan `form()` yang sudah punya). Inspektorat kab/kota A berpotensi memutuskan reviu tahapan milik kab/kota B jika tahu `reviu_id`.
  - Tidak ada validasi `$tahapan->status`. `putuskan()` bisa dipanggil berulang/di luar urutan (mis. setelah `inspektorat_approved` atau `inspektorat_revisi`), menimpa status & mengirim notifikasi ganda.
- **Perbaikan**: menambahkan guard kabkota (sama seperti `form()`) dan guard `$tahapan->status === 'inspektorat_reviu'`.

### 4. `Verif_kab.php::putuskan()` — IDOR & transisi status
- **File:line**: `application/controllers/Verif_kab.php::putuskan()` (sekitar baris 259-269, sebelum edit)
- **Temuan**: sama persis dengan #3 — tidak ada guard kabkota maupun guard status (`form()` punya, `putuskan()` tidak).
- **Perbaikan**: menambahkan guard kabkota (`skpkd_kabkota`) dan guard `$tahapan->status === 'skpkd_kab_verif'`.

### 5. `Verif_prov.php::putuskan()` — transisi status & re-approval
- **File:line**: `application/controllers/Verif_prov.php::putuskan()` (sekitar baris 169-179, sebelum edit)
- **Temuan**: tidak ada guard status. Karena status tahapan **tetap** `skpkd_prov_verif` setelah `disetujui` (menunggu SP2D), `putuskan()` bisa dipanggil ulang berkali-kali — termasuk membatalkan persetujuan menjadi `ditolak`/`perlu_perbaikan` setelah disetujui, atau mengirim notifikasi/Telegram berulang untuk keputusan yang sama.
- **Perbaikan**: menambahkan guard `$tahapan->status === 'skpkd_prov_verif' && $verif_prov->hasil_verifikasi !== 'disetujui'`.

### 6. `Verif_prov.php::konfirmasi_transfer()` — idempotensi
- **File:line**: `application/controllers/Verif_prov.php::konfirmasi_transfer()` (sekitar baris 321-333, sebelum edit)
- **Temuan**: tidak ada cek `status_transfer` sebelumnya. Klik ganda / replay request akan memanggil `_set_disalurkan()` berkali-kali → notifikasi & Telegram terkirim ulang, `set_status()` dipanggil ulang dengan status yang sama.
- **Perbaikan**: menambahkan guard `$penyaluran->status_transfer !== 'selesai'`.

### 7. `Verif_prov.php::simpan_sp2d_permohonan()` — idempotensi
- **File:line**: `application/controllers/Verif_prov.php::simpan_sp2d_permohonan()` (sekitar baris 495-507, sebelum edit)
- **Temuan**: jika `status_sp2d` sudah `'selesai'`, submit ulang form akan memproses ulang seluruh item (`_set_disalurkan()` per item) → notifikasi/Telegram ganda ke setiap SKPKD & OPD.
- **Perbaikan**: menambahkan guard di awal method — tolak jika `$pm->status_sp2d === 'selesai'`.

### 8. `Permohonan.php`, `Penyaluran_kab.php`, `Capaian.php`
- **Temuan**: tidak ditemukan celah — semua method sensitif sudah memiliki guard scoping kabkota dan validasi status transisi yang sesuai.
- **Tindakan**: tidak diubah.

### 9. UNIQUE KEY pada tabel upsert 1:1 (`buat_atau_ambil`)
- **File**: `database/schema.sql` (baris ~522-670)
- **Temuan**: `trx_reviu_inspektorat`, `trx_verifikasi_skpkd_kab`,
  `trx_verifikasi_skpkd_prov`, `trx_penyaluran_dana` **sudah** memiliki
  `UNIQUE KEY tahapan_id (tahapan_id)` di `schema.sql`.
- **Tindakan**: tidak ada perubahan skema diperlukan untuk instalasi baru.
  Sebagai jaring pengaman untuk database lama (predate constraint ini),
  ditambahkan `database/upsert_unique_migration.sql` (idempotent, pakai
  `ADD UNIQUE IF NOT EXISTS`) dan dicatat di CLAUDE.md bagian MIGRASI
  DATABASE.

## File yang Diubah

- `application/controllers/Pekerjaan.php` — fix #1, #2
- `application/controllers/Reviu.php` — fix #3
- `application/controllers/Verif_kab.php` — fix #4
- `application/controllers/Verif_prov.php` — fix #5, #6, #7
- `database/upsert_unique_migration.sql` (baru) — fix #9
- `CLAUDE.md` — dokumentasi aturan #3 (integer math), aturan baru #7
  (transisi status & scoping kab/kota di `putuskan()`), aturan baru #8
  (race condition upsert), migration SQL, dan koreksi catatan stub reset
  password.

Tidak ada perilaku bisnis yang sudah benar yang diubah — seluruh perbaikan
bersifat menambah guard pada jalur yang sebelumnya tidak tervalidasi.
