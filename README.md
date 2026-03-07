# FTTH Real-Time Monitoring System

Sistem monitoring dan manajemen FTTH (Fiber To The Home) real-time dengan integrasi perangkat jaringan (Mikrotik, OLT multi-vendor, ODC, ODP, ONT) dan peta interaktif dengan animasi garis fiber optik.

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11 + PHP 8.2+ |
| Frontend | Vue 3 + Inertia.js + Vite |
| Map | Leaflet.js + leaflet-polylinedecorator |
| Real-time | Laravel Reverb (WebSocket) |
| Database | MySQL 8 / SQLite |
| CSS | Tailwind CSS |
| Device Comm | RouterOS API, SSH (phpseclib) |

## Fitur

### Dashboard
- Statistik real-time: jumlah customer, perangkat aktif, ONT online/offline/LOS
- Grafik traffic live per Mikrotik (Chart.js)
- Monitoring PPPoE sessions aktif dan open tickets
- Panel alarm terbaru dan trouble tickets

### Peta Interaktif
- Visualisasi semua perangkat jaringan di peta (Mikrotik, OLT, ODC, ODP, ONT)
- Animated fiber lines dengan warna berdasarkan tipe koneksi
- Filter layer per tipe perangkat
- Popup detail saat klik perangkat
- Legend dan status warna (online/offline/LOS)

### Manajemen Perangkat
- **Mikrotik** - Multi-router management via RouterOS API (PPPoE, interface traffic, queue, hotspot, system resources, log)
- **OLT** - Multi-vendor support (ZTE, Huawei, FiberHome) via SSH dengan driver pattern
- **ODC** - Optical Distribution Cabinet dengan kapasitas dan splitter ratio
- **ODP** - Optical Distribution Point terhubung ke ODC
- **ONT** - ONT devices dengan monitoring status, Rx/Tx power, serial number
- **Bandwidth Plan** - CRUD paket bandwidth dengan harga

### Monitoring & Alert
- Polling otomatis Mikrotik (setiap 1 menit) dan OLT (setiap 3 menit)
- Alarm otomatis saat ONT berubah status (offline/LOS)
- Notifikasi via Email dan Telegram Bot
- Broadcast real-time via WebSocket (Laravel Reverb)
- Toast notification di browser untuk alarm dan status ONT
- Trouble ticket system dengan workflow (open -> in_progress -> resolved -> closed)

### Keamanan & Audit
- Role-based access control (admin/operator/viewer)
- Rate limiting pada login (5/menit) dan API (60/menit)
- Audit log otomatis untuk semua perubahan data
- CSRF protection pada semua endpoint

### Export Data
- Export CSV dan PDF untuk Customers, ONTs, dan Alarms

## Struktur Database (16 Tabel)

```
users, customers, mikrotiks, olts, pon_ports, odcs, odps, onts,
fiber_routes, bandwidth_plans, alarms, trouble_tickets,
monitoring_logs, pppoe_sessions, interface_traffics, audit_logs
```

## Default Login

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ftth.local | password |
| Operator | operator@ftth.local | password |

---

## Panduan Instalasi - Ubuntu 22.04 (Fresh Server)

> Panduan ini ditulis untuk **fresh server Ubuntu 22.04** dan dijalankan sebagai **root**.
> Semua perintah sudah diurutkan sesuai dependensi - jalankan dari atas ke bawah.

---

### Step 1 - Update Sistem dan Install Paket Dasar

Paket dasar yang dibutuhkan oleh langkah-langkah selanjutnya:
- `curl` - dibutuhkan oleh NodeSource installer (step 4)
- `git` - dibutuhkan untuk clone repository (step 6)
- `unzip` - dibutuhkan oleh Composer untuk extract package
- `software-properties-common` - dibutuhkan untuk `add-apt-repository` (step 2)

```bash
apt update && apt upgrade -y
apt install -y curl git unzip wget software-properties-common apt-transport-https ca-certificates
```

Verifikasi paket dasar terinstall:

```bash
curl --version | head -1
git --version
```

> Jika salah satu gagal, ulangi perintah `apt install` di atas.

---

### Step 2 - Install PHP 8.2 dan Ekstensi

Tambahkan PPA Ondrej (repository PHP terbaru untuk Ubuntu):

```bash
add-apt-repository ppa:ondrej/php -y
apt update
```

Install PHP 8.2 beserta semua ekstensi yang dibutuhkan Laravel dan project ini:

```bash
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring \
    php8.2-zip php8.2-bcmath php8.2-intl php8.2-readline \
    php8.2-sockets php8.2-gd php8.2-sqlite3
```

Verifikasi PHP terinstall:

```bash
php -v
```

