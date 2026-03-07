# FTTH Real-Time Monitoring System - Checklist Improvement

## Status: 10/10 Selesai

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

## Improvement Lanjutan (Belum Dikerjakan)

| No | Item | Prioritas |
|----|------|-----------|
| 11 | Halaman Audit Log viewer (admin) | Medium |
| 12 | Dashboard widget realtime (auto-refresh stats) | Medium |
| 13 | User management CRUD (admin) | High |
| 14 | Pagination & search di semua halaman index | Medium |
| 15 | Form validation error handling yang konsisten | Low |
| 16 | Dark mode / theme switcher | Low |
| 17 | Unit test & feature test | High |
| 18 | API endpoint untuk mobile app | Medium |
| 19 | Backup & restore database | Medium |
| 20 | Multi-language (i18n) support | Low |
| 21 | Performance optimization (caching, lazy loading) | Medium |

---

*Dokumen ini terakhir diupdate: 7 Maret 2026*
