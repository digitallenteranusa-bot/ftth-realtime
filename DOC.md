# Dokumentasi Server & Troubleshooting

## Server Info

- **Proxmox IP:** 10.196.1.253
- **Akses SSH:** `ssh root@10.196.1.253`
- **Web UI Proxmox:** `https://10.196.1.253:8006`
- **Aplikasi FTTH:** `/var/www/ftth`

---

## 1. Menghilangkan Fail2Ban (SSH Blocked) di Proxmox

### Gejala
- SSH ke server mendapat **"Access denied"** padahal password benar
- Sudah berkali-kali coba login dan tetap ditolak
- Ping ke server reply (server hidup), tapi SSH tidak bisa

### Penyebab
Fail2ban memblokir IP kamu karena terlalu banyak percobaan login gagal.

### Syarat
Harus ada **akses fisik** ke server (monitor + keyboard) karena SSH sudah diblokir.

---

### Cara 1: Unban via Fail2Ban Client (Direkomendasikan)

**Langkah 1** — Colokkan monitor dan keyboard ke server Proxmox

**Langkah 2** — Login langsung di console server
```
Proxmox VE Login: root
Password: (ketik password root, tidak terlihat saat mengetik)
```

**Langkah 3** — Cek apakah fail2ban aktif
```bash
systemctl status fail2ban
```
Output yang diharapkan:
```
● fail2ban.service - Fail2Ban Service
   Active: active (running)
```

**Langkah 4** — Lihat daftar IP yang diblokir
```bash
fail2ban-client status sshd
```
Output contoh:
```
Status for the jail: sshd
|- Filter
|  |- Currently failed: 0
|  `- Total failed:     12
`- Actions
   |- Currently banned: 1
   `- Banned IP list:   10.196.x.x
```

**Langkah 5** — Unban semua IP sekaligus
```bash
fail2ban-client unban --all
```

**Langkah 6** — Verifikasi sudah tidak ada yang diblokir
```bash
fail2ban-client status sshd
```
Pastikan `Currently banned: 0` dan `Banned IP list:` kosong.

**Langkah 7** — Coba SSH dari komputer kamu
```bash
ssh root@10.196.1.253
```

---

### Cara 2: Hapus Database Fail2Ban (Jika Cara 1 Gagal)

**Langkah 1** — Login langsung di console server (monitor + keyboard)

**Langkah 2** — Stop service fail2ban
```bash
systemctl stop fail2ban
```

**Langkah 3** — Cek file database fail2ban ada atau tidak
```bash
ls -la /var/lib/fail2ban/fail2ban.sqlite3
```

**Langkah 4** — Hapus database ban
```bash
rm /var/lib/fail2ban/fail2ban.sqlite3
```

**Langkah 5** — Start ulang fail2ban (database baru akan dibuat otomatis)
```bash
systemctl start fail2ban
```

**Langkah 6** — Pastikan fail2ban berjalan normal
```bash
systemctl status fail2ban
```

**Langkah 7** — Coba SSH dari komputer kamu
```bash
ssh root@10.196.1.253
```

---

### Cara 3: Disable Fail2Ban Sementara (Solusi Cepat)

**Langkah 1** — Login langsung di console server (monitor + keyboard)

**Langkah 2** — Matikan fail2ban
```bash
systemctl stop fail2ban
systemctl disable fail2ban
```

**Langkah 3** — Coba SSH dari komputer kamu
```bash
ssh root@10.196.1.253
```

**Langkah 4** — Setelah berhasil SSH, aktifkan kembali fail2ban
```bash
systemctl enable fail2ban
systemctl start fail2ban
```

---

### Cara 4: Cek & Hapus Iptables Block (Jika Fail2Ban Tidak Ditemukan)

Kadang block ada di iptables langsung, bukan fail2ban.

**Langkah 1** — Login langsung di console server (monitor + keyboard)

**Langkah 2** — Lihat semua rules iptables
```bash
iptables -L -n --line-numbers
```

**Langkah 3** — Cari IP kamu yang diblokir (lihat di chain f2b-sshd atau INPUT)
```bash
iptables -L f2b-sshd -n --line-numbers
```

**Langkah 4** — Hapus rule yang memblokir IP kamu (ganti NOMOR dengan nomor rule)
```bash
iptables -D f2b-sshd NOMOR
```

**Langkah 5** — Atau flush semua block sekaligus (hati-hati, menghapus semua rules)
```bash
iptables -F f2b-sshd
```

**Langkah 6** — Coba SSH dari komputer kamu
```bash
ssh root@10.196.1.253
```

---

### Cara 5: Reset Password Root (Jika Password Benar-Benar Lupa)

**Langkah 1** — Reboot server
```bash
reboot
```
Atau tekan tombol power/reset di server.

**Langkah 2** — Saat muncul GRUB boot menu, tekan tombol `e` untuk edit

**Langkah 3** — Cari baris yang dimulai dengan `linux /boot/vmlinuz...`

**Langkah 4** — Di akhir baris tersebut, tambahkan:
```
init=/bin/bash
```

**Langkah 5** — Tekan `Ctrl + X` atau `F10` untuk boot

**Langkah 6** — Setelah masuk shell, remount filesystem agar bisa write
```bash
mount -o remount,rw /
```

**Langkah 7** — Reset password root
```bash
passwd root
```
```
New password: (ketik password baru)
Retype new password: (ketik ulang password baru)
passwd: password updated successfully
```

**Langkah 8** — Reboot server
```bash
exec /sbin/init
```

**Langkah 9** — Coba SSH dengan password baru
```bash
ssh root@10.196.1.253
```

---

## 2. Pencegahan Agar IP Tidak Terblokir Lagi

### Whitelist IP di Fail2Ban

**Langkah 1** — SSH ke server
```bash
ssh root@10.196.1.253
```

**Langkah 2** — Buat/edit file jail.local
```bash
nano /etc/fail2ban/jail.local
```

**Langkah 3** — Tambahkan isi berikut (ganti IP sesuai IP kantor/rumah kamu)
```ini
[DEFAULT]
ignoreip = 127.0.0.1/8 10.196.1.0/24