Output yang diharapkan (versi bisa berbeda):

```
PHP 8.2.x (cli) ...
```

Verifikasi ekstensi `sockets` aktif (dibutuhkan untuk RouterOS API):

```bash
php -m | grep sockets
```

Output yang diharapkan:

```
sockets
```

Verifikasi PHP-FPM berjalan:

```bash
systemctl status php8.2-fpm
```

> Jika PHP-FPM belum running: `systemctl start php8.2-fpm && systemctl enable php8.2-fpm`

---

### Step 3 - Install Composer

Download dan install Composer menggunakan PHP (tidak membutuhkan `curl`):

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
```

Verifikasi:

```bash
composer --version
```

Output yang diharapkan:

```
Composer version 2.x.x ...
```

> **Catatan:** Composer akan menampilkan warning saat dijalankan sebagai root. Ini normal dan bisa diabaikan, ketik `yes` jika ditanya.

---

### Step 4 - Install Node.js 20 LTS

Download dan jalankan NodeSource installer (membutuhkan `curl` dari step 1):

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

Verifikasi Node.js dan npm:

```bash
node -v
npm -v
```

Output yang diharapkan:

```
v20.x.x
10.x.x
```

---

### Step 5 - Install dan Konfigurasi MySQL 8

Install MySQL server:

```bash
apt install -y mysql-server
systemctl start mysql
systemctl enable mysql
```

Verifikasi MySQL berjalan:

```bash
systemctl status mysql
```

Buat database dan user. Masuk ke MySQL console:

```bash
mysql
```

> **Catatan:** Di Ubuntu 22.04 fresh install, `mysql` bisa dijalankan langsung sebagai root tanpa password (menggunakan auth_socket). Tidak perlu `mysql -u root -p`.

Jalankan perintah SQL berikut (ganti `password_kuat_anda` dengan password pilihan Anda):

```sql
CREATE DATABASE ftth_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ftth_user'@'localhost' IDENTIFIED BY 'password_kuat_anda';
GRANT ALL PRIVILEGES ON ftth_monitoring.* TO 'ftth_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Verifikasi user bisa login:

```bash
mysql -u ftth_user -p ftth_monitoring -e "SELECT 'OK' AS status;"
```

Masukkan password yang tadi dibuat. Output yang diharapkan:

```
+--------+
| status |
+--------+
| OK     |
+--------+
```

---

### Step 6 - Clone Repository

```bash
cd /var/www
git clone https://github.com/digitallenteranusa-bot/ftth-realtime.git ftth
cd /var/www/ftth
```

Verifikasi file project ada:

```bash
ls /var/www/ftth/artisan
```

Output: `/var/www/ftth/artisan`

> **Alternatif** - upload manual via SCP/SFTP ke `/var/www/ftth/`

---

### Step 7 - Install Dependencies PHP (Composer)

```bash
cd /var/www/ftth
composer install --optimize-autoloader --no-dev
```

> Jika muncul prompt "Do not run Composer as root", ketik `yes`.
> Proses ini membutuhkan waktu 1-3 menit tergantung koneksi internet.

Verifikasi folder `vendor` terisi:

```bash
ls /var/www/ftth/vendor/autoload.php
```

---

### Step 8 - Install Dependencies Node.js (npm)

```bash
cd /var/www/ftth
npm install --legacy-peer-deps
```

> Flag `--legacy-peer-deps` diperlukan untuk menghindari konflik peer dependency.
> Proses ini membutuhkan waktu 1-3 menit.

Verifikasi folder `node_modules` terisi:

```bash
ls /var/www/ftth/node_modules/.package-lock.json
```

---

### Step 9 - Konfigurasi Environment (.env)

Copy template environment:

```bash
cd /var/www/ftth
cp .env.example .env
php artisan key:generate
```

Edit file `.env`:

```bash
nano /var/www/ftth/.env
```

Ubah baris-baris berikut sesuai konfigurasi server Anda:

```env
# === Aplikasi ===
APP_NAME="FTTH Monitoring"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://IP_SERVER_ANDA

# === Database (sesuaikan dengan step 5) ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ftth_monitoring
DB_USERNAME=ftth_user
DB_PASSWORD=password_kuat_anda

# === Broadcasting / WebSocket ===
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=ftth-app
REVERB_APP_KEY=ftth-key-random
REVERB_APP_SECRET=ftth-secret-random
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

# === Vite / Frontend Echo (WAJIB untuk WebSocket di browser) ===
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# === Email (opsional, untuk notifikasi alarm) ===
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.gmail.com
# MAIL_PORT=587
# MAIL_USERNAME=email@gmail.com
# MAIL_PASSWORD=app-password
# MAIL_FROM_ADDRESS=email@gmail.com
# MAIL_FROM_NAME="FTTH Monitoring"

# === Telegram Bot (opsional, untuk notifikasi alarm) ===
# TELEGRAM_BOT_TOKEN=your-bot-token
# TELEGRAM_CHAT_ID=your-chat-id
```

