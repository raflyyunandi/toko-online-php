async function api(path, options = {}) {
    const res = await fetch(path, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {})
        }
    });
    const json = await res.json().catch(() => ({}));
    return { status: res.status, json };
}

function pretty(obj) {
    return JSON.stringify(obj, null, 2);
}

async function refreshProducts(outputId) {
    const out = document.getElementById(outputId);
    if (!out) {
        return;
    }
    const { status, json } = await api('/api/products', { method: 'GET' });
    out.textContent = `HTTP ${status}\n` + pretty(json);
}

document.getElementById('refreshProducts')?.addEventListener('click', () => refreshProducts('productsList'));
document.getElementById('refreshProductsAdmin')?.addEventListener('click', () => refreshProducts('productsListAdmin'));

function loadAdminKey() {
    try {
        return localStorage.getItem('admin_key') || '';
    } catch {
        return '';
    }
}

function saveAdminKey(value) {
    try {
        localStorage.setItem('admin_key', value);
    } catch {
    }
}

function loadFlashSale() {
    try {
        return localStorage.getItem('flash_sale') || '0';
    } catch {
        return '0';
    }
}

function saveFlashSale(value) {
    try {
        localStorage.setItem('flash_sale', value ? '1' : '0');
    } catch {
    }
}

function isFlashSaleActive() {
    return loadFlashSale() === '1';
}

function maskKey(key) {
    const v = (key || '').trim();
    if (!v) {
        return '';
    }
    if (v.length <= 4) {
        return '***';
    }
    return v.slice(0, 2) + '***' + v.slice(-2);
}

function adminHeaders() {
    const key = loadAdminKey().trim();
    return key ? { 'X-Admin-Key': key } : {};
}

function orderHeaders() {
    return isFlashSaleActive() ? { 'X-Flash-Sale': '1' } : {};
}

function updateFlashSaleUi() {
    const active = isFlashSaleActive();

    const badge = document.getElementById('flashSaleBadge');
    if (badge) {
        badge.textContent = active ? 'Flash Sale: ON' : 'Flash Sale: OFF';
    }

    const btn = document.getElementById('toggleFlashSale');
    if (btn) {
        btn.textContent = active ? 'Nonaktifkan' : 'Aktifkan';
    }

    const out = document.getElementById('flashSaleResult');
    if (out) {
        out.textContent = active
            ? 'Mode flash sale aktif. Order akan memakai locking di server.'
            : 'Mode flash sale nonaktif.';
    }
}

function setTab(name) {
    const userBtn = document.getElementById('tabUser');
    const adminBtn = document.getElementById('tabAdmin');
    const userTab = document.getElementById('userTab');
    const adminTab = document.getElementById('adminTab');

    if (!userBtn || !adminBtn || !userTab || !adminTab) {
        return;
    }

    const isAdmin = name === 'admin';
    userBtn.classList.toggle('active', !isAdmin);
    adminBtn.classList.toggle('active', isAdmin);
    userTab.classList.toggle('active', !isAdmin);
    adminTab.classList.toggle('active', isAdmin);

    try {
        localStorage.setItem('active_tab', isAdmin ? 'admin' : 'user');
    } catch {
    }
}

document.getElementById('tabUser')?.addEventListener('click', () => setTab('user'));
document.getElementById('tabAdmin')?.addEventListener('click', () => setTab('admin'));

document.getElementById('adminKeyForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const key = (form.admin_key?.value || '').trim();
    const out = document.getElementById('adminKeyResult');
    if (!key) {
        out.textContent = 'Admin key wajib diisi.';
        return;
    }
    saveAdminKey(key);
    out.textContent = 'Admin key tersimpan: ' + maskKey(key);
});

document.getElementById('toggleAdminKey')?.addEventListener('click', () => {
    const form = document.getElementById('adminKeyForm');
    if (!form || !form.admin_key) {
        return;
    }
    const input = form.admin_key;
    const btn = document.getElementById('toggleAdminKey');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    if (btn) {
        btn.textContent = isHidden ? 'Hide' : 'Show';
    }
});

document.getElementById('toggleFlashSale')?.addEventListener('click', () => {
    const active = isFlashSaleActive();
    saveFlashSale(!active);
    updateFlashSaleUi();
});

document.getElementById('productForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const payload = {
        name: form.name.value,
        price: Number(form.price.value),
        stock: Number(form.stock.value),
    };
    const out = document.getElementById('productResult');
    const { status, json } = await api('/api/products', {
        method: 'POST',
        headers: adminHeaders(),
        body: JSON.stringify(payload)
    });
    out.textContent = `HTTP ${status}\n` + pretty(json);
    await refreshProducts('productsList');
    await refreshProducts('productsListAdmin');
});