[sshd]
enabled = true
port = ssh
filter = sshd
maxretry = 5
bantime = 600
findtime = 600
```

Keterangan:
- `ignoreip` = IP yang tidak akan pernah diblokir (whitelist)
- `10.196.1.0/24` = semua IP di jaringan 10.196.1.x tidak akan diblokir
- `maxretry = 5` = diblokir setelah 5 kali gagal login
- `bantime = 600` = durasi blokir 10 menit (dalam detik)
- `findtime = 600` = hitungan gagal login dalam 10 menit terakhir

**Langkah 4** — Simpan file: tekan `Ctrl + X`, lalu `Y`, lalu `Enter`

**Langkah 5** — Restart fail2ban
```bash
systemctl restart fail2ban
```

**Langkah 6** — Verifikasi konfigurasi aktif
```bash
fail2ban-client get sshd ignoreip
```

---

## 3. Update Aplikasi FTTH di Server

Setelah berhasil SSH ke server, jalankan perintah berikut untuk update aplikasi:

```bash
cd /var/www/ftth
git pull origin main
npm install && npm run build
php artisan optimize:clear
```

---

## 4. Perintah-Perintah Berguna di Proxmox

### Cek Status Service
```bash
systemctl status nginx        # cek web server
systemctl status php8.2-fpm   # cek PHP (sesuaikan versi)
systemctl status mysql         # cek database
systemctl status fail2ban      # cek fail2ban
```

### Restart Service
```bash
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart mysql
```

### Cek Log
```bash
tail -f /var/log/auth.log              # log SSH/login
tail -f /var/log/fail2ban.log          # log fail2ban
tail -f /var/www/ftth/storage/logs/laravel.log  # log aplikasi
```

### Cek Disk & Memory
```bash
df -h          # cek pemakaian disk
free -h        # cek pemakaian RAM
htop           # monitor proses (tekan q untuk keluar)
```

### Cek Koneksi Network
```bash
ip addr show              # lihat semua IP
ping 8.8.8.8              # tes koneksi internet
ss -tlnp                  # lihat port yang listening
```
