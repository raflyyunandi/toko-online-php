<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>toko-online-app</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <div class="container">
        <h1>toko-online-app</h1>

        <div class="tabs">
            <button id="tabUser" type="button" class="tab active">User</button>
            <button id="tabAdmin" type="button" class="tab">Admin</button>
        </div>

        <div id="userTab" class="tab-content active">
            <div class="grid">
                <div class="card">
                    <h2>Buat Order</h2>
                    <div class="badge-row">
                        <span class="badge" id="flashSaleBadge">Flash Sale: OFF</span>
                    </div>
                    <form id="orderForm">
                        <label>Customer</label>
                        <input name="customer_name" placeholder="Nama customer (opsional)">

                        <label>Product ID</label>
                        <input name="product_id" type="number" min="1" required>

                        <label>Quantity</label>
                        <input name="quantity" type="number" min="1" value="1" required>

                        <button type="submit">Checkout</button>
                    </form>
                    <pre id="orderResult" class="result"></pre>
                </div>

                <div class="card">
                    <h2>Order Saya</h2>
                    <form id="myOrdersForm">
                        <label>Customer</label>
                        <input name="customer_name" placeholder="Masukkan nama customer yang dipakai saat checkout" required>
                        <button type="submit">Lihat Order</button>
                    </form>
                    <pre id="myOrdersResult" class="result"></pre>
                </div>
            </div>

            <div class="card">
                <h2>Daftar Produk</h2>
                <button id="refreshProducts" type="button">Refresh</button>
                <pre id="productsList" class="result"></pre>
            </div>

            <div class="card">
                <h2>Race Condition FlashSale</h2>
                <form id="multiTabSetupForm">
                    <label>Nama untuk tab ini</label>
                    <input name="customer_name" placeholder="Contoh: user-tab-1" required>
                    <button type="submit">Simpan Nama Tab</button>
                </form>

                <form id="multiTabTriggerForm">
                    <label>Product ID (untuk semua tab)</label>
                    <input name="product_id" type="number" min="1" required>

                    <label>Quantity (untuk semua tab)</label>
                    <input name="quantity" type="number" min="1" value="1" required>

                    <button type="submit">Trigger Checkout (semua tab)</button>
                </form>

                <label>
                    Tab yang terdaftar
                    <span class="tooltip" tabindex="0" data-tooltip="Masing-masing tab menyimpan nama user-nya sendiri (sessionStorage).&#10;Saat klik Trigger, tab ini akan broadcast sinyal ke tab lain agar melakukan checkout hampir bersamaan.&#10;Catatan: satu tab tidak bisa membaca nilai input tab lain secara langsung karena batasan keamanan browser.">?</span>
                </label>
                <pre id="multiTabPeers" class="result"></pre>

                <label>
                    Penjelasan Locking
                    <span class="tooltip" tabindex="0" data-tooltip="Locking (Flash Sale ON) menggunakan BEGIN IMMEDIATE di SQLite.&#10;&#10;Apa artinya?&#10;- Write-lock diambil sejak awal transaksi.&#10;- Hanya 1 transaksi boleh menulis (stok/order) pada satu waktu.&#10;- Request lain menunggu sampai COMMIT/ROLLBACK (dibatasi busy_timeout).&#10;&#10;Siapa yang dapat giliran dulu?&#10;Bukan ditentukan oleh kode PHP, melainkan scheduler OS + SQLite: siapa yang paling cepat sampai ke BEGIN IMMEDIATE dan berhasil ambil lock. Dipengaruhi latency, urutan diterima server, jadwal CPU/OS, dan waktu eksekusi.&#10;&#10;Hasilnya: pemenang bisa terlihat acak antar user, tapi stok tetap aman dan tidak negatif.">?</span>
                </label>
                <pre id="lockingInfo" class="result">Flash Sale OFF: transaksi normal.
Flash Sale ON: transaksi memakai locking (BEGIN IMMEDIATE) untuk mencegah race condition.</pre>

                <pre id="multiTabResult" class="result"></pre>
            </div>
        </div>

        <div id="adminTab" class="tab-content">
            <div class="grid">
                <div class="card">
                    <h2>Admin Key</h2>
                    <form id="adminKeyForm">
                        <label>Admin Key</label>
                        <div class="input-row">
                            <input name="admin_key" type="password" placeholder="Isi sesuai ADMIN_KEY di .env" autocomplete="off" required>
                            <button id="toggleAdminKey" type="button" class="btn-secondary">Show</button>
                        </div>
                        <button type="submit">Simpan</button>
                    </form>
                    <pre id="adminKeyResult" class="result"></pre>
                </div>

                <div class="card">
                    <h2>Flash Sale</h2>
                    <button id="toggleFlashSale" type="button">Aktifkan</button>
                    <pre id="flashSaleResult" class="result"></pre>
                </div>

                <div class="card">
                    <h2>Buat Produk</h2>
                    <form id="productForm">
                        <label>Nama</label>
                        <input name="name" placeholder="Contoh: Flash Sale Item" required>

                        <label>Harga (integer)</label>
                        <input name="price" type="number" min="0" value="10000" required>

                        <label>Stok (integer)</label>
                        <input name="stock" type="number" min="0" value="10" required>

                        <button type="submit">Simpan</button>
                    </form>
                    <pre id="productResult" class="result"></pre>
                </div>
            </div>

            <div class="grid">
                <div class="card">
                    <h2>Set Stok Produk</h2>
                    <form id="setStockForm">
                        <label>Product ID</label>
                        <input name="product_id" type="number" min="1" required>

                        <label>Stock Baru</label>
                        <input name="stock" type="number" min="0" value="0" required>

                        <button type="submit">Update</button>
                    </form>
                    <pre id="setStockResult" class="result"></pre>
                </div>

                <div class="card">
                    <h2>Order untuk Produk</h2>
                    <form id="productOrdersForm">
                        <label>Product ID</label>
                        <input name="product_id" type="number" min="1" required>
                        <button type="submit">Lihat</button>
                    </form>
                    <pre id="productOrdersResult" class="result"></pre>
                </div>
            </div>

            <div class="grid">
                <div class="card">
                    <h2>Semua Order</h2>
                    <form id="allOrdersForm">
                        <label>Limit</label>
                        <input name="limit" type="number" min="1" max="200" value="50" required>

                        <label>Offset</label>
                        <input name="offset" type="number" min="0" value="0" required>

                        <button type="submit">Ambil Data</button>
                    </form>
                    <pre id="allOrdersResult" class="result"></pre>
                </div>

                <div class="card">
                    <h2>Daftar Customer</h2>
                    <form id="customersForm">
                        <label>Limit</label>
                        <input name="limit" type="number" min="1" max="500" value="100" required>
                        <button type="submit">Ambil Data</button>
                    </form>
                    <pre id="customersResult" class="result"></pre>
                </div>
            </div>

            <div class="card">
                <h2>Semua Produk</h2>
                <button id="refreshProductsAdmin" type="button">Refresh</button>
                <pre id="productsListAdmin" class="result"></pre>
            </div>
        </div>
    </div>

    <script src="/js/app.js"></script>
</body>
</html>
