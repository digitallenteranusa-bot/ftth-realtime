# Panduan Keamanan Server — FTTH Monitoring

Panduan ini untuk **setup keamanan di server production**. Jalankan semua perintah di direktori project (`/var/www/ftth` atau sesuai lokasi install).

---

## Mana yang AMAN dan mana yang BERBAHAYA?

| Langkah | Risiko | Keterangan |
|---------|--------|------------|
| Rotate Reverb Secrets | ✅ Aman | Tidak ada data yang bergantung pada nilai lama |
| Setting .env production | ✅ Aman | Hanya ubah konfigurasi |
| Permission file | ✅ Aman | Hanya ubah hak akses |
| Nginx headers | ✅ Aman | Hanya tambah header |
| Firewall | ✅ Aman | Hanya tutup port |
| HTTPS Reverb | ✅ Aman | Perlu SSL certificate |
| **Rotate APP_KEY** | ⚠️ **BERBAHAYA** | Merusak password Mikrotik & OLT yang terenkripsi |

---

## 1. Setting .env Production (WAJIB)

Pastikan nilai berikut sudah benar di `.env` server:

```
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
SESSION_ENCRYPT=true
```

Jika server sudah punya HTTPS:
```
SESSION_SECURE_COOKIE=true
```

```bash
php artisan optimize:clear
```

---

## 2. Rotate Reverb Secrets (DISARANKAN)

Reverb secrets default tidak aman. Ganti dengan nilai acak.

Generate nilai baru:
```bash
php -r "echo random_int(100000, 999999) . PHP_EOL;"
```

```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

Output pertama = APP_ID, kedua = APP_KEY, ketiga = SECRET.

Edit `.env` dengan `nano .env`, ganti 3 nilai ini:
```
REVERB_APP_ID=nilai_pertama
REVERB_APP_KEY=nilai_kedua
REVERB_APP_SECRET=nilai_ketiga
```

Setelah edit:
```bash
npm run build
```

```bash
php artisan optimize:clear
```

```bash
supervisorctl restart reverb
```

---

## 3. Permission File (DISARANKAN)

```bash
chmod 600 .env
```

```bash
chmod -R 775 storage bootstrap/cache
```

```bash
chown -R www-data:www-data storage bootstrap/cache
```

---

## 4. Firewall (DISARANKAN)

```bash
sudo ufw allow 22/tcp
```

```bash
sudo ufw allow 80/tcp
```

```bash
sudo ufw allow 443/tcp
```

```bash
sudo ufw deny 8080/tcp
```

```bash
sudo ufw enable
```

Port 8080 (Reverb) ditutup dari publik karena sudah diproxy via Nginx.

---

## 5. HTTPS untuk Reverb / WebSocket (OPSIONAL)

Hanya jika server sudah punya SSL certificate (HTTPS).

**a) Edit `.env`:**
```
REVERB_SCHEME=https
REVERB_PORT=443
REVERB_HOST="127.0.0.1"

VITE_REVERB_SCHEME="${REVERB_SCHEME}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_HOST="nama-domain-server-anda"
```

> Ganti `nama-domain-server-anda` dengan domain yang sebenarnya (contoh: `ftth.contoh.com`).

**b) Tambahkan di Nginx** (file `/etc/nginx/sites-available/ftth`):

Buka dengan `nano`, tambahkan di dalam block `server {}`:

```nginx
location /app {
    proxy_pass             http://127.0.0.1:8080;
    proxy_read_timeout     60;
    proxy_connect_timeout  60;
    proxy_redirect         off;
    proxy_http_version     1.1;
    proxy_set_header       Upgrade $http_upgrade;
    proxy_set_header       Connection "upgrade";
    proxy_set_header       Host $host;
    proxy_set_header       X-Real-IP $remote_addr;
    proxy_set_header       X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header       X-Forwarded-Proto $scheme;
}

location /apps {
    proxy_pass             http://127.0.0.1:8080;
    proxy_read_timeout     60;
    proxy_connect_timeout  60;
    proxy_redirect         off;
    proxy_http_version     1.1;
    proxy_set_header       Upgrade $http_upgrade;
    proxy_set_header       Connection "upgrade";
    proxy_set_header       Host $host;
    proxy_set_header       X-Real-IP $remote_addr;
    proxy_set_header       X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header       X-Forwarded-Proto $scheme;
}
```

**c) Test dan reload Nginx:**
```bash
sudo nginx -t
```

```bash
sudo systemctl reload nginx
```

**d) Rebuild dan restart:**
```bash
npm run build
```

```bash
supervisorctl restart reverb
```

---

## 6. Nginx Security Headers (OPSIONAL)

Aplikasi sudah punya security headers via middleware PHP. Ini tambahan di level Nginx.

Buka config Nginx dengan `nano`, tambahkan di dalam block `server {}`:

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

location ~ /\.(?!well-known) {
    deny all;
}
location ~ \.(env|log|sql|sqlite)$ {
    deny all;
}
```

Setelah edit:
```bash
sudo nginx -t
```

```bash
sudo systemctl reload nginx
```

---

## 7. Auto-Backup Database (OPSIONAL)

```bash
crontab -e
```

Tambahkan 2 baris ini (ketik manual, jangan paste):
```
0 2 * * * cd /var/www/ftth && php artisan db:backup >> /dev/null 2>&1
0 3 * * * find /var/www/ftth/storage/app/backups -name "*.sql" -mtime +30 -delete
```

> Sesuaikan `/var/www/ftth` dengan lokasi install di server.

---

## 8. Rotate APP_KEY (HANYA JIKA KEY BOCOR)

> **⚠️ PERINGATAN KERAS:**
> Jangan jalankan `php artisan key:generate` kecuali APP_KEY benar-benar bocor.
> APP_KEY digunakan untuk enkripsi password Mikrotik dan OLT di database.
> Jika key diganti tanpa re-encrypt, **semua halaman akan 500 error**.

**Untuk install baru (belum ada data Mikrotik/OLT):** aman, tidak ada yang rusak.

**Untuk server yang sudah jalan (sudah ada data):** ikuti langkah lengkap di bawah.

### Langkah Rotate (server yang sudah ada data):

```bash
cp .env .env.backup-$(date +%Y%m%d)
```

```bash
php artisan key:generate
```

Lihat key lama dari backup:
```bash
grep APP_KEY .env.backup-*
```

Re-encrypt semua password perangkat (ganti key lama sesuai output di atas):
```bash
php artisan app:reencrypt base64:KEY_LAMA_DARI_BACKUP
```

Pastikan output menunjukkan "OK" untuk semua perangkat. Jika ada "GAGAL", berarti key lama salah.

```bash
php artisan optimize:clear
```

---

## Checklist

### Wajib
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `SESSION_ENCRYPT=true`

### Disarankan
- [ ] Reverb secrets sudah diganti dengan nilai acak
- [ ] Permission `.env` sudah 600
- [ ] Firewall aktif, port 8080 tertutup

### Opsional
- [ ] HTTPS untuk Reverb (butuh SSL certificate)
- [ ] Nginx security headers
- [ ] Cron backup database

### Jangan Dilakukan (kecuali darurat)
- [ ] ~~Rotate APP_KEY~~ — hanya jika key bocor, ikuti panduan lengkap bagian 8
