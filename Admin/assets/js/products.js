const API_BASE = "./api/products.php";
const CAT_API = "./api/categories.php";

const state = {
  categories: [],
  products: [],
};

const els = {
  catSelect: document.getElementById("categoryId"),
  filterCat: document.getElementById("filter-cat"),
  filterStatus: document.getElementById("filter-status"),
  searchInput: document.getElementById("q"),
  tbody: document.getElementById("prod-body"),
  drawer: document.getElementById("prod-drawer"),
  backdrop: document.getElementById("drawer-backdrop"),
  btnNew: document.getElementById("btn-new"),
  btnClose: document.getElementById("btn-close-drawer"),
  btnCancel: document.getElementById("btn-cancel"),
  formTitle: document.getElementById("form-title"),
  form: document.getElementById("prod-form"),
  imgPreview: document.getElementById("img-preview"),
};

let currentImageData = null;

async function apiFetch(url, options = {}) {
  options.headers = {
    ...(options.headers || {}),
    Accept: "application/json",
  };
  options.credentials = "same-origin";
  const res = await fetch(url, options);
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`HTTP ${res.status}: ${text}`);
  }
  return res.json();
}

async function fetchCategories() {
  const data = await apiFetch(`${CAT_API}?action=list`);
  return Array.isArray(data.categories) ? data.categories : [];
}

async function fetchProducts() {
  const data = await apiFetch(`${API_BASE}?action=list`);
  return Array.isArray(data.products) ? data.products : [];
}

async function fetchProductById(id) {
  const data = await apiFetch(
    `${API_BASE}?action=get&id=${encodeURIComponent(id)}`,
  );
  return data.product || null;
}

function renderMessage(message) {
  if (!els.tbody) return;
  els.tbody.innerHTML = `<tr><td colspan="13" style="padding:20px;text-align:center;color:#6b7280;">${message}</td></tr>`;
}

function fillCategories() {
  const activeCats = state.categories.filter((c) => c.status === "active");
  const selectedCat = els.catSelect?.value || "";
  const selectedFilter = els.filterCat?.value || "";

  if (els.catSelect) {
    els.catSelect.innerHTML = activeCats
      .map((c) => `<option value="${c.id}">${c.name}</option>`)
      .join("");
    if (
      selectedCat &&
      Array.from(els.catSelect.options).some((opt) => opt.value === selectedCat)
    ) {
      els.catSelect.value = selectedCat;
    }
  }

  if (els.filterCat) {
    els.filterCat.innerHTML =
      `<option value="">— Tất cả loại —</option>` +
      activeCats
        .map((c) => `<option value="${c.id}">${c.name}</option>`)
        .join("");
    if (
      selectedFilter &&
      Array.from(els.filterCat.options).some(
        (opt) => opt.value === selectedFilter,
      )
    ) {
      els.filterCat.value = selectedFilter;
    }
  }
}

function normalizeCategoryId(prod) {
  return prod.category_id || prod.categoryId || "";
}

