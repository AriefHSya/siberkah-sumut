# MANUAL BOOK — SIBERKAH SUMUT v4.1.0
**Sistem Informasi Bantuan Keuangan Daerah Provinsi Sumatera Utara**
Berdasarkan SE Gubernur No. 900.1.1.3689 / 8 Mei 2026

---

## CARA LOGIN & LOGOUT

1. Buka `https://siberkah.sumutprov.go.id` → klik **Masuk ke Sistem**
2. Masukkan **Username** dan **Password** → klik **Masuk**
3. Pilih / konfirmasi **Tahun Anggaran** aktif
4. Untuk keluar: klik **Keluar** di bagian bawah sidebar kiri

> **Lupa password?** Hubungi Admin Provinsi / Superadmin untuk reset.

---

## ALUR BISNIS UTAMA

```
OPD Teknis         → Input Pekerjaan → Upload Dokumen → Submit
Inspektorat        → Reviu Checklist → Upload LHR → Putuskan
SKPKD Kab/Kota     → Verifikasi → Upload Permohonan → Kirim ke Provinsi (menu: Permohonan)
Admin Provinsi     → Verifikasi Bundel Permohonan → Cetak Nota + Ringkasan → Input SP2D
SKPKD Kab/Kota     → Konfirmasi Penyaluran RKUD (menu: Penyaluran) → input kode transaksi
OPD Teknis         → Input Capaian Output Fisik (menu: Capaian, khusus Tahap I bertahap)
```

---

## ROLE 1 — OPD TEKNIS

**Siapa:** Dinas/instansi pelaksana kegiatan BKP di Kab/Kota.

### A. Input Pekerjaan Baru
1. Menu **Pekerjaan** → klik **Input Pekerjaan Baru**
2. Pilih **BKP** dari dropdown (hanya BKP milik Kab/Kota sendiri yang belum ada pekerjaan)
3. Pilih **Jenis Penyaluran**: Bertahap / Sekaligus / Khusus Mendesak / Darurat Bencana
   - *Bertahap*: nilai kontrak wajib > Rp 200 juta
4. Isi 17 field: nama kegiatan, nomor kontrak, tanggal, SPMK, penyedia, nilai kontrak, lokasi, dll
5. Pin lokasi di peta (geser marker ke titik lokasi pekerjaan)
6. Klik **Simpan** → status menjadi **Draft**

### B. Upload Dokumen Persyaratan
1. Buka **Detail Pekerjaan** → klik **Upload Dokumen**
2. Pilih jenis dokumen → pilih file (PDF/JPG/PNG, maks 10 MB) → klik **Upload**
3. Ulangi untuk setiap dokumen persyaratan yang dibutuhkan

### C. Submit ke Inspektorat
1. Pastikan semua dokumen sudah diupload
2. Di halaman Detail → klik **Submit ke Inspektorat**
3. Sistem cek otomatis:
   - Batas waktu pengajuan belum lewat
   - Field wajib sudah terisi (nomor kontrak, SPMK, nilai kontrak, nama penyedia)
4. Jika lolos → status berubah ke **Diajukan**

### D. Tindak Lanjut Revisi
- Jika dikembalikan Inspektorat/SKPKD → status menjadi **Perlu Revisi**
- Buka Detail → klik **Edit** → perbaiki data → **Simpan** → **Submit** ulang

### E. Input Capaian Output Fisik *(khusus Tahap I bertahap)*
- Setelah dana Tahap I dikonfirmasi diterima → menu **Capaian** aktif
- Isi: persentase fisik (slider 0–100%), tanggal realisasi, nomor BA Kemajuan, keterangan, foto
- Klik **Simpan Capaian** → status berubah ke **Capaian Tahap I**
- Setelah ini, Tahap II dapat disubmit

---

## ROLE 2 — INSPEKTORAT

**Siapa:** Unit pengawas internal Kab/Kota yang mereviu kelayakan BKP.

### A. Melihat Antrian Reviu
1. Menu **Reviu** → lihat daftar pekerjaan dengan status **Menunggu Reviu**
2. Filter berdasarkan status, jenis penyaluran, atau cari berdasarkan kode BKP

### B. Melakukan Reviu
1. Klik **Reviu** pada baris pekerjaan
2. Isi **21 item checklist** (CK-01 s/d CK-21): pilih Sesuai / Tidak Sesuai / Tidak Berlaku
3. Tambahkan catatan per item jika diperlukan
4. Upload **LHR** (Laporan Hasil Reviu): klik **Upload LHR** → pilih file PDF

