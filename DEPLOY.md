# Panduan Deploy & Update — FTTH Monitoring

Dokumentasi untuk deploy, update, dan troubleshoot server production.

---

## Informasi Server

| Item | Nilai |
|------|-------|
| Server | `root@ftth` |
| Path project | `/var/www/ftth` |
| OS | Ubuntu 22.04 |
| Web server | Nginx |
| Database | MySQL (`ftth_monitoring`) |
| Process manager | Supervisor |
| WebSocket | Laravel Reverb (port 8080, via Supervisor) |
| Queue worker | Supervisor (`ftth-worker`) |

### Perbedaan Lokal vs Server

| Setting | Lokal (development) | Server (production) |
|---------|-------------------|-------------------|
| `DB_CONNECTION` | `sqlite` | `mysql` |
| `APP_DEBUG` | `true` | `false` |
| `APP_ENV` | `local` | `production` |
| `SESSION_ENCRYPT` | bisa false | `true` |
| `LOG_LEVEL` | `debug` | `warning` |

> **Penting:** Jangan pernah push `.env` lokal ke server. Konfigurasi berbeda.

---

## Update Rutin (Setelah Push Code)

SSH ke server lalu jalankan:

```bash
cd /var/www/ftth
git pull
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan optimize:clear
supervisorctl restart reverb
supervisorctl restart ftth-worker:*
```

### Jika Hanya Perubahan Frontend (Vue/CSS/JS)

```bash
cd /var/www/ftth
git pull
npm run build
```

### Jika Hanya Perubahan Backend (PHP)

```bash
cd /var/www/ftth
git pull
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
```

### Jika Ada Migration Baru

```bash
cd /var/www/ftth
git pull
php artisan migrate --force
php artisan optimize:clear
```

---

## Supervisor

Supervisor menjalankan 2 proses:
- **reverb** — WebSocket server untuk realtime monitoring
- **ftth-worker** — Queue worker untuk job background

### Perintah Dasar

```bash
# Cek status semua proses
supervisorctl status

# Restart satu proses
supervisorctl restart reverb
supervisorctl restart ftth-worker:*

# Stop / start
supervisorctl stop reverb
supervisorctl start reverb

# Setelah edit file config supervisor
supervisorctl reread
supervisorctl update
```

### Lokasi Config

```
/etc/supervisor/conf.d/reverb.conf        # WebSocket server
/etc/supervisor/conf.d/ftth-worker.conf   # Queue worker
```

### Lokasi Log

```
/var/www/ftth/storage/logs/reverb.log     # Log Reverb
/var/www/ftth/storage/logs/laravel.log    # Log aplikasi
```

---

## Troubleshoot

### Reverb: "Address already in use" (Port 8080)

**Penyebab paling umum:** Ada 2 config supervisor yang menjalankan Reverb.

```bash
# 1. Cek config duplikat
grep -r "reverb" /etc/supervisor/conf.d/

# Jika ada lebih dari 1 file, hapus yang lama:
rm /etc/supervisor/conf.d/[nama-file-lama].conf
supervisorctl reread
supervisorctl update
```

Jika bukan duplikat, cek proses yang menempati port:

```bash
# 2. Cek siapa yang pakai port 8080
lsof -i :8080

# 3. Lacak parent process (siapa yang me-respawn)
ps -o ppid,pid,cmd -p <PID_DARI_LSOF>
ps -o pid,cmd -p <PPID_DARI_LANGKAH_ATAS>
```

> **Jangan langsung `pkill`** — kalau Supervisor masih aktif, proses akan langsung respawn. Harus stop/remove dari Supervisor dulu.

```bash
# 4. Stop dari supervisor, baru kill manual jika perlu
supervisorctl stop reverb
pkill -9 -f "reverb:start"

# 5. Start ulang
supervisorctl start reverb
supervisorctl status reverb
```

### Reverb: Database Error (Table Not Found)

Jika log menunjukkan `Table 'cache' doesn't exist`:

```bash
php artisan migrate --force
php artisan optimize:clear
supervisorctl restart reverb
```

### Reverb: Config Supervisor Rusak

Baris `command=` di config supervisor **harus satu baris utuh**. Jika terpecah menjadi 2 baris, Reverb akan crash.

**Benar:**
```
command=php /var/www/ftth/artisan reverb:start --host=127.0.0.1 --port=8080
```

**Salah:**
```
command=php /var/www/ftth/artisan reverb:start
--host=127.0.0.1 --port=8080
```

Untuk edit, gunakan `nano` dan ketik manual:

```bash
nano /etc/supervisor/conf.d/reverb.conf
```

Setelah edit:

```bash
supervisorctl reread
supervisorctl update
supervisorctl restart reverb
```

### Halaman Blank / Error 500

```bash
# Cek log error
tail -50 /var/www/ftth/storage/logs/laravel.log

# Biasanya masalah cache atau permission
php artisan optimize:clear
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Permission Error pada Database SQLite (Jika Digunakan)

```bash
chmod 660 database/database.sqlite
chown www-data:www-data database/database.sqlite
```

### Queue Job Tidak Berjalan

```bash
# Cek status worker
supervisorctl status ftth-worker:*

# Restart worker
supervisorctl restart ftth-worker:*

# Cek log
tail -30 /var/www/ftth/storage/logs/laravel.log
```

---

## Backup & Restore Database

### Via Web (Admin Only)

Buka halaman **Backup** di navigasi (hanya terlihat oleh admin). Fitur:
- Buat backup baru
- Download backup ke komputer
- Upload backup dari komputer
- Restore dari backup (otomatis membuat backup sebelum restore)

### Via Terminal

```bash
# Buat backup
cd /var/www/ftth
php artisan db:backup

# Restore dari file
php artisan db:restore nama-file.sql --force

# Lokasi file backup
ls -la storage/app/backups/
```

### Auto-Backup (Cron)

```bash
crontab -e

# Tambahkan:
0 2 * * * cd /var/www/ftth && php artisan db:backup >> /dev/null 2>&1
0 3 * * * find /var/www/ftth/storage/app/backups -name "*.sql" -mtime +30 -delete
```

---

## Nginx

### Lokasi Config

```
/etc/nginx/sites-available/ftth
/etc/nginx/sites-enabled/ftth
```

### Test & Reload Config

```bash
nginx -t
systemctl reload nginx
```

### WebSocket Proxy (untuk Reverb via HTTPS)

Tambahkan di block `server` Nginx:

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

---

## Firewall

```bash
# Cek status
ufw status

# Setup minimal
ufw allow 22/tcp     # SSH
ufw allow 80/tcp     # HTTP
ufw allow 443/tcp    # HTTPS
ufw deny 8080/tcp    # Tutup Reverb dari publik (diproxy Nginx)
ufw enable
```