Simpan file: tekan `Ctrl+X`, lalu `Y`, lalu `Enter`.

> **Penting:** Ganti `IP_SERVER_ANDA` dengan IP publik atau domain server Anda.
> Ganti `password_kuat_anda` dengan password MySQL yang dibuat di step 5.

---

### Step 10 - Jalankan Migration dan Seeder

Buat tabel database dan isi data sample:

```bash
cd /var/www/ftth
php artisan migrate --force
php artisan db:seed --force
```

Verifikasi tabel dibuat:

```bash
php artisan migrate:status | head -20
```

Semua migration harus menunjukkan status `Ran`.

---

### Step 11 - Build Frontend untuk Production

```bash
cd /var/www/ftth
npm run build
```

Verifikasi build berhasil:

```bash
ls /var/www/ftth/public/build/manifest.json
```

> Jika file `manifest.json` ada, build berhasil.

---

### Step 12 - Optimasi Laravel untuk Production

```bash
cd /var/www/ftth
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
```

---

### Step 13 - Set Permission File

Set ownership dan permission agar Nginx (www-data) bisa baca/tulis:

```bash
chown -R www-data:www-data /var/www/ftth
chmod -R 755 /var/www/ftth
chmod -R 775 /var/www/ftth/storage /var/www/ftth/bootstrap/cache
```

Buat direktori log jika belum ada:

```bash
mkdir -p /var/www/ftth/storage/logs
chown www-data:www-data /var/www/ftth/storage/logs
```

---

### Step 14 - Install dan Konfigurasi Nginx

Install Nginx:

```bash
apt install -y nginx
```

Buat konfigurasi site (ganti `IP_SERVER_ANDA` dengan IP server):

```bash
cat > /etc/nginx/sites-available/ftth << 'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/ftth/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX
```

Aktifkan site dan restart Nginx:

```bash
ln -sf /etc/nginx/sites-available/ftth /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
```

Output yang diharapkan:

