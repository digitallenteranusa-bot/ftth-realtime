# FTTH Real-Time Monitoring System - Implementation Plan

## Context
Membangun sistem monitoring dan manajemen FTTH (Fiber To The Home) real-time dengan integrasi perangkat jaringan (Mikrotik, OLT multi-vendor, ODC, ODP, ONT) dan peta interaktif dengan animasi garis fiber optik.

## Tech Stack
- **Backend**: Laravel 11 + PHP 8.2+
- **Frontend**: Vue 3 + Inertia.js + Vite
- **Map**: Leaflet.js + leaflet-polylinedecorator (animated lines)
- **Real-time**: Laravel Reverb (WebSocket)
- **Database**: MySQL 8
- **CSS**: Tailwind CSS
- **Device Comm**: RouterOS API, SNMP, Telnet/SSH (phpseclib)

---

## Implementation Phases

### Phase 1: Project Setup & Database
1. Create Laravel 11 project with Inertia/Vue/Tailwind
2. Install dependencies:
   - `evilfreelancer/routeros-api-php` (Mikrotik API)
   - `phpseclib/phpseclib` (SSH/Telnet untuk OLT)
   - `leaflet`, `@vue-leaflet/vue-leaflet`, `leaflet-polylinedecorator`
   - `laravel-echo`, `pusher-js` (WebSocket client)
3. Database migrations (15 tabel):
   - `users` - Admin system
   - `customers` - Pelanggan FTTH
   - `mikrotiks` - Multi Router Mikrotik (name, host, api_port, api_username, api_password_encrypted, snmp_community, snmp_port, is_active, location, lat, lng, notes)
   - `olts` - OLT devices (vendor enum: zte/huawei/fiberhome, host, telnet_port, snmp_community)
   - `pon_ports` - Port PON pada OLT
   - `odcs` - Optical Distribution Cabinet (lat, lng, capacity, splitter_ratio, geojson_area)
   - `odps` - Optical Distribution Point (lat, lng, capacity, odc_id)
   - `onts` - ONT devices (odp_id, customer_id, serial, status, rx_power, olt_id, pon_port, ont_id)
   - `fiber_routes` - GeoJSON LineString untuk jalur kabel (source_type, source_id, dest_type, dest_id, coordinates JSON)
   - `bandwidth_plans` - Paket internet
   - `alarms` - Alert/notifikasi dari perangkat
   - `trouble_tickets` - Tiket gangguan
   - `monitoring_logs` - Log monitoring periodik
   - `pppoe_sessions` - Session PPPoE aktif dari Mikrotik
   - `interface_traffics` - Data traffic interface

### Phase 2: Backend Services & Device Integration

#### Mikrotik Service - Multi Router (`app/Services/Mikrotik/`)
- **MikrotikConnectionManager.php** - Connection pool, manage koneksi ke banyak Mikrotik
- **MikrotikApiService.php** - RouterOS API client (port 8728/8729)
- **MikrotikSnmpService.php** - SNMP monitoring (traffic, interface)
- Setiap Mikrotik di-register di tabel `mikrotiks` (bisa unlimited jumlah)
- Polling job iterate semua Mikrotik aktif dan poll masing-masing
- Dashboard bisa filter per-Mikrotik atau aggregated view
- Features per Mikrotik:
  - PPPoE session management (active users, disconnect, etc.)
  - Interface monitoring (traffic in/out per interface)
  - Queue/bandwidth management
  - Hotspot user management
  - System resource monitoring (CPU, RAM, uptime)
  - Log viewer

#### OLT Service (`app/Services/Olt/`) - Multi-vendor dengan Driver Pattern
- `OltDriverInterface.php` - Contract
- `ZteOltDriver.php` - ZTE C320/C300 via Telnet
- `HuaweiOltDriver.php` - Huawei MA56xx via Telnet
- `FiberHomeOltDriver.php` - FiberHome via Telnet
- `OltServiceFactory.php` - Auto-detect vendor, return driver
- Methods: getOntList(), getOntStatus(), getOpticalPower(), registerOnt(), deregisterOnt()

#### Polling Jobs (`app/Jobs/`)
- `PollMikrotikJob` - Poll SEMUA Mikrotik aktif (loop each, dispatch per-device sub-job)
- `PollOltJob` - Poll OLT setiap 1-5 menit
- `PollOntStatusJob` - Check ONT online/offline
- Scheduled via `routes/console.php`

