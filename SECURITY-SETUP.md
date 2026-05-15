# Panduan Keamanan Server — FTTH Monitoring

Panduan ini berisi langkah-langkah yang **harus dilakukan manual di server production** setelah deploy. Jalankan semua perintah di direktori project.

---

## 1. Rotate APP_KEY (Enkripsi Laravel)

> **⚠️ PERINGATAN:** APP_KEY digunakan untuk enkripsi password Mikrotik dan OLT di database.
> Jika key diganti tanpa re-encrypt data, **semua halaman yang memuat data Mikrotik/OLT akan 500 error**.
> Ikuti langkah di bawah **secara lengkap** — jangan skip langkah re-encrypt.

APP_KEY digunakan untuk enkripsi session, cookie, dan data sensitif (termasuk password perangkat).

```bash
# 1. Backup .env dulu
cp .env .env.backup-$(date +%Y%m%d)

# 2. Generate key baru (otomatis update .env)
php artisan key:generate

# 3. WAJIB — Re-encrypt semua password perangkat dengan key baru
#    Ganti OLD_KEY dengan APP_KEY dari file backup
php artisan app:reencrypt base64:KEY_LAMA_DARI_BACKUP

# 4. Bersihkan cache
php artisan optimize:clear
```

Cara dapat key lama:
```bash
grep APP_KEY .env.backup-*
```

**Efek samping:** Semua session aktif akan logout. User tinggal login kembali.

---

## 2. Rotate Reverb Secrets (WebSocket)

Reverb secrets digunakan untuk autentikasi koneksi WebSocket. Harus diganti dengan nilai baru yang acak.

```bash
# Generate nilai acak baru
php -r "echo 'APP_ID:  ' . random_int(100000, 999999) . PHP_EOL;"
php -r "echo 'APP_KEY: ' . bin2hex(random_bytes(16)) . PHP_EOL;"
php -r "echo 'SECRET:  ' . bin2hex(random_bytes(16)) . PHP_EOL;"
```

Akan muncul output seperti:
```
APP_ID:  847291
APP_KEY: a3f1c9e7b2d4081956ae7c3f1b0d2e4a
SECRET:  9c1d3a7f2e8b045612de3f7a8c1b0e49
```

Edit `.env` di server, ganti nilai lama:
```
REVERB_APP_ID=847291
REVERB_APP_KEY=a3f1c9e7b2d4081956ae7c3f1b0d2e4a
REVERB_APP_SECRET=9c1d3a7f2e8b045612de3f7a8c1b0e49
```

Lalu restart Reverb:
```bash
# Jika pakai supervisor
sudo supervisorctl restart reverb

# Jika pakai systemd
sudo systemctl restart reverb

# Rebuild frontend agar VITE_REVERB_APP_KEY ikut terupdate
npm run build
```

---

## 3. Aktifkan HTTPS untuk Reverb (WebSocket Secure)

Tanpa HTTPS, data real-time (traffic, alarm, status ONT) dikirim tanpa enkripsi dan bisa disadap.

### Langkah:

**a) Edit `.env` di server:**
```
REVERB_SCHEME=https
REVERB_PORT=443

VITE_REVERB_SCHEME="${REVERB_SCHEME}"
VITE_REVERB_PORT="${REVERB_PORT}"
```

**b) Konfigurasi Nginx sebagai reverse proxy untuk Reverb:**

Tambahkan di konfigurasi Nginx (biasanya di `/etc/nginx/sites-available/ftth`):

```nginx
# WebSocket reverse proxy
location /app {
    proxy_pass             http://127.0.0.1:8080;
    proxy_read_timeout     60;
    proxy_connect_timeout  60;
    proxy_redirect         off;

    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

location /apps {
    proxy_pass             http://127.0.0.1:8080;
    proxy_read_timeout     60;
    proxy_connect_timeout  60;
    proxy_redirect         off;

    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

**c) Update Reverb config agar listen di localhost saja:**

Edit `config/reverb.php` atau `.env`:
```
REVERB_HOST="127.0.0.1"
VITE_REVERB_HOST="ftth.rjjunior.web.id"
```

Dengan setup ini:
- Reverb listen di `127.0.0.1:8080` (HTTP, internal)
- Nginx proxy ke Reverb dengan SSL termination
- Client connect via `wss://ftth.rjjunior.web.id/app` (HTTPS)