document.getElementById('setStockForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const productId = Number(form.product_id.value);
    const stock = Number(form.stock.value);
    const out = document.getElementById('setStockResult');

    if (!productId || productId < 1) {
        out.textContent = 'Product ID tidak valid.';
        return;
    }
    if (Number.isNaN(stock) || stock < 0) {
        out.textContent = 'Stock tidak valid.';
        return;
    }

    const { status, json } = await api(`/api/products/${productId}/stock`, {
        method: 'PATCH',
        headers: adminHeaders(),
        body: JSON.stringify({ stock })
    });
    out.textContent = `HTTP ${status}\n` + pretty(json);
    await refreshProducts('productsList');
    await refreshProducts('productsListAdmin');
});

document.getElementById('allOrdersForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const limit = Number(form.limit.value);
    const offset = Number(form.offset.value);
    const out = document.getElementById('allOrdersResult');

    const qs = new URLSearchParams({
        limit: String(limit),
        offset: String(offset),
    }).toString();

    const { status, json } = await api('/api/orders?' + qs, { method: 'GET', headers: adminHeaders() });
    out.textContent = `HTTP ${status}\n` + pretty(json);
});

document.getElementById('customersForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const limit = Number(form.limit.value);
    const out = document.getElementById('customersResult');

    const qs = new URLSearchParams({
        limit: String(limit),
    }).toString();

    const { status, json } = await api('/api/customers?' + qs, { method: 'GET', headers: adminHeaders() });
    out.textContent = `HTTP ${status}\n` + pretty(json);
});

document.getElementById('productOrdersForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const productId = Number(form.product_id.value);
    const out = document.getElementById('productOrdersResult');
    if (!productId || productId < 1) {
        out.textContent = 'Product ID tidak valid.';
        return;
    }
    const { status, json } = await api(`/api/products/${productId}/orders`, { method: 'GET', headers: adminHeaders() });
    out.textContent = `HTTP ${status}\n` + pretty(json);
});

document.getElementById('orderForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const payload = {
        customer_name: form.customer_name.value || null,
        items: [
            { product_id: Number(form.product_id.value), quantity: Number(form.quantity.value) }
        ]
    };
    const out = document.getElementById('orderResult');
    const { status, json } = await api('/api/orders', {
        method: 'POST',
        headers: orderHeaders(),
        body: JSON.stringify(payload)
    });
    out.textContent = `HTTP ${status}\n` + pretty(json);
    await refreshProducts('productsList');
    await refreshProducts('productsListAdmin');
});

document.getElementById('myOrdersForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const customerName = (form.customer_name.value || '').trim();
    const out = document.getElementById('myOrdersResult');
    if (!customerName) {
        out.textContent = 'Customer wajib diisi.';
        return;
    }
    const { status, json } = await api('/api/orders?customer_name=' + encodeURIComponent(customerName), { method: 'GET' });
    out.textContent = `HTTP ${status}\n` + pretty(json);
});

function loadTabCustomerName() {
    try {
        return sessionStorage.getItem('tab_customer_name') || '';
    } catch {
        return '';
    }
}

function saveTabCustomerName(value) {
    try {
        sessionStorage.setItem('tab_customer_name', value);
    } catch {
    }
}

function loadTabId() {
    try {
        let v = sessionStorage.getItem('tab_id') || '';
        if (!v) {
            v = String(Date.now()) + '-' + String(Math.random()).slice(2);
            sessionStorage.setItem('tab_id', v);
        }
        return v;
    } catch {
        return 'tab-' + String(Date.now()) + '-' + String(Math.random()).slice(2);
    }
}

const PEER_PREFIX = 'toko_online_peer:';
const CLAIM_PREFIX = 'toko_online_claim:';
const RUNTIME_TOKEN = String(Date.now()) + '-' + String(Math.random()).slice(2);
const ACTIVE_TTL_MS = 15000;

function generateTabId() {
    return String(Date.now()) + '-' + String(Math.random()).slice(2);
}