```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

Restart Nginx:

```bash
systemctl restart nginx
systemctl enable nginx
```

---

### Step 15 - Setup Supervisor (Queue Worker + Reverb WebSocket)

Install Supervisor:

```bash
apt install -y supervisor
```

Buat konfigurasi Queue Worker:

```bash
cat > /etc/supervisor/conf.d/ftth-worker.conf << 'EOF'
[program:ftth-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ftth/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ftth/storage/logs/worker.log
stopwaitsecs=3600
EOF
```

Buat konfigurasi Laravel Reverb (WebSocket server):

```bash
cat > /etc/supervisor/conf.d/ftth-reverb.conf << 'EOF'
[program:ftth-reverb]
process_name=%(program_name)s
command=php /var/www/ftth/artisan reverb:start --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ftth/storage/logs/reverb.log
EOF
```

Setup Cron Scheduler:

```bash
crontab -l -u www-data 2>/dev/null; echo "* * * * * cd /var/www/ftth && php artisan schedule:run >> /dev/null 2>&1" | crontab -u www-data -
```

Verifikasi cron terpasang:

```bash
crontab -l -u www-data
```

Output yang diharapkan:

```
* * * * * cd /var/www/ftth && php artisan schedule:run >> /dev/null 2>&1
```

Jalankan Supervisor:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start all
```

Verifikasi semua proses berjalan:

```bash
supervisorctl status
```

Output yang diharapkan:

```
ftth-reverb                      RUNNING   pid xxxxx, uptime 0:00:xx
ftth-worker:ftth-worker_00       RUNNING   pid xxxxx, uptime 0:00:xx
ftth-worker:ftth-worker_01       RUNNING   pid xxxxx, uptime 0:00:xx
```

> Jika ada yang `FATAL`, cek log: `tail -20 /var/www/ftth/storage/logs/worker.log`

---

### Step 16 - Setup Firewall (Opsional tapi Disarankan)

```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 8080/tcp
ufw --force enable
```

Verifikasi:

```bash
ufw status
```

---

## Verifikasi Instalasi

### 1. Cek Semua Service Berjalan

Jalankan semua pengecekan sekaligus:

```bash
echo "=== Nginx ===" && systemctl is-active nginx
echo "=== PHP-FPM ===" && systemctl is-active php8.2-fpm
echo "=== MySQL ===" && systemctl is-active mysql
echo "=== Supervisor ===" && supervisorctl status
echo "=== Cron ===" && crontab -l -u www-data 2>/dev/null | head -1
```

Semua service harus menunjukkan `active` atau `RUNNING`.

### 2. Akses Web

Buka browser dan akses:

```
http://IP_SERVER_ANDA
```

Login dengan:

```
Email    : admin@ftth.local
Password : password
```

### 3. Checklist Fungsional

- [ ] Login berhasil, redirect ke Dashboard
- [ ] Dashboard menampilkan statistik (customers, ONT online/offline, dll)
- [ ] Grafik traffic muncul di Dashboard
- [ ] Halaman Map menampilkan peta dengan marker perangkat
- [ ] Animated fiber lines terlihat di peta
- [ ] Klik perangkat di peta menampilkan popup detail
- [ ] CRUD Mikrotik/OLT/ODC/ODP/ONT/Customer berfungsi
- [ ] Halaman Bandwidth Plans menampilkan daftar paket
- [ ] Halaman Alarms menampilkan daftar alarm
- [ ] Export CSV/PDF berfungsi di halaman Customers dan ONT
- [ ] Create dan manage Trouble Tickets berfungsi
- [ ] Toast notification muncul saat ada flash message

---

## Perintah Berguna

### Development (Lokal)

```bash
cd /var/www/ftth

# Jalankan dev server
php artisan serve --host=0.0.0.0 --port=8000

# Jalankan Vite dev server (terminal terpisah)
npm run dev

# Jalankan WebSocket server (terminal terpisah)
php artisan reverb:start

# Jalankan queue worker (terminal terpisah)
php artisan queue:work

# Jalankan scheduler manual (terminal terpisah)
php artisan schedule:work
```

### Maintenance

```bash
cd /var/www/ftth

# Clear semua cache
php artisan optimize:clear

# Rebuild cache production
php artisan optimize

# Fresh migration + seed (HAPUS SEMUA DATA!)
php artisan migrate:fresh --seed --force

# Cek log error
tail -f /var/www/ftth/storage/logs/laravel.log

# Restart queue worker
supervisorctl restart ftth-worker:*

# Restart Reverb WebSocket
supervisorctl restart ftth-reverb

# Restart semua service
supervisorctl restart all
```

### Deploy Update

```bash
cd /var/www/ftth
git pull origin main
composer install --optimize-autoloader --no-dev
npm install --legacy-peer-deps
npm run build
php artisan migrate --force
php artisan optimize
supervisorctl restart all
```

---

## Troubleshooting

### Error "Permission denied" pada storage/

```bash
chown -R www-data:www-data /var/www/ftth/storage /var/www/ftth/bootstrap/cache
chmod -R 775 /var/www/ftth/storage /var/www/ftth/bootstrap/cache
```

### Error "Class not found" setelah deploy

```bash
cd /var/www/ftth
composer dump-autoload
php artisan optimize:clear
```

### MySQL connection refused

```bash
systemctl start mysql
systemctl status mysql
# Cek kredensial di .env
cat /var/www/ftth/.env | grep DB_
```

### Halaman blank / error 500

```bash
# Cek log Laravel
tail -20 /var/www/ftth/storage/logs/laravel.log

# Cek log Nginx
tail -20 /var/log/nginx/error.log

# Pastikan .env sudah benar
cd /var/www/ftth && php artisan config:clear && php artisan config:cache
```

### WebSocket tidak konek

```bash
# Cek Reverb berjalan
supervisorctl status ftth-reverb

# Cek log Reverb
tail -20 /var/www/ftth/storage/logs/reverb.log

# Pastikan port 8080 terbuka
ufw allow 8080/tcp

# Test koneksi WebSocket
curl -s http://localhost:8080
```

### npm run build gagal

```bash
# Hapus node_modules dan install ulang
cd /var/www/ftth
rm -rf node_modules package-lock.json
npm install --legacy-peer-deps
npm run build
```

### Supervisor FATAL

```bash
# Cek log error
tail -30 /var/www/ftth/storage/logs/worker.log
tail -30 /var/www/ftth/storage/logs/reverb.log

# Pastikan permission benar
chown -R www-data:www-data /var/www/ftth/storage

# Restart supervisor
supervisorctl reread
supervisorctl update
supervisorctl restart all
```

### ONT polling tidak jalan

```bash
# Cek cron scheduler terpasang
crontab -l -u www-data

# Cek queue worker berjalan
supervisorctl status ftth-worker:*

# Cek log worker
tail -f /var/www/ftth/storage/logs/worker.log

# Test jalankan scheduler manual
cd /var/www/ftth && php artisan schedule:run
```

---

## Lisensi

Agus Setyono - Trenggalek - All rights reserved.
