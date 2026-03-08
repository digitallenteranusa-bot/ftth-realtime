# FTTH Real-Time Monitoring System - Checklist Improvement

## Status: 21/23 Selesai

---

### ✅ #1 - Middleware Role-Based Access Control

| Item | File | Status |
|------|------|--------|
| RoleMiddleware | `app/Http/Middleware/RoleMiddleware.php` | ✅ |
| Alias di bootstrap | `bootstrap/app.php` | ✅ |
| Route group viewer (read-only) | `routes/web.php` - `['auth', 'verified']` | ✅ |
| Route group operator (CUD) | `routes/web.php` - `['auth', 'verified', 'role:admin,operator']` | ✅ |
| Route group admin (delete) | `routes/web.php` - `['auth', 'verified', 'role:admin']` | ✅ |
| Shared userRole ke frontend | `app/Http/Middleware/HandleInertiaRequests.php` | ✅ |

**Catatan:** Role yang tersedia: `admin`, `operator`, `viewer`. Viewer hanya bisa melihat data. Operator bisa create/edit. Admin bisa delete.

---

### ✅ #2 - CSRF Protection pada Map Endpoints

| Item | File | Status |
|------|------|--------|
| Data map via Inertia props | `app/Http/Controllers/MapController.php` | ✅ |
| Map/Index.vue pakai props | `resources/js/Pages/Map/Index.vue` | ✅ |

**Catatan:** Sebelumnya map menggunakan fetch() ke API endpoint tanpa CSRF. Sekarang data dikirim langsung sebagai Inertia props sehingga otomatis terproteksi CSRF.

---

### ✅ #3 - Rate Limiting

| Item | File | Status |
|------|------|--------|
| Login rate limiter (5/menit) | `app/Providers/AppServiceProvider.php` | ✅ |
| API rate limiter (60/menit) | `app/Providers/AppServiceProvider.php` | ✅ |
| Throttle di route login | `routes/auth.php` - `throttle:login` | ✅ |

**Catatan:** Login dibatasi 5 percobaan per menit per IP. API dibatasi 60 request per menit per user.

---

### ✅ #4 - Audit Log

| Item | File | Status |
|------|------|--------|
| Migration audit_logs | `database/migrations/..._create_audit_logs_table.php` | ✅ |
| Model AuditLog | `app/Models/AuditLog.php` | ✅ |
| Trait Auditable | `app/Traits/Auditable.php` | ✅ |
| Trait di model Mikrotik | `app/Models/Mikrotik.php` | ✅ |
| Trait di model Olt | `app/Models/Olt.php` | ✅ |
| Trait di model Odc | `app/Models/Odc.php` | ✅ |
| Trait di model Odp | `app/Models/Odp.php` | ✅ |
| Trait di model Ont | `app/Models/Ont.php` | ✅ |
| Trait di model Customer | `app/Models/Customer.php` | ✅ |
| Trait di model FiberRoute | `app/Models/FiberRoute.php` | ✅ |
| Trait di model BandwidthPlan | `app/Models/BandwidthPlan.php` | ✅ |
| Trait di model TroubleTicket | `app/Models/TroubleTicket.php` | ✅ |
| Trait di model Alarm | `app/Models/Alarm.php` | ✅ |
| Trait di model PonPort | `app/Models/PonPort.php` | ✅ |

**Catatan:** Setiap create/update/delete pada model yang menggunakan trait Auditable akan dicatat di tabel `audit_logs`. Field sensitif (password, api_password) di-mask otomatis.

Kolom audit_logs: `user_id`, `action`, `model_type`, `model_id`, `old_values` (json), `new_values` (json), `ip_address`.

---

### ✅ #5 - Echo/WebSocket di Frontend

| Item | File | Status |
|------|------|--------|
| Laravel Echo + Reverb config | `resources/js/bootstrap.js` | ✅ |
| Composable useNetworkMonitoring | `resources/js/Composables/useNetworkMonitoring.js` | ✅ |
| Composable useTrafficMonitoring | `resources/js/Composables/useNetworkMonitoring.js` | ✅ |
| Toast component global | `resources/js/Components/Toast.vue` | ✅ |
| Toast listen AlarmTriggered | `resources/js/Components/Toast.vue` | ✅ |
| Toast listen OntStatusChanged | `resources/js/Components/Toast.vue` | ✅ |
| Toast di layout | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |
| Toast registered globally | `resources/js/app.js` | ✅ |
| Shared flash messages | `app/Http/Middleware/HandleInertiaRequests.php` | ✅ |