function ensureUniqueTabId() {
    for (let i = 0; i < 5; i++) {
        let id = '';
        try {
            id = sessionStorage.getItem('tab_id') || '';
        } catch {
            id = '';
        }

        if (!id) {
            id = generateTabId();
            try {
                sessionStorage.setItem('tab_id', id);
            } catch {
            }
        }

        const claimKey = CLAIM_PREFIX + id;
        const now = Date.now();
        let existing = null;
        try {
            const raw = localStorage.getItem(claimKey) || '';
            existing = raw ? JSON.parse(raw) : null;
        } catch {
            existing = null;
        }

        const existingToken = existing && existing.token ? String(existing.token) : '';
        const existingSeen = existing && existing.last_seen ? Number(existing.last_seen) : 0;
        const isClaimedByOther = existingToken && existingToken !== RUNTIME_TOKEN && (now - existingSeen) <= ACTIVE_TTL_MS;

        if (isClaimedByOther) {
            const newId = generateTabId();
            try {
                sessionStorage.setItem('tab_id', newId);
            } catch {
            }
            continue;
        }

        try {
            localStorage.setItem(claimKey, JSON.stringify({ token: RUNTIME_TOKEN, last_seen: now }));
        } catch {
        }

        return id;
    }

    return generateTabId();
}

function upsertPeer(id, name, lastSeen) {
    const peerId = String(id || '').trim();
    const peerName = String(name || '').trim();
    if (!peerId || !peerName) {
        return;
    }
    const payload = { id: peerId, name: peerName, last_seen: Number(lastSeen || Date.now()) };
    try {
        localStorage.setItem(PEER_PREFIX + peerId, JSON.stringify(payload));
    } catch {
    }

    try {
        localStorage.setItem(CLAIM_PREFIX + peerId, JSON.stringify({ token: RUNTIME_TOKEN, last_seen: payload.last_seen }));
    } catch {
    }
}

function readClaim(id) {
    try {
        const raw = localStorage.getItem(CLAIM_PREFIX + String(id)) || '';
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function listPeers() {
    const now = Date.now();
    const peers = [];
    try {
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (!key || key.indexOf(PEER_PREFIX) !== 0) {
                continue;
            }
            const raw = localStorage.getItem(key) || '';
            const p = raw ? JSON.parse(raw) : null;
            if (!p || !p.id || !p.name) {
                continue;
            }
            const ts = Number(p.last_seen || 0);
            const claim = readClaim(p.id);
            const claimSeen = claim && claim.last_seen ? Number(claim.last_seen) : 0;

            if ((now - ts) > ACTIVE_TTL_MS || (now - claimSeen) > ACTIVE_TTL_MS) {
                try {
                    localStorage.removeItem(key);
                } catch {
                }
                continue;
            }
            peers.push({ id: String(p.id), name: String(p.name), last_seen: ts });
        }
    } catch {
    }
    return peers;
}

function renderPeers() {
    const out = document.getElementById('multiTabPeers');
    if (!out) {
        return;
    }
    const myId = ensureUniqueTabId();
    const rows = listPeers().filter((p) => p && p.name && p.id !== myId);

    const byName = {};
    rows.forEach((p) => {
        const name = String(p.name);
        const prev = byName[name];
        if (!prev || Number(p.last_seen) > Number(prev.last_seen)) {
            byName[name] = p;
        }
    });

    const uniqueRows = Object.keys(byName)
        .map((k) => byName[k])
        .sort((a, b) => String(a.name).localeCompare(String(b.name)));

    if (uniqueRows.length === 0) {
        out.textContent = 'Belum ada tab lain yang terdaftar.';
        return;
    }

    out.textContent = uniqueRows.map((p) => `- ${p.name}`).join('\n');
}

function announceTab() {
    const tabId = ensureUniqueTabId();
    const name = loadTabCustomerName().trim();
    if (!name) {
        renderPeers();
        return;
    }

    upsertPeer(tabId, name, Date.now());
    renderPeers();

    const msg = { type: 'register_tab', payload: { id: tabId, name, last_seen: Date.now() } };
    if (multiTabChannel) {
        try {
            multiTabChannel.postMessage(msg);
        } catch {
        }
    }

    try {
        localStorage.setItem('multi_tab_register', JSON.stringify(msg));
        localStorage.setItem('multi_tab_register_trigger', String(Date.now()) + '-' + String(Math.random()));
    } catch {
    }
}

async function multiTabCheckout(payload) {
    const out = document.getElementById('multiTabResult');
    const tabName = loadTabCustomerName().trim();

    if (!out) {
        return;
    }
    if (!tabName) {
        out.textContent = 'Nama tab belum diset. Isi lalu klik "Simpan Nama Tab".';
        return;
    }
    if (!payload || !payload.product_id || !payload.quantity) {
        out.textContent = 'Payload tidak valid.';
        return;
    }

    const orderPayload = {
        customer_name: tabName,
        items: [
            { product_id: Number(payload.product_id), quantity: Number(payload.quantity) }
        ]
    };

    const { status, json } = await api('/api/orders', {
        method: 'POST',
        headers: orderHeaders(),
        body: JSON.stringify(orderPayload)
    });

    out.textContent = `Tab: ${tabName}\nHTTP ${status}\n` + pretty(json);
    await refreshProducts('productsList');
    await refreshProducts('productsListAdmin');
}

const multiTabChannel = (() => {
    try {
        return 'BroadcastChannel' in window ? new BroadcastChannel('toko-online-app') : null;
    } catch {
        return null;
    }
})();

function broadcastMultiTabCheckout(payload) {
    const message = { type: 'checkout_all', payload };
    if (multiTabChannel) {
        try {
            multiTabChannel.postMessage(message);
        } catch {
        }
    }

    try {
        localStorage.setItem('multi_tab_payload', JSON.stringify(payload));
        localStorage.setItem('multi_tab_trigger', String(Date.now()) + '-' + String(Math.random()));
    } catch {
    }
}

if (multiTabChannel) {
    multiTabChannel.addEventListener('message', (event) => {
        const data = event?.data;
        if (!data || data.type !== 'checkout_all') {
            if (data && data.type === 'register_tab' && data.payload) {
                const p = data.payload;
                if (p.id && p.name) {
                    upsertPeer(p.id, p.name, Date.now());
                    renderPeers();
                }
            }
            return;
        }
        multiTabCheckout(data.payload);
    });
}

window.addEventListener('storage', (event) => {
    if (!event) {
        return;
    }
    if (event.key !== 'multi_tab_trigger') {
        if (event.key === 'multi_tab_register_trigger') {
            try {
                const raw = localStorage.getItem('multi_tab_register') || '';
                const msg = raw ? JSON.parse(raw) : null;
                if (msg && msg.type === 'register_tab' && msg.payload) {
                    const p = msg.payload;
                    if (p.id && p.name) {
                        upsertPeer(p.id, p.name, Date.now());
                        renderPeers();
                    }
                }
            } catch {
            }
        } else if (event.key && event.key.indexOf(PEER_PREFIX) === 0) {
            renderPeers();
        }
        return;
    }
    try {
        const raw = localStorage.getItem('multi_tab_payload') || '';
        const payload = raw ? JSON.parse(raw) : null;
        multiTabCheckout(payload);
    } catch {
    }
});

document.getElementById('multiTabSetupForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const name = (form.customer_name?.value || '').trim();
    const out = document.getElementById('multiTabResult');
    if (!name) {
        out.textContent = 'Nama tab wajib diisi.';
        return;
    }
    saveTabCustomerName(name);
    out.textContent = 'Nama tab tersimpan: ' + name;
    announceTab();
});

