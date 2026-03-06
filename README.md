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
- **OLT** - Multi-vendor support (ZTE, Huawei, FiberHome) via SSH/Telnet dengan driver pattern
- **ODC** - Optical Distribution Cabinet dengan kapasitas dan splitter ratio
- **ODP** - Optical Distribution Point terhubung ke ODC
- **ONT** - ONT devices dengan monitoring status, Rx/Tx power, serial number

### Monitoring & Alert
- Polling otomatis Mikrotik (setiap 1 menit) dan OLT (setiap 3 menit)
- Alarm otomatis saat ONT berubah status (offline/LOS)
- Broadcast real-time via WebSocket (Laravel Reverb)
- Trouble ticket system dengan workflow (open -> in_progress -> resolved -> closed)

### Manajemen Pelanggan
- CRUD pelanggan dengan data lengkap (NIK, alamat, koordinat)
- Relasi langsung ke ONT dan trouble tickets

## Struktur Database (15 Tabel)

```
users, customers, mikrotiks, olts, pon_ports, odcs, odps, onts,
fiber_routes, bandwidth_plans, alarms, trouble_tickets,
monitoring_logs, pppoe_sessions, interface_traffics
```

## Default Login

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ftth.local | password |
| Operator | operator@ftth.local | password |

---

## Panduan Instalasi - Ubuntu 22.04

### 1. Update Sistem

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install PHP 8.2 dan Ekstensi

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring \
    php8.2-zip php8.2-bcmath php8.2-intl php8.2-readline \
    php8.2-sockets php8.2-gd php8.2-sqlite3
```

Verifikasi:

```bash
php -v
# PHP 8.2.x
```

### 3. Install Composer

```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Verifikasi:

```bash
composer --version
```

### 4. Install Node.js 20 LTS dan npm

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Verifikasi:

```bash
node -v
# v20.x.x
npm -v
```

### 5. Install MySQL 8

```bash
sudo apt install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql
```

Amankan instalasi MySQL:

```bash
sudo mysql_secure_installation
```

Buat database dan user:

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE ftth_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ftth_user'@'localhost' IDENTIFIED BY 'password_kuat_anda';
GRANT ALL PRIVILEGES ON ftth_monitoring.* TO 'ftth_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 6. Install Git

```bash
sudo apt install -y git
```

### 7. Clone / Upload Project

**Opsi A - Clone dari Git:**

```bash
cd /var/www
sudo git clone <URL_REPOSITORY_ANDA> ftth
sudo chown -R $USER:www-data /var/www/ftth
cd /var/www/ftth
```

**Opsi B - Upload manual:**

```bash
sudo mkdir -p /var/www/ftth
sudo chown -R $USER:www-data /var/www/ftth
# Upload file project ke /var/www/ftth/ via SCP/SFTP
cd /var/www/ftth
```

### 8. Install Dependencies PHP

```bash
cd /var/www/ftth
composer install --optimize-autoloader --no-dev
```

### 9. Install Dependencies Node.js

```bash
cd /var/www/ftth
npm install
```

### 10. Konfigurasi Environment

```bash
cd /var/www/ftth
cp .env.example .env
php artisan key:generate
```

Edit file `.env`:

```bash
nano /var/www/ftth/.env
```

Ubah konfigurasi berikut:

```env
APP_NAME="FTTH Monitoring"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://IP_SERVER_ANDA

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ftth_monitoring
DB_USERNAME=ftth_user
DB_PASSWORD=password_kuat_anda

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=ftth-app
REVERB_APP_KEY=ftth-key-random
REVERB_APP_SECRET=ftth-secret-random
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
```

Simpan file (`Ctrl+X`, `Y`, `Enter`).

### 11. Jalankan Migration dan Seeder

```bash
cd /var/www/ftth
php artisan migrate --force
php artisan db:seed --force
```

### 12. Build Frontend untuk Production

```bash
cd /var/www/ftth
npm run build
```

### 13. Optimasi Laravel untuk Production

```bash
cd /var/www/ftth
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
```

### 14. Set Permission

