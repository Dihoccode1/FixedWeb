const API_BASE = "./api/products.php";
const CAT_API = "./api/categories.php";

const state = {
  categories: [],
  products: [],
};

async function apiFetch(url, options = {}) {
  options.headers = {
    ...(options.headers || {}),
    Accept: "application/json",
  };
  options.credentials = "same-origin";

  const res = await fetch(url, options);
  const text = await res.text();
  if (!res.ok) {
    throw new Error(text || `HTTP ${res.status}`);
  }
  try {
    return JSON.parse(text);
  } catch {
    throw new Error("Phản hồi không hợp lệ từ máy chủ");
  }
}

async function fetchCategories() {
  const data = await apiFetch(`${CAT_API}?action=list`);
  return Array.isArray(data.categories) ? data.categories : [];
}

async function fetchProducts() {
  const data = await apiFetch(`${API_BASE}?action=list`);
  return Array.isArray(data.products) ? data.products : [];
}

function normalizeCategoryId(prod) {
  return prod.category_id || prod.categoryId || "";
}

function getCategoryName(categoryId) {
  return (
    state.categories.find((c) => String(c.id) === String(categoryId))?.name ||
    ""
  );
}

function formatCurrency(value) {
  return Math.round(Number(value || 0)).toLocaleString("vi-VN", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
}

function getSellPrice(product) {
  const cost = Number(product.cost_price ?? 0);
  const margin = Number(product.profit_margin ?? 0);
  const salePrice = Number(product.sale_price ?? 0);
  return salePrice || Math.round(cost * (1 + margin / 100));
}

function renderHeadlessMessage(message) {
  const tbody = document.getElementById("pricing-body");
  if (!tbody) return;
  tbody.innerHTML = `
    <tr>
      <td colspan="9" style="text-align:center;color:#9aa3ad;padding:20px">
        ${message}
      </td>
    </tr>`;
}

function renderPricing() {
  const q = (document.getElementById("q")?.value || "").toLowerCase().trim();
  const cat = document.getElementById("filter-cat")?.value || "";
  const tbody = document.getElementById("pricing-body");
  if (!tbody) return;

  const rows = state.products.filter((p) => {
    const categoryName = getCategoryName(normalizeCategoryId(p));
    const haystack =
      `${p.sku || p.code || ""} ${p.name || ""} ${categoryName}`.toLowerCase();
    if (q && !haystack.includes(q)) return false;
    if (cat && String(normalizeCategoryId(p)) !== cat) return false;
    return true;
  });

  if (!rows.length) {
    renderHeadlessMessage("Không có sản phẩm phù hợp");
    return;
  }

  tbody.innerHTML = rows
    .map((p, index) => {
      const categoryName = getCategoryName(normalizeCategoryId(p));
      const statusLabel =
        p.status === "selling"
          ? "Đang bán"
          : p.status === "stopped"
            ? "Hết bán"
            : "Ẩn";
      const cost = Number(p.cost_price ?? 0);
      const margin = Number(p.profit_margin ?? 0);
      const price = getSellPrice(p);

      return `
      <tr>
        <td>${index + 1}</td>
        <td>${p.sku || p.code || ""}</td>
        <td>${p.name || ""}</td>
        <td>${categoryName}</td>
        <td>${formatCurrency(cost)}</td>
        <td>
          <input
            type="number"
            class="input margin-input pricing-margin-input"
            data-id="${p.id}"
            value="${margin}"
            min="0"
          />
        </td>
        <td>
          <span class="price-display" data-id="${p.id}">
            ${formatCurrency(price)}
          </span>
        </td>
        <td>${statusLabel}</td>
        <td>
          <button class="btn btn-action btn-save-one" data-id="${p.id}">
            Lưu
          </button>
        </td>
      </tr>`;
    })
    .join("");
}

async function loadData() {
  try {
    state.categories = await fetchCategories();
  } catch (error) {
    console.error("fetchCategories error", error);
    state.categories = [];
  }

  try {
    state.products = await fetchProducts();
  } catch (error) {
    console.error("fetchProducts error", error);
    state.products = [];
    renderHeadlessMessage("Không thể tải dữ liệu sản phẩm.");
    return;
  }

  const filterCat = document.getElementById("filter-cat");
  if (filterCat) {
    filterCat.innerHTML =
      `<option value="">— Tất cả loại —</option>` +
      state.categories
        .filter((c) => c.status === "active")
        .map((c) => `<option value="${c.id}">${c.name}</option>`)
        .join("");
  }

  renderPricing();
}

function normalizeProductForSave(product) {
  return {
    id: product.id,
    code: product.sku || product.code || "",
    name: product.name || "",
    category_id: normalizeCategoryId(product),
    description: product.description || product.desc || "",
    unit: product.unit || product.uom || "",
    quantity: product.quantity ?? product.qty ?? 0,
    cost_price: Number(product.cost_price ?? product.cost ?? 0),
    profit_margin: Number(product.profit_margin ?? product.margin ?? 0),
    sale_price: Number(product.sale_price ?? product.price ?? 0),
    supplier: product.supplier || "",
    status: product.status || "selling",
    image: product.image || null,
  };
}

async function savePricingRow(productId, newMargin) {
  const product = state.products.find(
    (p) => String(p.id) === String(productId),
  );
  if (!product) throw new Error("Không tìm thấy sản phẩm");

  const data = normalizeProductForSave(product);
  data.profit_margin = Number(newMargin || 0);
  data.sale_price = Math.round(
    Number(data.cost_price || 0) * (1 + data.profit_margin / 100),
  );

  const formData = new FormData();
  formData.append("action", "save");
  formData.append("id", data.id);
  formData.append("code", data.code);
  formData.append("name", data.name);
  formData.append("category_id", data.category_id);
  formData.append("description", data.description);
  formData.append("unit", data.unit);
  formData.append("quantity", data.quantity);
  formData.append("cost_price", data.cost_price);
  formData.append("profit_margin", data.profit_margin);
  formData.append("sale_price", data.sale_price);
  formData.append("supplier", data.supplier);
  formData.append("status", data.status);

  const result = await apiFetch(API_BASE, {
    method: "POST",
    body: formData,
  });

  if (!result || !result.success) {
    throw new Error(result?.message || "Lưu không thành công");
  }

  return result;
}

// Event bindings
const qEl = document.getElementById("q");
const filterCatEl = document.getElementById("filter-cat");
const pricingBody = document.getElementById("pricing-body");

qEl?.addEventListener("input", renderPricing);
filterCatEl?.addEventListener("change", renderPricing);

pricingBody?.addEventListener("input", (event) => {
  const input = event.target.closest(".margin-input");
  if (!input) return;

  const id = input.dataset.id;
  const margin = Number(input.value || 0);
  const product = state.products.find((p) => String(p.id) === String(id));
  if (!product) return;

  const cost = Number(product.cost_price ?? 0);
  const price = Math.round(cost * (1 + margin / 100));
  const span = document.querySelector(`.price-display[data-id="${id}"]`);
  if (span) span.textContent = formatCurrency(price);
});

pricingBody?.addEventListener("click", async (event) => {
  const btn = event.target.closest(".btn-save-one");
  if (!btn) return;
  event.preventDefault();

  const id = btn.dataset.id;
  const marginInput = document.querySelector(`.margin-input[data-id="${id}"]`);
  if (!marginInput) return;

  const margin = Number(marginInput.value || 0);
  try {
    await savePricingRow(id, margin);
    await loadData();
    alert("Đã cập nhật giá bán sản phẩm.");
  } catch (error) {
    alert(error.message || "Cập nhật giá bán thất bại");
  }
});

loadData();