document.getElementById('multiTabTriggerForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const productId = Number(form.product_id?.value);
    const qty = Number(form.quantity?.value);
    const out = document.getElementById('multiTabResult');

    if (!productId || productId < 1) {
        out.textContent = 'Product ID tidak valid.';
        return;
    }
    if (!qty || qty < 1) {
        out.textContent = 'Quantity tidak valid.';
        return;
    }

    const payload = { product_id: productId, quantity: qty };
    broadcastMultiTabCheckout(payload);
    out.textContent = 'Trigger dikirim. Menjalankan checkout di tab ini dan mengirim trigger ke tab lain...';
    await multiTabCheckout(payload);
});

try {
    const tab = localStorage.getItem('active_tab') || 'user';
    setTab(tab === 'admin' ? 'admin' : 'user');

    const adminKey = loadAdminKey();
    const adminKeyForm = document.getElementById('adminKeyForm');
    if (adminKeyForm && adminKeyForm.admin_key) {
        adminKeyForm.admin_key.value = adminKey;
    }

    const tabName = loadTabCustomerName();
    const multiTabSetupForm = document.getElementById('multiTabSetupForm');
    if (multiTabSetupForm && multiTabSetupForm.customer_name) {
        multiTabSetupForm.customer_name.value = tabName;
    }
} catch {
}

refreshProducts('productsList');
refreshProducts('productsListAdmin');
updateFlashSaleUi();
renderPeers();
announceTab();
setInterval(announceTab, 5000);

window.addEventListener('beforeunload', () => {
    const tabId = ensureUniqueTabId();
    try {
        localStorage.removeItem(PEER_PREFIX + tabId);
    } catch {
    }
    try {
        localStorage.removeItem(CLAIM_PREFIX + tabId);
    } catch {
    }
});