### C. Memberikan Keputusan
1. Setelah semua checklist diisi dan LHR diupload → klik **Putuskan**
2. Pilih hasil:
   - **Disetujui** → pekerjaan lanjut ke SKPKD Kab
   - **Perlu Perbaikan** → dikembalikan ke OPD (*catatan wajib diisi*)
   - **Ditolak** → pekerjaan ditolak (*catatan wajib diisi*)
3. Sistem otomatis kirim notifikasi ke pihak terkait

### D. Cetak Dokumen
- **Kertas Kerja Reviu**: klik ikon cetak di halaman form reviu
- **Rekap Reviu**: tersedia di halaman detail setelah reviu selesai

---

## ROLE 3 — SKPKD KAB/KOTA

**Siapa:** Satuan Kerja Pengelola Keuangan Daerah (BPKD/BKAD) Kab/Kota.

### A. Verifikasi Permohonan Kegiatan
1. Menu **Verifikasi** → lihat antrian pekerjaan status **Reviu Selesai**
2. Klik **Verifikasi** pada pekerjaan yang akan diproses
3. Review dokumen pekerjaan dan hasil reviu inspektorat
4. Upload **Dokumen Permohonan Pencairan** (surat permohonan, lampiran)

### B. Memberikan Keputusan Verifikasi
1. Klik **Putuskan**
2. Pilih hasil:
   - **Disetujui** → pekerjaan siap dibundel dalam permohonan
   - **Perlu Perbaikan** → dikembalikan ke OPD
   - **Ditolak** → pekerjaan ditolak
3. Jika tolak/perbaikan → catatan wajib diisi

### C. Membuat Permohonan Pencairan (Bundel)
*Setelah satu atau beberapa kegiatan diverifikasi dan disetujui.*

1. Menu **Permohonan** → klik **Buat Permohonan Baru**
2. Pilih jenis penyaluran (Bertahap / Sekaligus / Khusus)
3. Centang kegiatan-kegiatan yang akan dibundel dalam satu permohonan
4. Klik **Simpan** → permohonan dibuat dengan nomor otomatis
5. Klik **Ajukan** → permohonan dikirim ke Admin Provinsi

### D. Konfirmasi Penyaluran Dana (menu Penyaluran)
*Dilakukan setelah Admin Provinsi menginput SP2D dan dana ditransfer ke RKUD.*

1. Menu **Penyaluran** → lihat daftar permohonan yang sudah ada SP2D Provinsi
2. Pada baris permohonan yang SP2D-nya berstatus **Menunggu Konfirmasi** → klik **Konfirmasi**
3. Isi data:
   - **Kode Transaksi RKUD**: nomor referensi transaksi masuk di rekening RKUD
   - **Tanggal Masuk RKUD**: tanggal dana diterima di rekening
   - **Nilai Diterima (Rp)**: nilai yang masuk ke RKUD
4. Klik **Konfirmasi Penerimaan** → status semua kegiatan dalam permohonan berubah ke **Dikonfirmasi**

> **Catatan penting:**
> - Untuk permohonan **Bertahap Tahap I** → setelah dikonfirmasi, menu **Capaian** aktif untuk OPD
> - Untuk permohonan **Sekaligus/Khusus/Tahap II** → kegiatan langsung berstatus **Selesai**

### E. Laporan Akhir Kab/Kota
1. Menu **Laporan** → **Laporan Akhir Kab/Kota**
2. Pilih Tahun Anggaran → klik **Tampilkan**
3. Klik **Cetak Laporan** untuk dokumen resmi ber-KOP dengan tanda tangan

---

## ROLE 4 — ADMIN PROVINSI

**Siapa:** Staf BKAD Provinsi yang mengelola sistem dan melakukan verifikasi final.

### A. Verifikasi Final Permohonan (Bundel)
1. Menu **Penyaluran** → lihat antrian permohonan masuk dari SKPKD Kab
2. Klik **Detail Permohonan** → review semua kegiatan, dokumen, dan hasil reviu inspektorat
3. Untuk setiap kegiatan dalam permohonan → klik **Verifikasi** → **Putuskan**:
   - **Disetujui** → kegiatan siap diproses SP2D
   - **Perlu Perbaikan** → dikembalikan ke Kab/Kota
   - **Ditolak** → kegiatan ditolak

