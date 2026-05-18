# Panduan Deploy ke Server Production Pemda

## Persiapan Server
- PHP 7.4+ / 8.x
- MySQL 5.7+ / MariaDB 10.3+
- Apache 2.4+ dengan `mod_rewrite` aktif

## Langkah Deploy

### 1. Upload File
Upload semua isi folder project ke direktori web server (contoh: `/var/www/html/siberkah/`)

### 2. Konfigurasi (WAJIB)
```bash
# Di server, copy template config
cp application/config/config.production.php application/config/config.php
cp application/config/database.production.php application/config/database.php
```
Edit kedua file tersebut dan isi nilai yang ditandai `[GANTI]`.

### 3. Import Database
```bash
mysql -u root -p siberkah_sumut < database/schema.sql
```

### 4. Permission Folder
```bash
chmod -R 777 application/cache application/logs uploads
chmod -R 755 application/  # semua folder lain
```

### 5. Session Database (jika pakai driver database)
Buat tabel session di MySQL:
```sql
CREATE TABLE IF NOT EXISTS ci_sessions (
  id            VARCHAR(128) NOT NULL,
  ip_address    VARCHAR(45)  NOT NULL,
  timestamp     INT(10) UNSIGNED DEFAULT 0 NOT NULL,
  data          BLOB NOT NULL,
  PRIMARY KEY (id),
  KEY ci_sessions_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 6. Cek Instalasi
Buka URL aplikasi di browser → harus tampil landing page SIBERKAH.

### 7. Buat Akun Superadmin Pertama
Jalankan SQL berikut (ganti password setelah login):
```sql
-- Password default: SiberkahPemda2026 (SEGERA GANTI setelah login pertama!)
INSERT INTO users (username, password, nama, email, role_id, instansi_jenis, is_active, created_at)
VALUES (
  'superadmin',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Super Administrator',
  'admin@sumutprov.go.id',
  1, 'bkad_provinsi', 1, NOW()
);
```
> Password `$2y$10$...` = hash bcrypt untuk kata sandi `password` — **langsung ganti setelah login pertama.**

## Checklist Sebelum Go-Live
- [ ] `base_url` sudah diisi domain yang benar di config.php
- [ ] `encryption_key` sudah diganti dengan key acak baru
- [ ] `cookie_secure = TRUE` (HTTPS sudah aktif)
- [ ] `sess_match_ip = TRUE`
- [ ] Password superadmin sudah diganti dari default
- [ ] Folder uploads sudah writable
- [ ] `db_debug = FALSE`
- [ ] Backup database pertama sudah dibuat