**Catatan:** WebSocket menggunakan Laravel Reverb. Frontend listen event melalui Laravel Echo. Toast notification muncul otomatis untuk alarm baru dan perubahan status ONT. Flash message dari backend (success/error) juga tampil sebagai toast.

Event yang di-broadcast:
- `AlarmTriggered` - channel `network-monitoring`
- `OntStatusChanged` - channel `network-monitoring`
- `TrafficUpdated` - channel `traffic.{mikrotikId}`
- `DeviceStatusChanged` - channel `network-monitoring`

---

### ✅ #6 - Grafik Traffic (Chart.js)

| Item | File | Status |
|------|------|--------|
| Package chart.js | `package.json` | ✅ |
| Package vue-chartjs | `package.json` | ✅ |
| TrafficChart component | `resources/js/Components/TrafficChart.vue` | ✅ |
| Integrasi di Dashboard | `resources/js/Pages/Dashboard.vue` | ✅ |
| Integrasi di Mikrotik/Show | `resources/js/Pages/Mikrotik/Show.vue` | ✅ |
| Data mikrotiks di controller | `app/Http/Controllers/DashboardController.php` | ✅ |

**Catatan:** TrafficChart menampilkan Line chart realtime dengan:
- Polling setiap 5 detik ke `/mikrotiks/{id}/resources`
- Listen WebSocket event `TrafficUpdated`
- Grafik RX (Download) hijau dan TX (Upload) biru
- Rolling window 30 data point
- Format bytes otomatis (B, KB, MB, GB)
- Dashboard menampilkan chart per Mikrotik aktif

---

### ✅ #7 - Export Data CSV/PDF

| Item | File | Status |
|------|------|--------|
| ExportController | `app/Http/Controllers/ExportController.php` | ✅ |
| Customers CSV export | `ExportController::customersCsv()` | ✅ |
| Customers PDF export | `ExportController::customersPdf()` | ✅ |
| ONTs CSV export | `ExportController::ontsCsv()` | ✅ |
| ONTs PDF export | `ExportController::ontsPdf()` | ✅ |
| Alarms CSV export | `ExportController::alarmsCsv()` | ✅ |
| Blade template PDF | `resources/views/exports/table.blade.php` | ✅ |
| Routes export | `routes/web.php` | ✅ |
| Tombol export di Customer | `resources/js/Pages/Customer/Index.vue` | ✅ |
| Tombol export di ONT | `resources/js/Pages/Ont/Index.vue` | ✅ |
| Tombol export di Alarm | `resources/js/Pages/Alarm/Index.vue` | ✅ |

**Catatan:**
- CSV menggunakan `StreamedResponse` dengan `fputcsv` - langsung download file
- PDF menggunakan Blade view HTML dengan CSS print-friendly dan auto `window.print()`
- Tidak memerlukan library PDF tambahan (wkhtmltopdf, dompdf, dll)

Routes:
- `GET /export/customers/csv` - export.customers.csv
- `GET /export/customers/pdf` - export.customers.pdf
- `GET /export/onts/csv` - export.onts.csv
- `GET /export/onts/pdf` - export.onts.pdf
- `GET /export/alarms/csv` - export.alarms.csv

---

### ✅ #8 - Notification System (Email/Telegram)

| Item | File | Status |
|------|------|--------|
| AlarmNotification | `app/Notifications/AlarmNotification.php` | ✅ |
| OntOfflineNotification | `app/Notifications/OntOfflineNotification.php` | ✅ |
| TelegramChannel | `app/Channels/TelegramChannel.php` | ✅ |
| SendAlarmNotification listener | `app/Listeners/SendAlarmNotification.php` | ✅ |
| SendOntOfflineNotification listener | `app/Listeners/SendOntOfflineNotification.php` | ✅ |
| Event listeners registered | `app/Providers/AppServiceProvider.php` | ✅ |
| Telegram config | `config/services.php` | ✅ |

**Catatan:**
- `AlarmNotification` dikirim via Email + Telegram untuk alarm severity `critical` dan `major`
- `OntOfflineNotification` dikirim via Email saat ONT status berubah ke `offline` atau `los`
- Telegram menggunakan Bot API (`https://api.telegram.org/bot{token}/sendMessage`)
- Kedua listener implements `ShouldQueue` (async via queue)
- Notifikasi dikirim ke semua user dengan role `admin`

Konfigurasi `.env` yang diperlukan:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=email@gmail.com
MAIL_PASSWORD=app-password
MAIL_FROM_ADDRESS=email@gmail.com

TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-chat-id
```

---

### ✅ #9 - ONT Registration/Deregistration dari OLT

| Item | File | Status |
|------|------|--------|
| OltDriverInterface register/deregister | `app/Services/Olt/OltDriverInterface.php` | ✅ |
| ZTE registerOnt | `app/Services/Olt/ZteOltDriver.php` | ✅ |
| ZTE deregisterOnt | `app/Services/Olt/ZteOltDriver.php` | ✅ |
| Huawei registerOnt | `app/Services/Olt/HuaweiOltDriver.php` | ✅ |
| Huawei deregisterOnt | `app/Services/Olt/HuaweiOltDriver.php` | ✅ |
| FiberHome registerOnt | `app/Services/Olt/FiberHomeOltDriver.php` | ✅ |
| FiberHome deregisterOnt | `app/Services/Olt/FiberHomeOltDriver.php` | ✅ |
| OltController registerOnt | `app/Http/Controllers/OltController.php` | ✅ |
| OltController deregisterOnt | `app/Http/Controllers/OltController.php` | ✅ |
| Route register-ont | `routes/web.php` | ✅ |
| Route deregister-ont | `routes/web.php` | ✅ |

**Catatan:**
- Register ONT: validasi input -> connect OLT via SSH -> kirim command register -> simpan record Ont ke database
- Deregister ONT: connect OLT via SSH -> kirim command deregister -> hapus record Ont dari database
- PonPort otomatis dibuat jika belum ada (`firstOrCreate`)
- Hanya role `admin` dan `operator` yang bisa register/deregister

Routes:
- `POST /olts/{olt}/register-ont` - parameter: slot, port, ont_id, serial_number, line_profile, service_profile, name, customer_id, odp_id
- `POST /olts/{olt}/deregister-ont` - parameter: slot, port, ont_id

Command per vendor:
- **ZTE**: `show gpon onu uncfg`, `onu {id} type {profile} sn {sn}`, `no onu {id}`
- **Huawei**: `display ont autofind all`, `ont add {port} {id} sn-auth {sn}`, `ont delete {port} {id}`
- **FiberHome**: `show onu unauth`, `set whitelist phy_addr address {sn}`, `set whitelist action delete`

---

### ✅ #10 - Bandwidth Plan Integration + CRUD

| Item | File | Status |
|------|------|--------|
| BandwidthPlanController | `app/Http/Controllers/BandwidthPlanController.php` | ✅ |
| BandwidthPlan/Index.vue | `resources/js/Pages/BandwidthPlan/Index.vue` | ✅ |
| BandwidthPlan/Create.vue | `resources/js/Pages/BandwidthPlan/Create.vue` | ✅ |
| BandwidthPlan/Edit.vue | `resources/js/Pages/BandwidthPlan/Edit.vue` | ✅ |
| NavLink desktop | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |
| NavLink responsive | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |
| Route index | `GET /bandwidth-plans` | ✅ |
| Route create | `GET /bandwidth-plans/create` | ✅ |
| Route store | `POST /bandwidth-plans` | ✅ |
| Route edit | `GET /bandwidth-plans/{id}/edit` | ✅ |
| Route update | `PUT /bandwidth-plans/{id}` | ✅ |
| Route destroy | `DELETE /bandwidth-plans/{id}` | ✅ |

**Catatan:**
- Model `BandwidthPlan` sudah ada dengan field: `name`, `upload_speed`, `download_speed`, `price`, `is_active`, `notes`
- Harga ditampilkan dalam format Rupiah (IDR) menggunakan `Intl.NumberFormat`
- Index menampilkan tabel dengan pagination
- Create/Edit menggunakan `useForm` dari Inertia
- Delete hanya untuk role `admin`

---

## Improvement Lanjutan

---

### ✅ #11 - Halaman Audit Log Viewer (Admin)

| Item | File | Status |
|------|------|--------|
| AuditLogController | `app/Http/Controllers/AuditLogController.php` | ✅ |
| AuditLog/Index.vue | `resources/js/Pages/AuditLog/Index.vue` | ✅ |
| Route audit-logs | `routes/web.php` - `GET /audit-logs` | ✅ |
| NavLink desktop | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |
| NavLink responsive | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |

**Catatan:** Halaman audit log menampilkan tabel dengan kolom: Waktu, User, Action (color-coded badges), Model, Detail, IP Address. Filter dropdown untuk Action, Model Type, dan User. Klik row untuk expand dan lihat old/new values sebagai JSON. Hanya admin yang bisa akses.

---

### ✅ #12 - Dashboard Auto-Refresh

| Item | File | Status |
|------|------|--------|
| Auto-refresh 30 detik | `resources/js/Pages/Dashboard.vue` | ✅ |

**Catatan:** Dashboard auto-refresh stats, alarms, dan tickets setiap 30 detik menggunakan `setInterval` + `router.reload({ only: [...], preserveScroll: true })`. Interval dibersihkan saat komponen di-unmount.

---

### ✅ #13 - User Management CRUD (Admin)

| Item | File | Status |
|------|------|--------|
| UserController | `app/Http/Controllers/UserController.php` | ✅ |
| User/Index.vue | `resources/js/Pages/User/Index.vue` | ✅ |
| User/Create.vue | `resources/js/Pages/User/Create.vue` | ✅ |
| User/Edit.vue | `resources/js/Pages/User/Edit.vue` | ✅ |
| Routes (6 routes) | `routes/web.php` | ✅ |
| NavLink desktop | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |
| NavLink responsive | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |

**Catatan:** CRUD lengkap untuk user management. Role: admin, operator, viewer (color-coded badges). Password optional saat edit. Tidak bisa menghapus akun sendiri. Semua route dilindungi `middleware('role:admin')`.

---

### ✅ #14 - Pagination & Search di Semua Halaman Index

| Item | File | Status |
|------|------|--------|
| ODC search + pagination | `OdcController::index()` + `Odc/Index.vue` | ✅ |
| ODP search + pagination | `OdpController::index()` + `Odp/Index.vue` | ✅ |
| Customer search + pagination | `CustomerController::index()` + `Customer/Index.vue` | ✅ |
| ONT search + pagination | `OntController::index()` + `Ont/Index.vue` | ✅ |
| User search + pagination | `UserController::index()` + `User/Index.vue` | ✅ |
| Alarm pagination | `AlarmController::index()` + `Alarm/Index.vue` | ✅ |
| Audit Log search + pagination | `AuditLogController::index()` + `AuditLog/Index.vue` | ✅ |

**Catatan:** Semua halaman index menggunakan Laravel pagination (`paginate()->withQueryString()`). Search menggunakan `watch` + `router.get` dengan `preserveState: true`. Query string dipertahankan saat navigasi pagination.

---

### ✅ #15 - Form Validation Error Handling

| Item | File | Status |
|------|------|--------|
| Customer Create/Edit errors | `Customer/Create.vue`, `Customer/Edit.vue` | ✅ |
| OLT Create/Edit errors | `Olt/Create.vue`, `Olt/Edit.vue` | ✅ |
| ODC Create/Edit errors | `Odc/Create.vue`, `Odc/Edit.vue` | ✅ |
| ODP Create/Edit errors | `Odp/Create.vue`, `Odp/Edit.vue` | ✅ |
| ONT Create/Edit errors | `Ont/Create.vue`, `Ont/Edit.vue` | ✅ |
| User Create/Edit errors | `User/Create.vue`, `User/Edit.vue` | ✅ |

**Catatan:** Semua form menampilkan error validation di bawah field menggunakan `form.errors.fieldName`. Input yang error ditandai dengan border merah (`border-red-500`). Menggunakan Inertia `useForm` untuk handling form state dan errors.

---

### ✅ #16 - Dark Mode / Theme Switcher

| Item | File | Status |
|------|------|--------|
| useDarkMode composable | `resources/js/Composables/useDarkMode.js` | ✅ |
| Toggle di layout desktop | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |
| Toggle di layout mobile | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |
| Tailwind dark mode config | `tailwind.config.js` | ✅ |

**Catatan:** Dark mode menggunakan class strategy Tailwind. Preferensi disimpan di localStorage. Toggle icon sun/moon di navbar.

---

### ⬜ #17 - Unit Test & Feature Test

| Item | File | Status |
|------|------|--------|
| Feature tests | - | ⬜ |
| Unit tests | - | ⬜ |

**Catatan:** Belum dikerjakan. Perlu dibuat test untuk controller, model, dan middleware.

---

### ✅ #18 - API Endpoint untuk Mobile App

| Item | File | Status |
|------|------|--------|
| ApiController | `app/Http/Controllers/Api/ApiController.php` | ✅ |
| API routes | `routes/api.php` | ✅ |
| Route registration | `bootstrap/app.php` | ✅ |
| POST /api/login | Token auth via Sanctum | ✅ |
| GET /api/dashboard | Stats summary | ✅ |
| GET /api/customers | List + search + paginate | ✅ |
| GET /api/customers/{id} | Detail with ONT | ✅ |
| GET /api/onts | List + search + paginate | ✅ |
| GET /api/onts/{id} | Detail | ✅ |
| GET /api/alarms | List + filter + paginate | ✅ |
| GET /api/tickets | List + filter + paginate | ✅ |
| POST /api/logout | Revoke token | ✅ |

**Catatan:** API menggunakan Laravel Sanctum token authentication. Login mengembalikan bearer token. Semua endpoint selain login memerlukan `auth:sanctum` middleware. Response dalam format JSON dengan pagination.

---

### ✅ #19 - Backup & Restore Database

| Item | File | Status |
|------|------|--------|
| BackupDatabase command | `app/Console/Commands/BackupDatabase.php` | ✅ |
| RestoreDatabase command | `app/Console/Commands/RestoreDatabase.php` | ✅ |

**Catatan:**
- `php artisan db:backup` - Backup database ke `storage/app/backups/backup_YYYY-MM-DD_HH-mm-ss.sql`
- `php artisan db:restore {file}` - Restore dari file backup
- Support MySQL (mysqldump) dan SQLite (file copy)
- Restore memiliki konfirmasi sebelum eksekusi

---

### ⬜ #20 - Multi-language (i18n) Support

| Item | File | Status |
|------|------|--------|
| i18n setup | - | ⬜ |

**Catatan:** Belum dikerjakan. Prioritas rendah.

---

### ✅ #21 - Performance Optimization

| Item | File | Status |
|------|------|--------|
| Eager loading di controllers | Semua controller | ✅ |
| Pagination di semua index | Semua controller | ✅ |
| withQueryString pagination | Semua controller | ✅ |
| Select specific columns | Map, ODP, ODC controllers | ✅ |
| withCount instead of loading | Customer, ODC, ODP controllers | ✅ |

**Catatan:** Optimasi yang dilakukan:
- Eager loading (`with()`) untuk menghindari N+1 queries
- `withCount()` untuk menghitung relasi tanpa loading semua data
- `select()` untuk hanya mengambil kolom yang diperlukan
- Pagination di semua halaman index (15-30 per page)
- `withQueryString()` untuk mempertahankan filter saat pagination

---

### ✅ #22 - Responsive Mobile Layout + PWA

| Item | File | Status |
|------|------|--------|
| PWA meta tags | `resources/views/app.blade.php` | ✅ |
| Mobile card layout semua Index | `Customer, ONT, OLT, Mikrotik, Ticket, BandwidthPlan, ODC, ODP, User, AuditLog` | ✅ |
| Alarm responsive layout | `resources/js/Pages/Alarm/Index.vue` | ✅ |
| Dashboard dark mode | `resources/js/Pages/Dashboard.vue` | ✅ |
| OLT Show responsive | `resources/js/Pages/Olt/Show.vue` | ✅ |
| Header responsive stacking | Semua halaman index | ✅ |

**Catatan:** Semua halaman index menggunakan pattern `hidden sm:block` untuk desktop table dan `sm:hidden` untuk mobile card layout. PWA meta tags ditambahkan untuk experience native-like di Android/iOS. Header tombol menggunakan `flex-col gap-3 sm:flex-row` untuk stacking di mobile.

---

### ✅ #23 - GPS Search ODP & Pelanggan untuk Teknisi

| Item | File | Status |
|------|------|--------|
| NearbyController | `app/Http/Controllers/NearbyController.php` | ✅ |
| Nearby/Index.vue | `resources/js/Pages/Nearby/Index.vue` | ✅ |
| Route nearby.index & nearby.search | `routes/web.php` | ✅ |
| NavLink desktop & responsive | `resources/js/Layouts/AuthenticatedLayout.vue` | ✅ |
| Geolocation API integration | `Nearby/Index.vue` | ✅ |
| Haversine distance formula | `NearbyController::search()` | ✅ |
| Sort by distance | `NearbyController::search()` | ✅ |
| Google Maps navigation | `Nearby/Index.vue` - openMaps() | ✅ |

**Catatan:** Halaman Nearby Search menggunakan Browser Geolocation API untuk mendapatkan posisi GPS teknisi, lalu mencari ODP dan pelanggan terdekat menggunakan Haversine formula di MySQL. Hasil diurutkan berdasarkan jarak, dengan tombol navigasi ke Google Maps. Filter radius (1-50 km) dan tipe (ODP/Pelanggan/Semua). Semua role bisa akses.

---

*Dokumen ini terakhir diupdate: 8 Maret 2026*