### B. Cetak Dokumen Nota Dinas
*Dilakukan setelah semua kegiatan dalam permohonan disetujui.*
1. Di halaman **Detail Permohonan** → klik:
   - **Cetak Nota Kabid** → Nota Dinas dari Kabid Perencanaan Anggaran ke Kepala Badan
   - **Cetak Nota Kepala Badan** → Nota Dinas dari Kepala Badan ke Bendahara Pengeluaran
   - **Cetak Ringkasan** → Rekapitulasi kegiatan dalam permohonan (format lanskap A4)
2. Semua 3 dokumen harus dicetak/dibuka sebelum SP2D dapat diinput

### C. Input SP2D (Surat Perintah Pencairan Dana)
1. Setelah semua nota dicetak → scroll ke bagian **Input SP2D** di Detail Permohonan
2. Isi:
   - **Nomor SP2D**, **Tanggal SP2D**, **Nilai SP2D**
   - **Rekening Asal**, **Bank Asal**
   - **Rekening Tujuan**, **Bank Tujuan** (rekening RKUD Kab/Kota)
   - **Status**: *Proses* (transfer sedang berjalan) atau *Selesai* (dana sudah ditransfer)
3. Klik **Simpan SP2D**
4. Notifikasi otomatis terkirim ke SKPKD Kab melalui Telegram dan in-app

### D. Kelola Master Data (Parameter)
#### Data Tahun Anggaran
- Menu **Parameter** → **Data Tahun** → Tambah tahun baru → Set sebagai Aktif

#### Batas Waktu Pengajuan
- **Parameter** → **Batas Waktu** → atur tanggal batas per jenis penyaluran
- ⚠️ Setelah batas waktu lewat, OPD **tidak dapat** submit (hard block)

#### Data BKP
- **Parameter** → **Data BKP** → Tambah/Edit/Hapus data BKP per Kab/Kota
- Import massal: klik **Import Excel** → upload file CSV/XLSX → Preview → Proses

#### Data Pemda (Pejabat & Dokumen)
- **Parameter** → **Data Umum Pemda** → pilih Kab/Kota dan Tahun
- Input nama Bupati/Walikota, Inspektur, PPKD, Kepala BKAD
- Upload Perda APBD, Perkada BKP yang digunakan sebagai referensi cetak

#### Tampilan Landing Page
- **Parameter** → **Tampilan Landing** → upload foto Gubernur, Wakil Gubernur, Sekda, Kepala BKAD
- Tab **Slideshow** → upload foto kegiatan Pemprovsu untuk ditampilkan di halaman beranda

### E. Kelola Laporan
- **Laporan** → **Rekap BKP**: ringkasan semua BKP dan status per Kab/Kota
- **Laporan** → **Rekap Penyaluran**: daftar SP2D yang sudah diterbitkan
- Tombol **Export CSV** tersedia di setiap laporan

---

## ROLE 5 — SUPERADMIN

**Siapa:** Administrator teknis sistem, memiliki akses penuh ke semua fitur.

*Semua fungsi Admin Provinsi berlaku ditambah:*

### A. Manajemen User
1. Menu **Pengaturan** → **Manajemen User**
2. **Tambah User**: klik **Tambah User** → isi username, nama, email, role, Kab/Kota (untuk role kabkota)
3. **Edit User**: klik ikon pensil → ubah data → simpan
4. **Nonaktifkan User**: klik ikon ✕ → user tidak bisa login (data tetap aman)
5. **Reset Password**: klik ikon kunci → password direset ke default → informasikan ke user

### B. Manajemen Role & Hak Akses
1. Menu **Pengaturan** → **Role & Hak Akses**
2. **Lihat Role**: daftar semua role + jumlah permission + jumlah user
3. **Tambah Role Custom**: klik **Tambah Role** → isi kode, nama, level (3–99)
4. **Atur Permission**: klik **Hak Akses** pada baris role → centang/uncentang permission per modul
5. Role bawaan sistem (superadmin, admin_provinsi, dst) tidak dapat dihapus

---

## ROLE 6 — PENGAWAS

**Siapa:** Pejabat/staf yang memantau seluruh proses tanpa intervensi data.

