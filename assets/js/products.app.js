(function () {
  if (window.SV_DISABLE_LISTING_APP) return;

  // ====== Config ======
  const PAGE_SIZE = 8;

  // ====== Notification System ======
  function showNotification(message, type = 'info') {
    // Chỉ show trên trang product.php, không show trên cart.php
    const isCartPage = /\/cart\.php(?:$|[?#])/i.test(location.pathname);
    if (isCartPage) return;
    
    // type: 'success', 'error', 'warning', 'info'
    let notifContainer = document.getElementById('products-notification-container');
    if (!notifContainer) {
      notifContainer = document.createElement('div');
      notifContainer.id = 'products-notification-container';
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
  if (!document.getElementById('products-notification-styles')) {
    const style = document.createElement('style');
    style.id = 'products-notification-styles';
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

  // ====== Utils DOM & Format ======
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const moneyVND = (n) =>
    (n ?? 0).toLocaleString("vi-VN", {
      style: "currency",
      currency: "VND",
      maximumFractionDigits: 0,
    });
  const stripVN = (str = "") =>
    str
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase();

  // ====== URL query helpers ======
  const getQuery = (k, def = "") => {
    const u = new URL(location.href);
    return u.searchParams.get(k) ?? def;
  };
  const setQuery = (obj) => {
    const u = new URL(location.href);
    Object.entries(obj).forEach(([k, v]) => {
      if (v === null || v === "" || v === undefined) u.searchParams.delete(k);
      else u.searchParams.set(k, v);
    });
    history.replaceState({}, "", u.toString());
  };

  // ====== Data ======
  function getAllProducts() {
    if (window.SVStore?.getAllProducts && typeof window.SVStore.getAllProducts === "function") {
      try {
        const products = window.SVStore.getAllProducts();
        if (Array.isArray(products)) return products;
      } catch (err) {
        console.warn("Error loading products from SVStore:", err);
      }
    }
    return Array.isArray(window.SV_PRODUCT_SEED) ? window.SV_PRODUCT_SEED : [];
  }

  // Badge giỏ ở header
  function updateCartBadge() {
    const el = document.querySelector("#cartCount, .cart-count");
    if (!el) return;
    const isCartPage = /\/cart\.php(?:$|[?#])/i.test(location.pathname + location.search);
    const cartLooksEmpty = !!document.querySelector('.alert-warning') && document.querySelectorAll('.cart-item-row').length === 0;
    let count = 0;

    if (typeof window.SERVER_CART_COUNT === 'number') {
      count = Math.max(0, Number(window.SERVER_CART_COUNT) || 0);
    } else if (window.SVStore?.count) {
      count = SVStore.count();
    }

    if (isCartPage && cartLooksEmpty) {
      count = 0;
      window.SERVER_CART_COUNT = 0;
    }
    el.textContent = count;
  }

  // ====== UI build ======
  function badgeHTML(badge) {
    if (!badge) return "";
    const map = {
      sale: { cls: "badge-sale", text: "Sale" },
      new: { cls: "badge-sale", text: "Mới" },
      oos: { cls: "badge-out-of-stock", text: "Hết hàng" },
      out_of_stock: { cls: "badge-out-of-stock", text: "Hết hàng" },
    };
    const meta = map[String(badge).toLowerCase()] || null;
    return meta
      ? `<span class="product-badge ${meta.cls}">${meta.text}</span>`
      : "";
  }

  function isOutOfStock(p) {
    const b = String(p.badge || "").toLowerCase();
    return (
      b === "oos" ||
      b === "out_of_stock" ||
      (typeof p.stock === "number" && p.stock <= 0)
    );
  }

  function getAppRoot() {
    var script = document.currentScript || document.scripts[document.scripts.length - 1];
    if (!script || !script.src) return new URL("./", location.href);
    var url = new URL(script.src, location.origin);
    url.pathname = url.pathname.replace(/\/assets\/js\/[^/]+$/, "/");
    return url;
  }

  const APP_ROOT = getAppRoot();
  function normalizeAssetUrl(src) {
    if (!src) return src;
    if (/^(?:https?:)?\/\//.test(src) || src.startsWith("/")) return src;
    try {
      return new URL(src, APP_ROOT).pathname;
    } catch (e) {
      return src;
    }
  }
  function productDetailUrl(p) {
    return new URL(
      `../Product/pages/product_detail.php?id=${encodeURIComponent(p.id)}`,
      APP_ROOT
    ).pathname;
  }

  function itemHTML(p) {
    const ori =
      p.original_price && Number(p.original_price) > Number(p.price)
        ? `<span class="original-price">${moneyVND(p.original_price)}</span>`
        : "";

    const disabled = isOutOfStock(p) ? "disabled" : "";
    const btnText = isOutOfStock(p) ? "Hết hàng" : "Thêm giỏ";

    return `
      <div class="col-lg-3 col-md-4 col-sm-6 col-6">
        <div class="product-item">
          <a href="${productDetailUrl(p)}">
            <div class="product-image">
              ${badgeHTML(p.badge)}
              <img src="${normalizeAssetUrl(p.image)}" alt="${p.name}">
            </div>
            <div class="product-name">${p.name}</div>
            <div class="product-price">
              <span class="sale-price">${moneyVND(p.price)}</span>
              ${ori}
            </div>
          </a>
          <div class="mt-2">
            <button class="btn btn-sm btn-dark btn-add-cart" data-id="${
              p.id
            }" ${disabled}>
              <i class="fas fa-cart-plus"></i> ${btnText}
            </button>
          </div>
        </div>
      </div>
    `;
  }

  // Lọc theo: q (tên), category, minprice, maxprice
  function filterProducts(all) {
    const q = (getQuery("q", "") || "").trim();
    const category = (getQuery("category", "all") || "all").toLowerCase();
    const minprice = Number(getQuery("minprice", "")) || null;
    const maxprice = Number(getQuery("maxprice", "")) || null;

    const qNorm = stripVN(q);

    let result = all;

    if (q) {
      result = result.filter((p) => {
        // Chuẩn hóa tên: bỏ dấu, thường hóa, loại ký tự đặc biệt, trim
        let nameNorm = stripVN(p.name || '').replace(/[^a-z0-9 ]/g, ' ').replace(/\s+/g, ' ').trim();
        if (!nameNorm) return false;
        // Tách từ bằng khoảng trắng
        const words = nameNorm.split(' ');
        return words.some(word => word && word.startsWith(qNorm));
      });
    }
    if (category !== "all") {
      result = result.filter(
        (p) => (p.category || "").toLowerCase() === category
      );
    }
    result = result.filter((p) => {
      const price = Number(p.price || 0);
      if (minprice !== null && price < minprice) return false;
      if (maxprice !== null && price > maxprice) return false;
      return true;
    });

    return { result, q, category, minprice, maxprice };
  }

  function buildPagination(pages, current) {
    const buildHref = (p) => {
      const params = new URLSearchParams();
      const q = getQuery("q", "");
      const category = getQuery("category", "all");
      const minprice = getQuery("minprice", "");
      const maxprice = getQuery("maxprice", "");

      if (q) params.set("q", q);
      if (category && category !== "all") params.set("category", category);
      if (minprice) params.set("minprice", minprice);
      if (maxprice) params.set("maxprice", maxprice);
      params.set("page", String(p));
      return `?${params.toString()}`;
    };

    const li = (label, p, active = false, disabled = false) =>
      active
        ? `<li class="page-item active"><span class="page-link">${label}</span></li>`
        : `<li class="page-item${
            disabled ? " disabled" : ""
          }"><a class="page-link" href="${buildHref(p)}">${label}</a></li>`;

    let html = "";
    html += li("«", Math.max(1, current - 1), false, current === 1);
    for (let i = 1; i <= pages; i++) html += li(String(i), i, i === current);
    html += li("»", Math.min(pages, current + 1), false, current === pages);
    return html;
  }

  function render(all, page, pageSize) {
    const row = $(".product-list .row");
    if (!row) return;

    const { result: filtered } = filterProducts(all);
    const sorted = filtered;

    const total = sorted.length;
    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    const cur = Math.min(Math.max(1, page), totalPages);
    const start = (cur - 1) * pageSize;
    const pageItems = sorted.slice(start, start + pageSize);

    row.innerHTML = pageItems.length
      ? pageItems.map(itemHTML).join("")
      : `<div class="col-12 py-5 text-center text-muted">Không tìm thấy sản phẩm phù hợp.</div>`;

    const info = $("#categoryInfo");
    if (info) {
      info.textContent = `${total} sản phẩm`;
    }

    const pag = $(".pagination-list");
    if (pag) {
      pag.innerHTML = buildPagination(totalPages, cur);

      pag.addEventListener(
        "click",
        function (e) {
          const a = e.target.closest("a.page-link");
          if (!a) return;
          e.preventDefault();
          const u = new URL(a.href, location.origin);
          const next = Number(u.searchParams.get("page") || "1");
          setQuery(Object.fromEntries(u.searchParams.entries()));
          render(all, next, pageSize);
          window.scrollTo({ top: 0, behavior: "smooth" });
        },
        { once: true }
      );
    }

    updateCartBadge();
  }

  function boot() {
    const all = getAllProducts();

    const form = $("#searchForm");
    if (form) {
      if (form.q) form.q.value = getQuery("q", "");
      if (form.category)
        form.category.value = getQuery("category", "all") || "all";
      if (form.minprice) form.minprice.value = getQuery("minprice", "");
      if (form.maxprice) form.maxprice.value = getQuery("maxprice", "");

      form.addEventListener("submit", (e) => {
        e.preventDefault();
        const q = (form.q?.value || "").trim();
        const category = form.category ? form.category.value : "all";
        const minprice =
          form.minprice && form.minprice.value
            ? Number(form.minprice.value)
            : null;
        const maxprice =
          form.maxprice && form.maxprice.value
            ? Number(form.maxprice.value)
            : null;

        setQuery({
          q: q || null,
          category: category || "all",
          minprice: minprice != null ? String(minprice) : null,
          maxprice: maxprice != null ? String(maxprice) : null,
          page: "1",
        });
        render(all, 1, PAGE_SIZE);
      });
    }

    // === Event: Thêm giỏ - BẮT BUỘC ĐĂNG NHẬP ===
    document.addEventListener(
      "click",
      function (e) {
        const btn = e.target.closest(".btn-add-cart");
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === "function")
          e.stopImmediatePropagation();

        // ✅ KIỂM TRA ĐĂNG NHẬP
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

        if (btn.disabled || btn.dataset.busy === "1") return;

        const id = btn.dataset.id || btn.getAttribute("data-id");
        let qty = parseInt(btn.dataset.qty || "1", 10);
        if (!id) return;
        if (!Number.isFinite(qty) || qty < 1) qty = 1;

        btn.dataset.busy = "1";

        // Gọi API giỏ hàng (đã kiểm tra auth bên trong)
        if (window.SVStore?.addToCart) {
          window.SVStore.addToCart(id, qty);
        }

        const op = window.SVStore?.getLastCartOp?.();
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
          btn.dataset.busy = "";
          updateCartBadge();
          window.SVUI?.updateCartCount?.();
          return;
        }

        updateCartBadge();
        window.SVUI?.updateCartCount?.();

        const prev = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-check"></i> Đã thêm`;
        setTimeout(() => {
          btn.disabled = false;
          btn.innerHTML = prev;
          btn.dataset.busy = "";
        }, 800);
      },
      true
    );

    // đồng bộ badge nếu giỏ đổi từ tab khác và tải lại danh sách khi Admin cập nhật catalog
    window.addEventListener("storage", (e) => {
      if (e.key === "catalog.bump") {
        const fresh = getAllProducts();
        const page = Number(getQuery("page", "1")) || 1;
        render(fresh, page, PAGE_SIZE);
      }
      if (e.key && e.key.startsWith("sv_cart_user_")) updateCartBadge();
    });

    window.addEventListener("cart:changed", updateCartBadge);

    const page = Number(getQuery("page", "1")) || 1;
    render(all, page, PAGE_SIZE);
  }

  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();

