# toko-online-app (Native PHP)

API JSON sederhana untuk kebutuhan flash sale:

- Order minimal terdiri dari 1 Order Item.
- Mencegah stok (inventory) menjadi negatif saat terjadi order bersamaan.
- Menyediakan functional test via command line untuk menguji race condition.

## Requirement

- PHP 7.4+
- Composer

## Instalasi

```bash
composer install
composer migrate
```

Opsional seed data:

```bash
composer seed
```

## Menjalankan Server (Local)

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

Buka:

- Web (frontend sederhana): http://127.0.0.1:8000/
- Healthcheck: http://127.0.0.1:8000/api/health

## Endpoint API

### Products

- `GET /api/products`
- `GET /api/products/{id}`
- `POST /api/products` (Admin-only, butuh `X-Admin-Key`)
- `PATCH /api/products/{id}/stock` (Admin-only, butuh `X-Admin-Key`)
- `GET /api/products/{id}/orders` (Admin-only, butuh `X-Admin-Key`)

Contoh request:

```bash
curl.exe -X POST http://127.0.0.1:8000/api/products ^
  -H "Content-Type: application/json" ^
  -H "X-Admin-Key: local-admin-key" ^
  -d "{\"name\":\"Flash Sale Item\",\"price\":10000,\"stock\":10}"
```

### Orders

- `POST /api/orders`
- `GET /api/orders` (User: filter `customer_name`, Admin: lihat semua + pagination)
- `GET /api/orders/{id}`

Contoh request:

```bash
curl.exe -X POST http://127.0.0.1:8000/api/orders ^
  -H "Content-Type: application/json" ^
  -d "{\"customer_name\":\"andi\",\"items\":[{\"product_id\":1,\"quantity\":1}]}"
```

Jika stok tidak cukup, API mengembalikan `409 Conflict` dengan code `out_of_stock`.

## Flash Sale (Locking)

Mode flash sale bisa diaktifkan untuk mensimulasikan lonjakan order bersamaan dengan metode locking di level database (SQLite). Saat aktif, proses checkout akan berjalan serial (antrian lock), sehingga menghindari masalah race condition.

- Header: `X-Flash-Sale: 1`
- Atau query: `?flash_sale=1`

Contoh (aktifkan flash sale saat checkout):

```bash
curl.exe -X POST http://127.0.0.1:8000/api/orders ^
  -H "Content-Type: application/json" ^
  -H "X-Flash-Sale: 1" ^
  -d "{\"customer_name\":\"andi\",\"items\":[{\"product_id\":1,\"quantity\":1}]}"
```

## Functional Test (Race Condition)

Test akan:

1. Menjalankan built-in server PHP pada port lokal.
2. Membuat produk stok 10 (menggunakan `X-Admin-Key`).
3. Menjalankan 20 proses order bersamaan.
4. Memastikan hanya 10 yang sukses (201) dan 10 ditolak (409), serta stok akhir = 0.

Jalankan:

```bash
composer test
```

## Fitur Tambahan (Opsional)

Bagian ini menjelaskan fitur yang ditambahkan di luar kebutuhan inti (flash sale + race condition), untuk memudahkan demo sebagai Admin dan User.

### Peran Admin vs User

- **User (Customer)**: membuat order dan melihat order miliknya sendiri berdasarkan `customer_name`.
- **Admin**: membuat produk, mengubah stok, dan melihat seluruh order.

### Admin Key

Endpoint admin dilindungi header `X-Admin-Key`. Nilainya diambil dari file `.env`:

```
ADMIN_KEY=local-admin-key
```

Contoh header:

```bash
curl.exe -H "X-Admin-Key: local-admin-key"
```

### Endpoint Tambahan

**Admin-only**

- `POST /api/products` (create produk + stok awal) + `X-Admin-Key`
- `PATCH /api/products/{id}/stock` (set stok) + `X-Admin-Key`
- `GET /api/orders` (lihat semua order, mendukung `limit` dan `offset`) + `X-Admin-Key`
- `GET /api/customers` (rekap customer yang pernah order) + `X-Admin-Key`
- `GET /api/products/{id}/orders` (lihat siapa saja yang order produk tertentu) + `X-Admin-Key`

**User**

- `GET /api/orders?customer_name=andi` (lihat order milik customer tertentu)

### Demo via Browser

Buka `http://127.0.0.1:8000/`:

- Tab **User**: checkout dan cari order berdasarkan nama customer.
- Tab **Admin**: isi Admin Key (default tersensor, bisa show/hide), buat produk, ubah stok, toggle Flash Sale ON/OFF, dan lihat semua order.

Untuk demo race condition:

- Buka 2-3 tab browser pada halaman yang sama.
- Set "Nama tab ini" di masing-masing tab.
- Jalankan checkout via section **Race Condition FlashSale** (trigger akan mengirim perintah checkout ke tab lain yang aktif).

### Contoh Request (Windows)

Buat produk (Admin):

```bash
curl.exe -X POST http://127.0.0.1:8000/api/products ^
  -H "Content-Type: application/json" ^
  -H "X-Admin-Key: local-admin-key" ^
  -d "{\"name\":\"Meja\",\"price\":55000,\"stock\":7}"
```

Lihat semua order (Admin):

```bash
curl.exe http://127.0.0.1:8000/api/orders -H "X-Admin-Key: local-admin-key"
```

Cari order berdasarkan nama customer (User):

```bash
curl.exe "http://127.0.0.1:8000/api/orders?customer_name=andi"
```

Audit siapa saja yang order produk tertentu (Admin):

```bash
curl.exe http://127.0.0.1:8000/api/products/2/orders -H "X-Admin-Key: local-admin-key"
```