**d) Restart semua:**
```bash
npm run build
sudo nginx -t && sudo systemctl reload nginx
sudo supervisorctl restart reverb
```

---

## 4. Cek .env di Git History

File `.env` tidak pernah di-commit ke repository ini (sudah diverifikasi). Tapi untuk jaga-jaga, cara mengecek:

```bash
# Cek apakah .env pernah ada di history
git log --all --diff-filter=A --name-only -- ".env"
```

**Jika hasilnya kosong** — aman, tidak perlu tindakan lanjutan.

**Jika muncul commit hash** — artinya `.env` pernah masuk git. Lakukan:

```bash
# Hapus .env dari seluruh history (PERINGATAN: rewrite history)
git filter-branch --force --index-filter \
  'git rm --cached --ignore-unmatch .env' \
  --prune-empty --tag-name-filter cat -- --all

# Force push (koordinasi dengan tim dulu!)
git push origin --force --all

# Setelah itu WAJIB rotate semua credentials:
# - APP_KEY (langkah 1)
# - REVERB secrets (langkah 2)
# - DB password jika ada
# - API keys pihak ketiga jika ada
```

---

## 5. Hardening Tambahan yang Disarankan

### a) Pastikan .env Production Sudah Benar

Checklist `.env` di server:
```
APP_ENV=production          # WAJIB production
APP_DEBUG=false             # WAJIB false — mencegah stack trace bocor
LOG_LEVEL=warning           # Jangan debug di production
SESSION_ENCRYPT=true        # Enkripsi session di database
SESSION_SECURE_COOKIE=true  # Cookie hanya via HTTPS
REVERB_SCHEME=https         # WebSocket via HTTPS
```

### b) Permission File di Server

```bash
# File .env hanya bisa dibaca oleh web server
chmod 600 .env

# Direktori storage dan cache bisa ditulis
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Database SQLite hanya bisa diakses web server
chmod 660 database/database.sqlite
chown www-data:www-data database/database.sqlite
```

### c) Nginx Security Headers (Opsional, Tambahan)

Aplikasi sudah menambahkan security headers via middleware PHP, tapi untuk performa lebih baik bisa juga ditambah di Nginx:

```nginx
# Di block server {}
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

# Blokir akses ke file sensitif
location ~ /\.(?!well-known) {
    deny all;
}
location ~ \.(env|log|sql|sqlite)$ {
    deny all;
}
```

### d) Firewall

```bash
# Hanya buka port yang diperlukan
sudo ufw allow 22/tcp     # SSH
sudo ufw allow 80/tcp     # HTTP (redirect ke HTTPS)
sudo ufw allow 443/tcp    # HTTPS
sudo ufw enable

# Pastikan port Reverb (8080) TIDAK terbuka ke publik
# karena sudah diproxy via Nginx
sudo ufw deny 8080/tcp
```

### e) Auto-Backup Terjadwal

Tambahkan cron job untuk backup database otomatis:

```bash
# Edit crontab
crontab -e

# Tambahkan baris ini — backup setiap hari jam 02:00
0 2 * * * cd /path/ke/project/ftth && php artisan db:backup >> /dev/null 2>&1

# Opsional: hapus backup lebih dari 30 hari
0 3 * * * find /path/ke/project/ftth/storage/app/backups -name "*.sql" -mtime +30 -delete
```

---

## Checklist Setelah Selesai

- [ ] APP_KEY sudah di-rotate (`php artisan key:generate`)
- [ ] REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET sudah diganti
- [ ] REVERB_SCHEME=https sudah diset
- [ ] Nginx sudah dikonfigurasi untuk proxy WebSocket
- [ ] APP_DEBUG=false
- [ ] SESSION_ENCRYPT=true
- [ ] Permission file .env sudah 600
- [ ] Firewall aktif, port 8080 tertutup dari publik
- [ ] Cron backup database sudah terjadwal
- [ ] `npm run build` sudah dijalankan setelah semua perubahan .env
- [ ] `php artisan optimize:clear` sudah dijalankan
