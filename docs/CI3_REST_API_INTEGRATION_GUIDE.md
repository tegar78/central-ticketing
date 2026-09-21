# Panduan Integrasi RESTful API: Central Ticket System & CodeIgniter 3

Dokumen ini menjelaskan arsitektur dan cara menghubungkan aplikasi **CodeIgniter 3 (CI3)** ke **Central Ticket System (Laravel)** murni menggunakan **RESTful API** (HTTP JSON).

Dengan arsitektur RESTful API:
* **Tidak memerlukan koneksi database langsung (port 3306)** antar server.
* Setiap server billing CI3 dapat berada di VPS, jaringan, hosting, atau server lokal yang berbeda secara aman.
* Komunikasi dua arah terstandarisasi menggunakan JSON dan autentikasi `X-API-KEY`.

---

## 1. Arsitektur Komunikasi RESTful API (Metode 1-File Controller)

Di CodeIgniter 3, Anda hanya perlu **1 file controller** saja:  
📁 [`docs/Central.php`](file:///d:/project/central-ticket-system/docs/Central.php) -> Copy ke `application/controllers/Central.php` di CI3.

```
┌───────────────────────────────────────────────────────────┐
│               Central Ticket System (Hub)                 │
│               http://central-ticket-system.test           │
│                                                           │
│  Endpoint Inbound:                                        │
│  - POST /api/v1/tickets        (Terima tiket dari CI3)    │
│  - GET  /api/v1/tickets        (List tiket milik tenant)  │
│  - GET  /api/v1/tickets/{id}   (Cek detail status tiket)  │
│  - POST /api/v1/sync-customer  (Terima sinkron pelanggan) │
│                                                           │
│  Request Outbound:                                        │
│  - POST {callback_url}         (Webhook update status)    │
│  - GET  {domain_url}/central/customers (Tarik pelanggan) │
└────────────────────────▲───────┬──────────────────────────┘
                         │       │
          HTTP POST / GET│       │HTTP POST (Webhook Callback)
             (X-API-KEY) │       │
                         │       │
┌────────────────────────┴───────▼──────────────────────────┐
│          Aplikasi Billing CodeIgniter 3 (Mitra)           │
│          Controller: application/controllers/Central.php  │
│                                                           │
│  1. Saat Pelanggan / CS buat tiket di CI3:                │
│     -> $this->Central->kirim_tiket($data)                 │
│                                                           │
│  2. Saat Central klik "Sync Data":                        │
│     <- Central memanggil GET /central/customers           │
│                                                           │
│  3. Saat Status Tiket diperbarui teknisi Central:         │
│     <- Terima Webhook di POST /central/callback           │
└───────────────────────────────────────────────────────────┘
```

---

## 2. Autentikasi API

Setiap request dari CI3 ke Central Ticket System wajib menyertakan header:
```http
X-API-KEY: key-bill-001-secret-12345
Accept: application/json
Content-Type: application/json
```
API Key ini diterbitkan dan dicocokkan dengan kolom `api_key` di tabel `billing_instances` Central.

---

## 3. Spesifikasi Endpoint Central Ticket System

### A. Kirim Tiket Gangguan Baru (`POST /api/v1/tickets`)
Dipanggil oleh CI3 saat pelanggan atau admin membuat tiket gangguan baru di aplikasi billing.

* **URL**: `http://central-ticket-system.test/api/v1/tickets`
* **Method**: `POST`
* **Headers**:
  * `X-API-KEY`: `[API_KEY_BILLING_ANDA]`
  * `Content-Type`: `application/json`
  * `Accept`: `application/json`

* **Request Body (JSON)**:
```json
{
  "remote_ticket_id": "TKT-CI3-10293",
  "no_services": "10029384",
  "customer_name": "Budi Santoso",
  "customer_phone": "081234567890",
  "customer_address": "Jl. Mawar No. 12, RT 02/03",
  "latitude": "-7.250445",
  "longitude": "112.768845",
  "category_name": "Internet Lambat / Putus-putus",
  "problem_description": "Lampu LOS merah berkedip sejak pagi hari",
  "created_by_name": "Admin Billing",
  "created_by_role": "Operator"
}
```

* **Response (JSON - 201 Created)**:
```json
{
  "success": true,
  "message": "Tiket berhasil diterima di Central System",
  "data": {
    "ticket_id": 45,
    "ticket_number": "TKT-20260921-A8B9C",
    "status": "pending",
    "tenant_code": "BILL-001"
  }
}
```

---

### B. Sinkronisasi Data Pelanggan (`POST /api/v1/sync-customer`)
Mendukung pengiriman data pelanggan **satuan** (saat tambah/update pelanggan baru) maupun **batch array** (sinkronisasi massal).

* **URL**: `http://central-ticket-system.test/api/v1/sync-customer`
* **Method**: `POST`
* **Headers**: `X-API-KEY: [API_KEY_BILLING_ANDA]`

* **Request Body - Format Satuan (Single Object)**:
```json
{
  "remote_customer_id": 105,
  "no_services": "10029384",
  "name": "Budi Santoso",
  "phone": "081234567890",
  "address": "Jl. Mawar No. 12",
  "odp_name": "ODP-KBD-01",
  "latitude": "-7.250445",
  "longitude": "112.768845",
  "package_name": "Paket 50 Mbps",
  "monthly_fee": 250000,
  "status": "active"
}
```

* **Request Body - Format Batch (Array of Objects)**:
```json
[
  {
    "remote_customer_id": 105,
    "no_services": "10029384",
    "name": "Budi Santoso",
    "phone": "081234567890",
    "address": "Jl. Mawar No. 12",
    "status": "active"
  },
  {
    "remote_customer_id": 106,
    "no_services": "10029385",
    "name": "Siti Rahma",
    "phone": "081234567891",
    "address": "Jl. Melati No. 5",
    "status": "isolated"
  }
]
```

* **Response (JSON - 200 OK)**:
```json
{
  "success": true,
  "message": "Berhasil menyinkronkan 2 data pelanggan dari billing node [BILL-001] PT Gayuh Media Informatika.",
  "processed_count": 2,
  "billing_node": {
    "id": 1,
    "tenant_code": "BILL-001",
    "name": "PT Gayuh Media Informatika"
  }
}
```

---

### C. Cek Detail & Status Tiket (`GET /api/v1/tickets/{id}`)
Digunakan oleh CI3 untuk mengecek status terkini, riwayat penanganan (timeline), dan teknisi yang ditugaskan.

* **URL**: `http://central-ticket-system.test/api/v1/tickets/TKT-20260921-A8B9C`
* **Method**: `GET`
* **Headers**: `X-API-KEY: [API_KEY_BILLING_ANDA]`

* **Response (JSON - 200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 45,
    "ticket_number": "TKT-20260921-A8B9C",
    "remote_ticket_id": "TKT-CI3-10293",
    "no_services": "10029384",
    "customer_name": "Budi Santoso",
    "status": "process",
    "assigned_technician": {
      "id": 3,
      "name": "Teknisi Gayuh A",
      "phone": "081234567892"
    },
    "timelines": [
      {
        "status": "pending",
        "remark": "Tiket dibuat via PT Gayuh Media Informatika",
        "created_at": "2026-09-21T07:15:00.000000Z"
      },
      {
        "status": "process",
        "remark": "Ditugaskan ke teknisi: Teknisi Gayuh A",
        "created_at": "2026-09-21T07:20:00.000000Z"
      }
    ]
  }
}
```

---

## 4. Webhook Callback CI3 (Central -> CI3)

Saat status tiket diperbarui oleh Teknisi atau Operator di Central Ticket System, Central akan otomatis mengirimkan HTTP POST ke `callback_url` billing Anda (misal: `http://bill-testing.gayuh.net.id/help/api_callback`):