#### Events & Broadcasting (`app/Events/`)
- `OntStatusChanged` - Broadcast saat ONT berubah status
- `AlarmTriggered` - Broadcast saat ada alarm baru
- `TrafficUpdated` - Broadcast data traffic real-time
- `DeviceStatusChanged` - Broadcast status perangkat

### Phase 3: API & Controllers

#### Routes (`routes/web.php` via Inertia)
```
GET  /dashboard          - Dashboard utama
GET  /map                - Peta interaktif full-screen
GET  /mikrotiks          - CRUD Mikrotik
GET  /olts               - CRUD OLT
GET  /odcs               - CRUD ODC
GET  /odps               - CRUD ODP
GET  /onts               - CRUD ONT
GET  /customers          - CRUD Pelanggan
GET  /fiber-routes       - Manage jalur kabel
GET  /alarms             - Daftar alarm
GET  /tickets            - Trouble tickets
```

#### API Routes (`routes/api.php`)
```
GET  /api/map/elements    - Semua elemen peta (GeoJSON)
GET  /api/map/fiber-routes - Semua jalur fiber (GeoJSON)
GET  /api/monitoring/traffic/{mikrotik} - Real-time traffic
GET  /api/monitoring/ont-status/{olt} - ONT status list
POST /api/olt/{olt}/ont/register - Register ONT baru
```

### Phase 4: Frontend Vue Components

#### Pages (`resources/js/Pages/`)
- `Dashboard.vue` - Dashboard with stats cards, charts, recent alarms
- `Map/Index.vue` - Full-screen map view
- `Mikrotik/Index.vue`, `Show.vue`, `Create.vue`
- `Olt/Index.vue`, `Show.vue` (with PON port tree view)
- `Odc/Index.vue`, `Show.vue`
- `Odp/Index.vue`, `Show.vue`
- `Ont/Index.vue`, `Show.vue`
- `Customer/Index.vue`, `Show.vue`, `Create.vue`
- `Alarm/Index.vue`
- `Ticket/Index.vue`, `Create.vue`

#### Map Components (`resources/js/Components/Map/`)
- `NetworkMap.vue` - Main map container (Leaflet)
- `AnimatedFiberLine.vue` - Animated polyline with directional arrows
- `DeviceMarker.vue` - Custom marker per device type
- `DevicePopup.vue` - Info popup on click
- `MapLegend.vue` - Legend panel
- `MapFilter.vue` - Filter sidebar (by status, area, device type)
- `FiberRouteDrawer.vue` - Draw/edit fiber routes on map

#### Animated Lines Implementation
```javascript
// Menggunakan leaflet-polylinedecorator untuk animasi
// - OLT → ODC: Thick blue animated dashed line
// - ODC → ODP: Medium green animated dashed line
// - ODP → ONT: Thin cyan animated line
// - Warna berubah berdasarkan status (hijau=aktif, merah=down, kuning=warning)
// - Animasi panah bergerak menunjukkan arah data flow
// - Kecepatan animasi bisa merepresentasikan traffic load
```

### Phase 5: Real-time Integration
- Setup Laravel Reverb WebSocket server
- Vue frontend listen via Laravel Echo
- Auto-update map markers saat ONT status berubah
- Real-time alarm notification toast
- Live traffic graph update pada dashboard

---

## Key Files to Create (Priority Order)

### Batch 1: Foundation
- [ ] Laravel project + all migrations
- [ ] Models with relationships
- [ ] Seeders with sample data
- [ ] Auth (Laravel Breeze)

### Batch 2: Core Backend
- [ ] MikrotikService + RouterOS API
- [ ] OLT Driver pattern (ZTE, Huawei, FiberHome)
- [ ] Polling jobs + scheduler
- [ ] Events + broadcasting

### Batch 3: Frontend
- [ ] Layout + navigation (AppLayout.vue)
- [ ] Dashboard page
- [ ] NetworkMap.vue with animated lines
- [ ] CRUD pages for all entities

### Batch 4: Real-time & Polish
- [ ] WebSocket integration
- [ ] Real-time map updates
- [ ] Alarm system
- [ ] Trouble ticket system

---

## Verification Plan
1. `php artisan migrate` - Semua migration berhasil
2. `php artisan db:seed` - Sample data ter-populate
3. `npm run dev` - Frontend build sukses
4. Login → Dashboard menampilkan statistik
5. Map menampilkan semua elemen dengan animasi garis
6. Klik device di map → popup detail muncul
7. `php artisan reverb:start` → real-time updates berjalan
8. Simulasi ONT down → marker berubah merah di map real-time
