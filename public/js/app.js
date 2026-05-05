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
    await refreshProducts();
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
    await refreshProducts();
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
    const { status, json } = await api('/api/orders', { method: 'POST', body: JSON.stringify(payload) });
    out.textContent = `HTTP ${status}\n` + pretty(json);
    await refreshProducts();
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

try {
    const tab = localStorage.getItem('active_tab') || 'user';
    setTab(tab === 'admin' ? 'admin' : 'user');

    const adminKey = loadAdminKey();
    const adminKeyForm = document.getElementById('adminKeyForm');
    if (adminKeyForm && adminKeyForm.admin_key) {
        adminKeyForm.admin_key.value = adminKey;
    }
} catch {
}

refreshProducts('productsList');
refreshProducts('productsListAdmin');