* **Payload yang Diterima CI3**:
```json
{
  "remote_ticket_id": "TKT-CI3-10293",
  "ticket_number": "TKT-20260921-A8B9C",
  "status": "close",
  "remark": "Kabel dropcore putus telah disambung kembali. Redaman -19 dBm. Selesai.",
  "technician_name": "Teknisi Gayuh A"
}
```

### Contoh Controller CodeIgniter 3 Menerima Callback:
Simpan di `application/controllers/Help.php` (atau controller yang menangani callback):
```php
public function api_callback()
{
    $raw_input = file_get_contents('php://input');
    $payload = json_decode($raw_input, true);

    if (empty($payload)) {
        $this->output->set_status_header(400)->set_output('Invalid payload');
        return;
    }

    $remote_ticket_id = $payload['remote_ticket_id'] ?? null;
    $status = $payload['status'] ?? null;
    $remark = $payload['remark'] ?? '';
    $technician = $payload['technician_name'] ?? '';

    // Update status tiket di database lokal CI3
    if ($remote_ticket_id) {
        $this->db->where('ticket_id', $remote_ticket_id);
        $this->db->update('tickets', [
            'status'     => ($status === 'close') ? 'selesai' : 'diproses',
            'remark'     => $remark,
            'technician' => $technician,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(['success' => true, 'message' => 'Callback processed']));
}
```

---

## 5. File Helper CI3 yang Disediakan

Central Ticket System menyediakan 2 file helper siap pakai di folder `docs/`:
1. [`docs/ci3_ticket_creation_helper.php`](file:///d:/project/central-ticket-system/docs/ci3_ticket_creation_helper.php):
   Fungsi `send_ticket_to_central_hub($ticket_data)` untuk kirim tiket via cURL.
2. [`docs/ci3_customer_sync_helper.php`](file:///d:/project/central-ticket-system/docs/ci3_customer_sync_helper.php):
   Fungsi `sync_customer_to_central_hub($customer_data)` untuk sinkronisasi pelanggan via cURL.
