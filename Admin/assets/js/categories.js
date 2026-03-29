const API_URL = './api/categories.php';
const PUBLIC_CATALOG_KEY = 'sv_products_v1';
const BUMP_KEY = 'catalog.bump';
let categories = [];

function saveLocalCategories(list) {
  try {
    localStorage.setItem('admin.categories', JSON.stringify(list || []));
  } catch {
    // ignore storage errors
  }
}

function syncPublicCatalogFromAdmin() {
  try {
    const rawProds = localStorage.getItem('admin.products');
    if (!rawProds) return;

    const products = JSON.parse(rawProds);
    if (!Array.isArray(products) || !products.length) return;

    const rawCats = localStorage.getItem('admin.categories');
    let localCats = [];
    try {
      const parsedCats = JSON.parse(rawCats || '[]');
      localCats = Array.isArray(parsedCats) ? parsedCats : [];
    } catch (_err) {
      localCats = [];
    }

    const activeCategoryIds = new Set(
      localCats
        .filter((c) => String(c.status).toLowerCase() === 'active')
        .map((c) => Number(c.id))
    );

    const catalog = products
      .filter(
        (p) =>
          (String(p.status || 'selling') === 'selling') &&
          activeCategoryIds.has(Number(p.categoryId))
      )
      .map((p) => {
        const category = localCats.find((c) => Number(c.id) === Number(p.categoryId));
        return {
          id: p.seedId || `admin-${p.id}`,
          name: p.name || '',
          brand: p.supplier || '',
          category: (category && (category.slug || category.name && String(category.name).toLowerCase().replace(/[^a-z0-9]+/g, '-'))) || 'other',
          price: Number(p.price) || 0,
          original_price: undefined,
          image: p.image || '../assets/images/placeholder.png',
          images: p.image ? [p.image] : [],
          badge: '',
          featured: false,
          short_desc: p.desc || p.description || '',
          long_desc: p.desc || p.description || '',
          specs: {
            'Đơn vị': p.uom || p.unit || '',
            Mã: p.code || p.sku || '',
          },
          unit: p.uom || p.unit || '',
          quantity: 1,
          min_qty: 1,
          max_qty: Math.max(1, Number(p.qty) || Number(p.quantity) || 1),
          stock: Number(p.qty) || Number(p.quantity) || 0,
          tags: [],
          details: [],
          usage: [],
        };
      });

    localStorage.setItem(PUBLIC_CATALOG_KEY, JSON.stringify(catalog));
    localStorage.setItem(BUMP_KEY, String(Date.now()));
  } catch (err) {
    console.warn('Không thể đồng bộ catalog public từ admin categories:', err);
  }
}

function notifyError(message) {
  alert(message || 'Có lỗi xảy ra. Vui lòng thử lại.');
}

async function apiRequest(action, body = {}, method = 'GET') {
  const url = API_URL + '?action=' + encodeURIComponent(action);
  const options = {
    method,
    headers: {
      Accept: 'application/json',
    },
  };
  if (method === 'POST') {
    options.body = new URLSearchParams(body);
  }
  const response = await fetch(url, options);
  const data = await response.json();
  if (!response.ok || data.success !== true) {
    throw new Error(data.message || 'Lỗi API');
  }
  return data;
}

function render(list) {
  const q = (document.getElementById('q')?.value || '').toLowerCase().trim();
  const cats = (list || categories).filter((c) => {
    if (q && !`${c.name} ${c.description || ''}`.toLowerCase().includes(q)) {
      return false;
    }
    return true;
  });

  const tbody = document.getElementById('cat-body');
  if (!tbody) return;

  const countEl = document.getElementById('cat-count');
  if (countEl) countEl.textContent = `${cats.length} danh mục`;

  tbody.innerHTML = cats
    .map((c) => {
      const isActive = c.status === 'active';
      const toggleLabel = isActive ? 'Ẩn' : 'Hiện';
      const statusBadge = isActive
        ? '<span class="badge on">Đang dùng</span>'
        : '<span class="badge off">Đang ẩn</span>';
      return `
      <tr>
        <td>
          <div style="display:flex; align-items:center; gap:10px;">
            <strong>${c.name}</strong> ${statusBadge}
          </div>
        </td>
        <td>
          <a href="#" class="btn btn-action" data-act="toggle" data-id="${c.id}">${toggleLabel}</a>
        </td>
      </tr>
    `;
    })
    .join('');
}

async function loadCategories() {
  const body = document.getElementById('cat-body');
  if (body) {
    body.innerHTML = '<tr><td colspan="3" class="inline-clean-11">Đang tải…</td></tr>';
  }
  try {
    const data = await apiRequest('list');
    categories = Array.isArray(data.categories) ? data.categories : [];
    saveLocalCategories(categories);
    syncPublicCatalogFromAdmin();
    render(categories);
  } catch (err) {
    notifyError(err.message);
  }
}

function setForm(c) {
  document.getElementById('id').value = c?.id || '';
  document.getElementById('name').value = c?.name || '';
  document.getElementById('form-title').textContent = c?.id ? 'Sửa danh mục' : 'Thêm danh mục';
}

document.getElementById('btn-new')?.addEventListener('click', () => {
  setForm(null);
  window.AdminCategoryDrawer?.open?.();
});

document.getElementById('btn-cancel')?.addEventListener('click', () => setForm(null));

document.getElementById('q')?.addEventListener('input', () => render());

document.getElementById('cat-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id = Number(document.getElementById('id').value || 0);
  const name = document
    .getElementById('name')
    .value.trim()
    .replace(/\s{2,}/g, ' ');

  if (!name) {
    return alert('Nhập tên danh mục');
  }

  try {
    const res = await apiRequest('save', { id, name }, 'POST');
    if (!res || res.success !== true) {
      notifyError(res && res.message ? res.message : 'Lỗi không xác định khi thêm danh mục.');
      return;
    }
    await loadCategories();
    // Phát custom event để các module khác cập nhật dropdown
    window.dispatchEvent(new Event('admin:categories-updated'));
    setForm(null);
    window.AdminCategoryDrawer?.close?.();
  } catch (err) {
    notifyError(err && err.message ? err.message : 'Lỗi không xác định khi thêm danh mục.');
  }
});

document.getElementById('cat-body')?.addEventListener('click', async (e) => {
  const a = e.target.closest('a[data-act]');
  if (!a) return;
  e.preventDefault();

  const id = Number(a.dataset.id);
  const act = a.dataset.act;
  const category = categories.find((x) => Number(x.id) === id);
  if (!category) return;

  if (act === 'edit') {
    setForm(category);
    window.AdminCategoryDrawer?.open?.();
    return;
  }

  if (act === 'toggle') {
    try {
      await apiRequest('toggle', { id }, 'POST');
      await loadCategories();
    } catch (err) {
      notifyError(err.message);
    }
    return;
  }
});

loadCategories();