```bash
cd /var/www/ftth
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 15. Install dan Konfigurasi Nginx

```bash
sudo apt install -y nginx
```

Buat konfigurasi site:

```bash
sudo nano /etc/nginx/sites-available/ftth
```

Isi dengan:

```nginx
server {
    listen 80;
    server_name IP_SERVER_ANDA;
    root /var/www/ftth/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

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
```

Aktifkan site:

```bash
sudo ln -s /etc/nginx/sites-available/ftth /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

### 16. Setup Supervisor untuk Queue Worker dan Reverb

Install Supervisor:

```bash
sudo apt install -y supervisor
```

**Queue Worker:**

```bash
sudo nano /etc/supervisor/conf.d/ftth-worker.conf
```

```ini
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
```

**Laravel Reverb (WebSocket):**

```bash
sudo nano /etc/supervisor/conf.d/ftth-reverb.conf
```

```ini
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
```

**Scheduler (Cron):**

```bash
sudo crontab -e -u www-data
```

Tambahkan baris:

```cron
* * * * * cd /var/www/ftth && php artisan schedule:run >> /dev/null 2>&1
```

Jalankan Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

Verifikasi semua proses berjalan:

```bash
sudo supervisorctl status
```

Output yang diharapkan:

```
ftth-reverb                      RUNNING   pid 12345, uptime 0:00:05
ftth-worker:ftth-worker_00       RUNNING   pid 12346, uptime 0:00:05
ftth-worker:ftth-worker_01       RUNNING   pid 12347, uptime 0:00:05
```

### 17. Setup Firewall (Opsional)

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8080/tcp
sudo ufw enable
```

---

## Verifikasi Instalasi

### 1. Cek Semua Service Berjalan

```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo supervisorctl status
```

### 2. Akses Web

Buka browser dan akses:

```
http://IP_SERVER_ANDA
```

Login dengan:
- Email: `admin@ftth.local`
- Password: `password`

### 3. Checklist Fungsional

- [ ] Login berhasil, redirect ke Dashboard
- [ ] Dashboard menampilkan statistik (customers, ONT online/offline, dll)
- [ ] Halaman Map menampilkan peta dengan marker perangkat
- [ ] Animated fiber lines terlihat di peta
- [ ] Klik perangkat di peta menampilkan popup detail
- [ ] CRUD Mikrotik/OLT/ODC/ODP/ONT/Customer berfungsi
- [ ] Halaman Alarms menampilkan daftar alarm
- [ ] Create dan manage Trouble Tickets berfungsi

---

## Perintah Berguna

### Development

```bash
# Jalankan dev server (development only)
cd /var/www/ftth
php artisan serve --host=0.0.0.0 --port=8000
npm run dev

# Jalankan WebSocket server
php artisan reverb:start

# Jalankan queue worker
php artisan queue:work

# Jalankan scheduler manual
php artisan schedule:work
```

### Maintenance

```bash
# Clear semua cache
cd /var/www/ftth
php artisan optimize:clear

# Rebuild cache production
php artisan optimize

# Fresh migration + seed (HAPUS SEMUA DATA)
php artisan migrate:fresh --seed --force

# Cek log error
tail -f /var/www/ftth/storage/logs/laravel.log

# Restart queue worker setelah deploy
sudo supervisorctl restart ftth-worker:*

# Restart Reverb setelah deploy
sudo supervisorctl restart ftth-reverb
```

### Deploy Update

```bash
cd /var/www/ftth
git pull origin main
composer install --optimize-autoloader --no-dev
npm install
npm run build
php artisan migrate --force
php artisan optimize
sudo supervisorctl restart all
```

---

## Troubleshooting

### Error "Permission denied" pada storage/

```bash
sudo chown -R $USER:www-data /var/www/ftth/storage /var/www/ftth/bootstrap/cache
sudo chmod -R 775 /var/www/ftth/storage /var/www/ftth/bootstrap/cache
```

### Error "Class not found" setelah deploy

```bash
cd /var/www/ftth
composer dump-autoload
php artisan optimize:clear
```

### MySQL connection refused

```bash
sudo systemctl start mysql
sudo systemctl status mysql
# Cek kredensial di .env
```

### WebSocket tidak konek

```bash
# Cek Reverb berjalan
sudo supervisorctl status ftth-reverb
# Cek log
tail -f /var/www/ftth/storage/logs/reverb.log
# Pastikan port 8080 terbuka
sudo ufw allow 8080/tcp
```

### ONT polling tidak jalan

```bash
# Cek cron scheduler
crontab -l -u www-data
# Cek queue worker
sudo supervisorctl status ftth-worker:*
# Cek log worker
tail -f /var/www/ftth/storage/logs/worker.log
```

---

## Lisensi

Agus Setono - Trenggalek - All rights reserved.
