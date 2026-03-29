// product.detail.js (User) — đọc dữ liệu từ Admin + tự cập nhật khi Admin lưu
(function () {
  // ====== Utils chung ======
  const PUBLIC_CATALOG_KEY = "sv_products_v1";
  const BUMP_KEY = "catalog.bump";

  function qs(sel) {
    return document.querySelector(sel);
  }
  function escapeHtml(s = "") {
    return String(s).replace(
      /[&<>"']/g,
      (m) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        }[m])
    );
  }
  function labelize(k) {
    return k.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
  }
  function fmtVND(n) {
    return Number(n || 0).toLocaleString("vi-VN") + "₫";
  }

  function getAppRoot() {
    var script = document.currentScript || document.scripts[document.scripts.length - 1];
    if (!script || !script.src) return new URL("./", location.href);
    var url = new URL(script.src, location.origin);
    url.pathname = url.pathname.replace(/\/assets\/js\/[^/]+$/, "/");
    return url;
  }

  const APP_ROOT = getAppRoot();

  // Lấy param id
  const usp = new URLSearchParams(location.search);
  const routeId = usp.get("id") || "";

  // Format tiền: ưu tiên của site nếu có
  const fmt = window.SVStore?.fmtVND ? window.SVStore.fmtVND : fmtVND;

  function safeParse(json, def = []) {
    try {
      return JSON.parse(json);
    } catch {
      return def;
    }
  }

  // ====== Nguồn dữ liệu ======
  // Ưu tiên catalog Admin; fallback sang SVStore.getAllProducts() hoặc seed tĩnh
  function getCatalog() {
    const fromAdmin = safeParse(localStorage.getItem(PUBLIC_CATALOG_KEY), []);
    if (Array.isArray(fromAdmin) && fromAdmin.length) return fromAdmin;

    const fromStore = window.SVStore?.getAllProducts?.() || [];
    if (Array.isArray(fromStore) && fromStore.length) return fromStore;

    const fromSeed = window.SV_PRODUCT_SEED || [];
    return Array.isArray(fromSeed) ? fromSeed : [];
  }

  // Tìm sản phẩm theo id:
  //   - Trùng id (kể cả "admin-123")
  //   - Fallback: trùng code/specs.Mã (cho link cũ kiểu ?id=SP001)
  function findByParamId(catalog, idParam) {
    if (!idParam) return null;

    // 1) match thẳng theo id
    let p = catalog.find((x) => String(x.id) === String(idParam));
    if (p) return p;

    // 2) fallback theo code/specs.Mã
    const lower = String(idParam).toLowerCase();
    p = catalog.find((x) => {
      const code = (x.code || (x.specs && (x.specs.Mã || x.specs["Mã"])) || "")
        .toString()
        .toLowerCase();
      return code && code === lower;
    });
    return p || null;
  }

  // Ghép mô tả cho tương thích nhiều schema
  function pickDescription(p) {
    return p.long_desc || p.short_desc || p.description || "";
  }

  // ====== Render ======
  const root = document.getElementById("pd-root");
  const bcName = document.getElementById("bc-name");

  // Notification System
  function showNotification(message, type = 'info') {
    // Chỉ show trên trang product detail, không show trên cart.php
    const isCartPage = /\/cart\.php(?:$|[?#])/i.test(location.pathname);
    if (isCartPage) return;
    
    // type: 'success', 'error', 'warning', 'info'
    let notifContainer = document.getElementById('pd-notification-container');
    if (!notifContainer) {
      notifContainer = document.createElement('div');
      notifContainer.id = 'pd-notification-container';
      notifContainer.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;';
      document.body.appendChild(notifContainer);
    }

    const notif = document.createElement('div');
    const bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : type === 'warning' ? '#ffc107' : '#17a2b8';
    const textColor = type === 'warning' ? '#000' : '#fff';
    
    notif.style.cssText = `
      background: ${bgColor};
      color: ${textColor};
      padding: 14px 18px;
      border-radius: 6px;
      margin-bottom: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      animation: slideInRight 0.3s ease-out;
      font-size: 14px;
      line-height: 1.4;
    `;
    notif.textContent = message;
    notifContainer.appendChild(notif);

    setTimeout(() => {
      notif.style.animation = 'slideOutRight 0.3s ease-out forwards';
      setTimeout(() => notif.remove(), 300);
    }, 4000);
  }

  // Thêm CSS animation nếu chưa có
  if (!document.getElementById('pd-notification-styles')) {
    const style = document.createElement('style');
    style.id = 'pd-notification-styles';
    style.textContent = `
      @keyframes slideInRight {
        from { transform: translateX(450px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(450px); opacity: 0; }
      }
    `;
    document.head.appendChild(style);
  }

  function renderSpecs(p) {
    const specs = p.specs || {};
    const keys = Object.keys(specs);
    if (!keys.length) return "";
    return `
      <h6 class="mt-3">Thông số</h6>
      <dl class="row specs">
        ${keys
          .map(
            (k) => `
          <dt class="col-sm-4">${escapeHtml(labelize(k))}</dt>
          <dd class="col-sm-8">${escapeHtml(String(specs[k]))}</dd>
        `
          )
          .join("")}
      </dl>
    `;
  }

  function renderProduct(p) {
    if (!root) return;

    if (!p) {
      root.innerHTML =
        '<div class="col-12"><div class="alert alert-warning">Không tìm thấy sản phẩm.</div></div>';
      if (bcName) bcName.textContent = "Không tìm thấy";
      return;
    }

    // Breadcrumb name
    if (bcName) bcName.textContent = p.name || "";

    // Badge
    const badge = String(p.badge || "").toLowerCase();
    const stockRaw = Number(p.quantity ?? p.stock);
    const stock = Number.isFinite(stockRaw) ? Math.max(0, Math.floor(stockRaw)) : null;
    const badgeHtml =
      badge === "sale"
        ? '<span class="pd-badge sale">Sale</span>'
        : badge === "new"
        ? '<span class="pd-badge new">Mới</span>'
        : badge === "oos" || badge === "out_of_stock"
        ? '<span class="pd-badge oos">Hết hàng</span>'
        : "";

    // Ảnh chính + thumbs
    const images =
      Array.isArray(p.images) && p.images.length
        ? p.images
        : p.image
        ? [p.image]
        : [];

    root.innerHTML = `
      <div class="col-md-5">
        <img id="mainImg" src="${images[0] || ""}" alt="${escapeHtml(
      p.name || ""
    )}" class="img-fluid rounded border">
        <div class="thumbs mt-2 d-flex">
          ${images
            .map(
              (src) =>
                `<img data-src="${src}" src="${src}" alt="${escapeHtml(
                  p.name || ""
                )}">`
            )
            .join("")}
        </div>
      </div>

      <div class="col-md-7">
        <div class="mb-2">${badgeHtml}</div>
        <h1 class="pd-title">${escapeHtml(p.name || "")}</h1>

        <div class="mb-2 text-muted">Mã: ${escapeHtml(String(p.id || ""))}</div>
        <div class="pd-price mb-3">
          <span class="new">${fmt(p.price)}</span>
          ${
            p.original_price
              ? `<span class="old">${fmt(p.original_price)}</span>`
              : ""
          }
        </div>

        <div class="mb-3 desc">${
          escapeHtml(pickDescription(p)) || "Đang cập nhật mô tả..."
        }</div>

        ${renderSpecs(p)}

        <div class="pd-actions">
          <input id="qty" type="number" min="1" ${stock !== null ? `max="${stock}"` : ""} value="1" class="form-control pd-qty" style="width:110px">
          <button id="btnAdd" class="btn btn-primary"
            ${badge === "oos" || badge === "out_of_stock" || stock === 0 ? "disabled" : ""}>
            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
          </button>
        </div>

        <div class="pd-trust">
          <span><i class="fa-regular fa-circle-check"></i> Hàng chính hãng</span>
          <span><i class="fa-regular fa-clock"></i> Đổi trả 7 ngày</span>
          <span><i class="fa-solid fa-truck"></i> Giao nhanh nội thành</span>
        </div>
      </div>
    `;

    // Thumbs click → đổi ảnh lớn
    root.querySelectorAll(".thumbs img").forEach((t) => {
      t.addEventListener("click", () => {
        const main = document.getElementById("mainImg");
        if (main) main.src = t.dataset.src;
        root
          .querySelectorAll(".thumbs img")
          .forEach((i) => i.classList.remove("active"));
        t.classList.add("active");
      });
    });

    // ===== THÊM VÀO GIỎ - BẮT BUỘC ĐĂNG NHẬP =====
    document.getElementById("btnAdd")?.addEventListener("click", () => {
      // ✅ Kiểm tra đăng nhập
      if (!window.AUTH?.loggedIn) {
        showNotification("Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!", 'error');
        const back = location.pathname + location.search + location.hash;
        const loginUrl = window.AUTH?.LOGIN_URL || (function () {
          const p = (location.pathname || "").toLowerCase();
          if (p.indexOf("/account/") !== -1) return "login.php";
          if (p.indexOf("/product/pages/") !== -1) return "../../account/login.php";
          if (p.indexOf("/product/") !== -1 || p.indexOf("/news_section/") !== -1) return "../account/login.php";
          return "./account/login.php";
        })();
        setTimeout(() => {
          location.href = loginUrl + "?redirect=" + encodeURIComponent(back);
        }, 1500);
        return;
      }

      const qty = Math.max(
        1,
        Number(document.getElementById("qty").value) || 1
      );

      const currentInCart = window.SVStore?.getCartItemQty?.(p.id) || 0;
      const maxStock = window.SVStore?.getProductStockById?.(p.id);
      console.log('[ProductDetail] add button: stock=' + maxStock + ', currentInCart=' + currentInCart + ', requesting=' + qty);
      
      if (Number.isFinite(maxStock)) {
        const remain = Math.max(0, maxStock - currentInCart);
        if (remain <= 0) {
          showNotification("Bạn đã thêm tối đa theo tồn kho của sản phẩm này.\nTồn kho còn: " + maxStock + " | Bạn đã có: " + currentInCart, 'warning');
          return;
        }
        if (qty > remain) {
          showNotification("Không thể thêm " + qty + " cái.\nTồn kho còn: " + remain + " | Bạn đã có: " + currentInCart, 'warning');
          return;
        }
      }

      // Gọi API giỏ hàng (đã có auth check bên trong)
      if (window.SVStore?.addToCart) {
        window.SVStore.addToCart(p.id, qty);
      }

      const op = window.SVStore?.getLastCartOp?.();
      console.log('[ProductDetail] after add, op=', op);
      
      if (op && op.addedQty > 0) {
        // Success: items were added
        if (op.message) {
          showNotification(op.message, 'success');
        }
      } else if (op && op.addedQty <= 0) {
        // Error: nothing was added
        if (op.message) {
          showNotification(op.message, 'error');
        }
        return;
      }

      // Cập nhật badge header
      window.dispatchEvent(new CustomEvent("cart:changed"));
      window.SVUI?.updateCartCount?.();

      // Feedback
      const btn = document.getElementById("btnAdd");
      const prev = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-check"></i> Đã thêm';
      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = prev;
      }, 900);
    });

    // Sản phẩm liên quan
    const all = getCatalog();
    const related = all
      .filter((x) => x.category === p.category && x.id !== p.id)
      .slice(0, 3);
    const relBox = document.getElementById("pd-related");
    if (relBox) {
      relBox.innerHTML = related
        .map(
          (r) => `
        <div class="col-6 col-md-4 related-col">
          <a href="${new URL(
            `../Product/pages/product_detail.php?id=${encodeURIComponent(r.id)}`,
            APP_ROOT
          ).pathname}" class="related-card text-reset">
            <div class="img-wrap">
              <img src="${r.image || ""}" alt="${escapeHtml(r.name || "")}">
            </div>
            <div class="related-body">
              <div class="related-name">${escapeHtml(r.name || "")}</div>
              <div class="related-price">${fmt(r.price)}</div>
            </div>
          </a>
        </div>
      `
        )
        .join("");
    }
  }

  // ====== Khởi tạo lần đầu ======
  function initOnce() {
    const catalog = getCatalog();
    const p = findByParamId(catalog, routeId);

    if (!p) {
      renderProduct(null);
      return;
    }
    renderProduct(p);
  }
  initOnce();

  // ====== Tự cập nhật khi Admin lưu (storage event) ======
  window.addEventListener("storage", (e) => {
    // 1) catalog.bump đổi: Admin vừa syncToStorefront -> reload & render lại
    if (e.key === BUMP_KEY) {
      const catalog = getCatalog();
      const p = findByParamId(catalog, routeId);
      renderProduct(p);
    }
    // 2) đồng bộ badge giỏ hàng (hành vi cũ)
    if (e.key && e.key.startsWith("sv_cart_user_")) {
      window.SVUI?.updateCartCount?.();
    }
  });
  window.addEventListener("cart:changed", () =>
    window.SVUI?.updateCartCount?.()
  );
  document.addEventListener("DOMContentLoaded", () =>
    window.SVUI?.updateCartCount?.()
  );
})();