### Akses yang Tersedia (View-Only)
| Menu | Yang Bisa Dilihat |
|------|-------------------|
| Dashboard | Statistik, funnel, realisasi semua Kab/Kota |
| Pekerjaan | Semua pekerjaan semua Kab/Kota |
| Reviu | Semua antrian dan hasil reviu |
| Verifikasi | Semua antrian verifikasi |
| Penyaluran | Semua data SP2D |
| Capaian | Semua capaian output |
| Laporan | Rekap BKP, Rekap Penyaluran, Laporan Akhir |

> Pengawas **tidak dapat** mengedit, submit, upload, atau mengubah data apapun.

---

## DASHBOARD — SEMUA ROLE

### Statistik Utama
- **Kartu angka** di atas: total BKP, pekerjaan aktif, total disalurkan, dll (berbeda per role)
- **Antrian Aksi**: kotak kuning/biru yang menunjukkan hal yang perlu segera dilakukan

### Funnel Progress
Menampilkan jumlah pekerjaan di setiap tahap alur bisnis.

### Realisasi per Kab/Kota *(hanya Provinsi & Pengawas)*
- Tabel 33 Kab/Kota dengan nilai BKP, nilai disalurkan, progress bar
- Navigasi halaman: gunakan tombol **‹ ›** di bawah tabel (15 baris per halaman)

### Ganti Tahun Anggaran
- Klik dropdown **TA [tahun]** di pojok kanan atas → pilih tahun yang diinginkan

---

## NOTIFIKASI

- Ikon lonceng (🔔) di pojok kanan atas menampilkan notifikasi terbaru
- Notifikasi dikirim otomatis saat:
  - Pekerjaan disubmit / dikembalikan / disetujui
  - Dana disalurkan / dikonfirmasi
  - Capaian diinput

---

## CETAK DOKUMEN

| Dokumen | Cara Cetak | Tersedia untuk |
|---------|-----------|----------------|
| Surat Permohonan Reviu | Detail Pekerjaan → **Cetak Permohonan** | OPD, SKPKD |
| Kertas Kerja Reviu | Form Reviu → **Cetak Kertas Kerja** | Inspektorat |
| Rekap Hasil Reviu | Form Reviu → **Cetak Rekap** | Inspektorat, SKPKD |
| Rekap Verifikasi Kab | Form Verif Kab → **Cetak Rekap** | SKPKD Kab |
| Rekap Penyaluran | Menu Penyaluran → **Cetak Rekap** | Admin Prov |
| Laporan Akhir Kab/Kota | Laporan → Laporan Akhir → **Cetak** | SKPKD, Admin Prov |
| Rekap BKP | Laporan → Rekap BKP → **Cetak** | Semua |

> Semua dokumen cetak sudah ber-KOP dan menyertakan tanda tangan (dari data Pemda yang diinput Admin Provinsi).

---

## TROUBLESHOOTING UMUM

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| Tombol Submit tidak muncul | Batas waktu sudah lewat | Hubungi Admin Provinsi untuk cek batas waktu |
| BKP tidak muncul di dropdown input | BKP sudah punya pekerjaan aktif, atau belum diinput Admin | Hubungi Admin Provinsi |
| Upload dokumen gagal | File terlalu besar atau format salah | Gunakan JPG/PNG/PDF, maks 10 MB |
| Tidak bisa akses menu tertentu | Hak akses role tidak mencukupi | Hubungi Superadmin |
| Data Kab/Kota lain tidak terlihat | Normal — OPD/Inspektorat/SKPKD hanya lihat data sendiri | — |
| Lupa password | — | Hubungi Admin Provinsi / Superadmin untuk reset |
| Logo provinsi hilang setelah update | Server menggunakan container (Railway/Docker) — file tidak persisten | Admin harus upload ulang logo; pastikan Volume sudah terpasang di server |
| Menu Penyaluran tidak muncul (SKPKD Kab) | Permission `penyaluran_kab.view` belum di-assign | Superadmin → Pengaturan → Role → Hak Akses → centang Penyaluran Kab |
| Konfirmasi RKUD tidak bisa dilakukan | SP2D belum diinput oleh Admin Provinsi | Tunggu Admin Provinsi menginput SP2D di menu Penyaluran |
| Export XLSX tidak berfungsi | Browser memblokir download | Cek popup blocker; coba klik kanan → Save As |

---

*Manual Book SIBERKAH SUMUT v4.1.0 — BKAD Provinsi Sumatera Utara — Juni 2026*