function adminAssetUrl(path) {
  if (!path) return "";
  if (/^(?:https?:)?\/\//.test(path) || path.startsWith("data:")) {
    return path;
  }
  return path.replace(/^(?:\.\.\/|\.\/|\/)*assets\//, "../assets/");
}

function formatCurrency(value) {
  return Math.round(Number(value || 0)).toLocaleString("vi-VN", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
}

function render() {
  if (!els.tbody) return;
  const q = (els.searchInput?.value || "").toLowerCase().trim();
  const cat = els.filterCat?.value || "";
  const st = els.filterStatus?.value || "";

  const rows = state.products.filter((p) => {
    const effectiveStatus = p.status === "stopped" ? "hidden" : p.status;
    if (
      q &&
      !`${p.sku || p.code || ""} ${p.name || ""} ${p.description || p.desc || ""}`
        .toLowerCase()
        .includes(q)
    ) {
      return false;
    }
    if (cat && String(normalizeCategoryId(p)) !== cat) return false;
    if (st && effectiveStatus !== st) return false;
    return true;
  });

  if (!rows.length) {
    renderMessage("Không có sản phẩm nào.");
    return;
  }

  const rowsHtml = rows
    .map((p, index) => {
      const categoryName =
        state.categories.find(
          (c) => String(c.id) === String(normalizeCategoryId(p)),
        )?.name || "";
      const img = p.image
        ? `<img src="${adminAssetUrl(p.image)}" alt="" class="thumb">`
        : "";

      let statusBadge = "";
      if (p.status === "selling") {
        statusBadge = '<span class="status-chip selling">Đang bán</span>';
      } else if (p.status === "hidden" || p.status === "stopped") {
        statusBadge = '<span class="status-chip hidden">Ẩn</span>';
      } else {
        statusBadge = '<span class="status-chip hidden">Ẩn</span>';
      }

      return `
      <tr>
        <td>${index + 1}</td>
        <td>${img}</td>
        <td>${p.sku || p.code || ""}</td>
        <td>${p.name || ""}</td>
        <td>${categoryName}</td>
        <td>${p.unit || p.uom || ""}</td>
        <td>${p.quantity ?? p.qty ?? 0}</td>
        <td>${formatCurrency(p.cost_price ?? p.cost ?? 0)}</td>
        <td>${Number(p.profit_margin ?? p.margin ?? 0)}%</td>
        <td>${formatCurrency(p.sale_price ?? p.price ?? 0)}</td>
        <td>${p.supplier || ""}</td>
        <td>${statusBadge}</td>
        <td>
          <a href="#" class="btn btn-action" data-act="edit" data-id="${p.id}">Sửa</a>
          <a href="#" class="btn btn-action" data-act="delete" data-id="${p.id}">Xóa và ẩn</a>
        </td>
      </tr>`;
    })
    .join("");

  els.tbody.innerHTML = rowsHtml;
}

async function loadData() {
  try {
    state.categories = await fetchCategories();
    fillCategories();
  } catch (error) {
    console.error("fetchCategories error", error);
    renderMessage("Không thể tải danh mục.");
    state.categories = [];
  }

  try {
    state.products = await fetchProducts();
    render();
  } catch (error) {
    console.error("fetchProducts error", error);
    renderMessage("Không thể tải sản phẩm.");
    state.products = [];
  }
}

function openDrawer() {
  els.drawer?.classList.add("open");
  els.backdrop?.classList.add("show");
  els.drawer?.setAttribute("aria-hidden", "false");
}

function closeDrawer() {
  els.drawer?.classList.remove("open");
  els.backdrop?.classList.remove("show");
  els.drawer?.setAttribute("aria-hidden", "true");
}

function resetForm() {
  els.form?.reset();
  currentImageData = null;
  renderPreview();
  els.formTitle.textContent = "Thêm sản phẩm";
  document.getElementById("id").value = "";
}

function renderPreview() {
  if (!els.imgPreview) return;
  const src = currentImageData ? adminAssetUrl(currentImageData) : "";
  els.imgPreview.innerHTML = src
    ? `<img src="${src}" alt="" style="max-width:100%;border-radius:10px;border:1px solid #243040">`
    : `<small style="color:#9ca3af;">(chưa có hình)</small>`;
}

function recalcPriceFromMargin() {
  const costEl = document.getElementById("cost");
  const marginEl = document.getElementById("margin");
  const priceEl = document.getElementById("price");
  if (!costEl || !marginEl || !priceEl) return;
  const cost = Number(costEl.value || 0);
  const margin = Number(marginEl.value || 0);
  const price = Math.round(cost * (1 + margin / 100));
  priceEl.value = isFinite(price) ? price : 0;
}

function recalcMarginFromPrice() {
  const costEl = document.getElementById("cost");
  const marginEl = document.getElementById("margin");
  const priceEl = document.getElementById("price");
  if (!costEl || !marginEl || !priceEl) return;
  const cost = Number(costEl.value || 0);
  const price = Number(priceEl.value || 0);
  if (cost <= 0) {
    marginEl.value = 0;
    return;
  }
  const margin = Math.round((price / cost - 1) * 100);
  marginEl.value = isFinite(margin) ? margin : 0;
}

function setFormValues(product) {
  document.getElementById("id").value = product?.id || "";
  document.getElementById("code").value = product?.sku || product?.code || "";
  document.getElementById("name").value = product?.name || "";
  document.getElementById("categoryId").value =
    product?.category_id ||
    product?.categoryId ||
    els.catSelect?.options[0]?.value ||
    "";
  document.getElementById("desc").value =
    product?.description || product?.desc || "";
  document.getElementById("uom").value = product?.unit || product?.uom || "";
  const marginEl = document.getElementById("margin");
  if (marginEl) marginEl.value = product?.profit_margin ?? product?.margin ?? 0;
  document.getElementById("supplier").value = product?.supplier || "";
  const statusValue =
    product?.status === "stopped" ? "hidden" : product?.status;
  document.getElementById("status").value = statusValue || "selling";

  currentImageData = product?.image || null;
  renderPreview();
  els.formTitle.textContent = product?.id ? "Sửa sản phẩm" : "Thêm sản phẩm";
}

async function saveProduct(formData) {
  const res = await apiFetch(API_BASE, {
    method: "POST",
    body: formData,
  });
  if (typeof window.syncToStorefront === "function") {
    try {
      window.syncToStorefront();
    } catch (e) {}
  }
  return res;
}

async function updateProductStatus(id, status) {
  const product = state.products.find((p) => String(p.id) === String(id));
  if (!product) throw new Error("Không tìm thấy sản phẩm");
  const formData = new FormData();
  formData.append("action", "save");
  formData.append("id", id);
  formData.append("code", product.sku || product.code || "");
  formData.append("name", product.name || "");
  formData.append("category_id", normalizeCategoryId(product));
  formData.append("description", product.description || product.desc || "");
  formData.append("unit", product.unit || product.uom || "");
  formData.append("quantity", product.quantity ?? product.qty ?? 0);
  formData.append("cost_price", product.cost_price ?? product.cost ?? 0);
  formData.append(
    "profit_margin",
    product.profit_margin ?? product.margin ?? 0,
  );
  formData.append("sale_price", product.sale_price ?? product.price ?? 0);
  formData.append("supplier", product.supplier || "");
  formData.append("status", status);
  return saveProduct(formData);
}

function addEventListeners() {
  els.btnNew?.addEventListener("click", () => {
    resetForm();
    openDrawer();
  });
  els.btnClose?.addEventListener("click", closeDrawer);
  els.btnCancel?.addEventListener("click", (e) => {
    e.preventDefault();
    resetForm();
    closeDrawer();
  });
  els.backdrop?.addEventListener("click", closeDrawer);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeDrawer();
  });

  els.searchInput?.addEventListener("input", render);
  els.filterCat?.addEventListener("change", render);
  els.filterStatus?.addEventListener("change", render);

  document.getElementById("image")?.addEventListener("change", (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      currentImageData = reader.result;
      renderPreview();
    };
    reader.readAsDataURL(file);
  });

  document.getElementById("btn-remove-img")?.addEventListener("click", () => {
    currentImageData = null;
    const input = document.getElementById("image");
    if (input) input.value = "";
    renderPreview();
  });

  document
    .getElementById("cost")
    ?.addEventListener("input", recalcPriceFromMargin);
  document
    .getElementById("margin")
    ?.addEventListener("input", recalcPriceFromMargin);
  document
    .getElementById("price")
    ?.addEventListener("input", recalcMarginFromPrice);

  els.tbody?.addEventListener("click", async (event) => {
    const button = event.target.closest("a[data-act]");
    if (!button) return;
    event.preventDefault();
    const id = button.dataset.id;
    const act = button.dataset.act;

    if (act === "edit") {
      const product = await fetchProductById(id);
      if (!product) {
        alert("Không tìm thấy sản phẩm");
        return;
      }
      setFormValues(product);
      openDrawer();
      return;
    }

    if (act === "delete") {
      if (!confirm("Bạn chắc chắn muốn xóa và ẩn sản phẩm này?")) return;
      try {
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("id", id);
        await apiFetch(API_BASE, {
          method: "POST",
          body: formData,
        });
        await loadData();
      } catch (error) {
        alert(error.message || "Xóa sản phẩm thất bại");
      }
      return;
    }
  });

  els.form?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(els.form);
    formData.set("action", "save");
    if (document.getElementById("image")?.files?.[0]) {
      formData.set("image", document.getElementById("image").files[0]);
    }

    // Always set status to 'selling' when adding new product
    if (!formData.get("id")) {
      formData.set("status", "selling");
      formData.set("quantity", "0");
      formData.set("cost_price", "0");
      formData.set("sale_price", "0");
    }

    if (!formData.get("code") || !formData.get("name")) {
      alert("Nhập mã & tên sản phẩm");
      return;
    }

    try {
      await saveProduct(formData);
      await loadData();
      resetForm();
      closeDrawer();
    } catch (error) {
      alert(error.message || "Lưu sản phẩm thất bại");
    }
  });
}

function setFormValues(product) {
  const idField = document.getElementById("id");
  if (idField) idField.value = product?.id || "";
  document.getElementById("code").value = product?.sku || product?.code || "";
  document.getElementById("name").value = product?.name || "";
  document.getElementById("categoryId").value =
    product?.category_id ||
    product?.categoryId ||
    els.catSelect?.options[0]?.value ||
    "";
  document.getElementById("desc").value =
    product?.description || product?.desc || "";
  document.getElementById("uom").value = product?.unit || product?.uom || "";
  const marginEl = document.getElementById("margin");
  if (marginEl) marginEl.value = product?.profit_margin ?? product?.margin ?? 0;
  document.getElementById("supplier").value = product?.supplier || "";
  const statusValue =
    product?.status === "stopped" ? "hidden" : product?.status;
  document.getElementById("status").value = statusValue || "selling";

  currentImageData = product?.image || null;
  renderPreview();
  if (els.formTitle)
    els.formTitle.textContent = product?.id ? "Sửa sản phẩm" : "Thêm sản phẩm";
}

function init() {
  addEventListeners();
  loadData();
}

init();
