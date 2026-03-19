
        (function(w, d) {
                /* 1. search ở header -> chuyển sang đúng trang sản phẩm (LINK TƯƠNG ĐỐI) */
                (function initHeaderSearch() {
                    const form = d.querySelector(".search-bar");
                    if (!form) return;

                    function getProductsURL() {
                        const a =
                            d.querySelector("nav.main-nav a.js-products-url") ||
                            d.querySelector('nav.main-nav a[href*="product"]');
                        return a && a.getAttribute("href") ?
                            a.getAttribute("href").trim() :
                            "./product.html";
                    }

                    form.addEventListener(
                        "submit",
                        function(e) {
                            e.preventDefault();
                            const input = form.querySelector(
                                'input[name="query"], input[name="q"]'
                            );
                            const q = ((input && input.value) || "")
                                .trim()
                                .replace(/\s+/g, " ");

                            const raw = getProductsURL(); // ví dụ: "./product.html" hoặc "../product.html"
                            const base = raw.split("?")[0].split("#")[0];
                            const href = q ? base + "?q=" + encodeURIComponent(q) : base;

                            w.location.href = href; // luôn là đường link tương đối
                        }, {
                            passive: false
                        }
                    );
                })(window, document);

                /* 2. chỗ chào mừng click được -> profile */
                (function() {
                    const el =
                        d.querySelector(".welcome-user") ||
                        d.querySelector(
                            "#welcomeName, [data-welcome-name], .js-welcome-name"
                        );
                    if (!el) return;
                    if (el.tagName === "A") {
                        el.setAttribute("href", "../account/profile.html");
                    } else {
                        const a = d.createElement("a");
                        a.href = "../account/profile.html";
                        a.className = "welcome-link";
                        while (el.firstChild) a.appendChild(el.firstChild);
                        el.appendChild(a);
                    }
                })(window, document);

                /* 3. refs */
                const ROOT = d.getElementById("product-grid");
                const PAGING = d.getElementById("pagination");
                const FORM = d.getElementById("searchForm");
                const catSelect = d.getElementById("category");
                const qEl = d.getElementById("q");
                const minEl = d.getElementById("priceMin");
                const maxEl = d.getElementById("priceMax");
                const sortEl = d.getElementById("sort");
                const resetBtn = d.getElementById("svReset");
                const PRODUCT_DETAIL_URL = "./pages/product_detail.html";

                const fmt = (n) => (Number(n) || 0).toLocaleString("vi-VN") + "₫";
                const esc = (s) =>
                    String(s || "").replace(
                        /[&<>"']/g,
                        (m) =>
                        ({
                            "&": "&amp;",
                            "<": "&lt;",
                            ">": "&gt;",
                            '"': "&quot;",
                            "'": "&#39;"
                        }[
                            m
                        ] || m)
                    );

                /* ====== STOCK ====== */
                function getStock(p) {
                    const raw =
                        p.stock ? ? p.qty ? ? p.quantity ? ? p.inventory ? ? p.quantity_in_stock ? ? 0;
                    const n = Number(raw);
                    return isNaN(n) ? 0 : n;
                }

                function isOut(p) {
                    const stock = getStock(p);
                    if (stock <= 0) return true;
                    const st = String(p.status || "").toLowerCase();
                    if (st === "hidden" || st === "inactive") return true;
                    const b = String(p.badge || "").toLowerCase();
                    if (b === "out_of_stock" || b === "oos") return true;
                    return false;
                }

                function badgeLabel(b) {
                    const x = String(b || "").toLowerCase();
                    if (x === "sale") return "Sale";
                    if (x === "new") return "New";
                    if (x === "out_of_stock" || x === "oos") return "Hết hàng";
                    return "";
                }

                /* 4. lấy dữ liệu sản phẩm */
                function getAllProducts() {
                    try {
                        const fromAdmin = JSON.parse(
                            localStorage.getItem("sv_products_v1") || "[]"
                        );
                        if (Array.isArray(fromAdmin) && fromAdmin.length)
                            return fromAdmin.slice();
                    } catch (e) {}
                    return (
                        (w.SVStore && typeof w.SVStore.getAllProducts === "function" ?
                            w.SVStore.getAllProducts() :
                            w.SV_PRODUCT_SEED || []) || []
                    ).slice();
                }

                /* 5. fill danh mục */
                function toSlug(s) {
                    return String(s || "")
                        .trim()
                        .toLowerCase()
                        .normalize("NFD")
                        .replace(/[\u0300-\u036f]/g, "")
                        .replace(/[^a-z0-9]+/g, "-")
                        .replace(/(^-|-$)/g, "");
                }

                function loadCategoriesFromAdmin() {
                    try {
                        const raw = JSON.parse(
                            localStorage.getItem("admin.categories") || "[]"
                        );
                        return raw
                            .filter((c) => c && (c.active ? ? true))
                            .map((c) => ({
                                slug: c.name === "Sáp vuốt tóc" ?
                                    "hair_wax" : c.name === "Gôm xịt" ?
                                    "hair_spray" : c.name === "Bột tạo phồng" ?
                                    "volumizing_powder" : toSlug(c.name),
                                name: c.name,
                            }));
                    } catch {
                        return [];
                    }
                }

                function loadCategoriesFromProducts() {
                    const set = new Set();
                    getAllProducts().forEach((p) => {
                        const c = String(p.category || "").trim();
                        if (c) set.add(c);
                    });
                    return [...set].map((slug) => ({
                        slug,
                        name: slug
                            .replace(/[-_]+/g, " ")
                            .replace(/\b\w/g, (c) => c.toUpperCase()),
                    }));
                }

                function fillCategorySelect(keepCurrent = true) {
                    if (!catSelect) return;
                    const current = catSelect.value;
                    const list = loadCategoriesFromAdmin();
                    const data = list.length ? list : loadCategoriesFromProducts();
                    let html = '<option value="all">Tất cả</option>';
                    data.forEach((c) => {
                        html += `<option value="${c.slug}">${c.name}</option>`;
                    });
                    catSelect.innerHTML = html;
                    if (
                        keepCurrent &&
                        (current === "all" || data.some((c) => c.slug === current))
                    )
                        catSelect.value = current;
                    else catSelect.value = "all";
                }
                fillCategorySelect(true);

                /* 6. render 1 card */
                function renderCard(p) {
                    const out = isOut(p);
                    const badge = badgeLabel(p.badge);
                    const hasOld =
                        p.original_price && Number(p.original_price) > Number(p.price);
                    const img =
                        p.image || p.thumbnail || "https://placehold.co/600x600/png";
                    const stock = getStock(p);
                    let stockHtml = "";
                    if (out) {
                        stockHtml =
                            '<div class="product-stock"><span class="stock-out"><i class="fa-regular fa-circle-xmark"></i> Hết hàng</span></div>';
                    } else if (stock <= 5) {
                        stockHtml = `<div class="product-stock"><span class="stock-low"><i class="fa-solid fa-triangle-exclamation"></i> Còn ${stock} sp · Sắp hết</span></div>`;
                    } else {
                        stockHtml = `<div class="product-stock"><span class="stock-in"><i class="fa-regular fa-circle-check"></i> Còn ${stock} sp</span></div>`;
                    }

                    return `
            <div class="col col-3">
              <div class="product-box ${out ? "is-out" : ""}">
                <div class="product-thumbnail">
                  <a class="image-link" href="${PRODUCT_DETAIL_URL}?id=${encodeURIComponent(
                    p.id
                  )}">
                    <img src="${esc(img)}" alt="${esc(p.name)}" />
                  </a>
                  ${
                    badge || out
                      ? `<div class="product-label">
                          ${
                            badge
                              ? `<span class="label">${esc(badge)}</span>`
                              : ""
                          }
                          ${
                            out
                              ? `<span class="label label-oos">Hết hàng</span>`
                              : ""
                          }
                        </div>`
                      : ""
                  }
                  <div class="product-action-grid">
                    ${
                      out
                        ? `<button class="btn-cart" disabled>Hết hàng</button>`
                        : `<button class="btn-cart btn-add-cart" data-id="${esc(
                            p.id
                          )}"><i class="fa fa-plus"></i> Giỏ hàng</button>`
                    }
                  </div>
                </div>
                <div class="product-info a-left">
                  <h3 class="product-name">
                    <a href="${PRODUCT_DETAIL_URL}?id=${encodeURIComponent(
                      p.id
                    )}">${esc(p.name)}</a>
                  </h3>
                  <div class="price-box">
                    <span class="price product-price">${fmt(p.price)}</span>
                    ${
                      hasOld
                        ? `<span class="price product-price-old">${fmt(
                            p.original_price
                          )}</span>`
                        : ""
                    }
                  </div>
                  ${stockHtml}
                </div>
              </div>
            </div>
          `;
        }

        /* 7. lọc + phân trang */
        const state = { view: [], page: 1, per: 8 };
        function normalizeText(str) {
  return String(str || "")
    .trim()
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, ""); // bỏ dấu tiếng Việt
}
        function applyFilters(e) {
  if (e) e.preventDefault();
  const all = getAllProducts();

  // LẤY KEYWORD TỪ Ô TÌM KIẾM
  const rawQ = (qEl?.value || "").trim().replace(/\s+/g, " ");
  const qNormalized = normalizeText(rawQ);

  const cat = catSelect.value || "all";
  const min = Number(minEl.value || 0);
  const max = Number(maxEl.value || 0);
  const sort = sortEl.value || "";

  let view = all.filter((p) => {
    const nameNormalized = normalizeText(p.name);

    // 1️⃣ Nếu có nhập từ khoá → tên phải bắt đầu bằng keyword
    if (qNormalized && !nameNormalized.startsWith(qNormalized)) return false;

    // 2️⃣ Lọc theo category
    if (cat !== "all" && String(p.category || "") !== cat) return false;

    // 3️⃣ Lọc theo giá
    if (min && Number(p.price) < min) return false;
    if (max && Number(p.price) > max) return false;

    return true;
  });

  // Sắp xếp
  if (sort === "price-asc")
    view.sort((a, b) => Number(a.price) - Number(b.price));
  else if (sort === "price-desc")
    view.sort((a, b) => Number(b.price) - Number(a.price));
  else if (sort === "name-asc")
    view.sort((a, b) =>
      String(a.name || "").localeCompare(String(b.name || ""), "vi")
    );
  else if (sort === "name-desc")
    view.sort((a, b) =>
      String(b.name || "").localeCompare(String(a.name || ""), "vi")
    );

  // ĐẨY FILTER LÊN URL (DÙNG rawQ CHỨ KHÔNG DÙNG q)
  const u = new URL(location.href);
  if (rawQ) u.searchParams.set("q", rawQ);
  else u.searchParams.delete("q");

  cat !== "all"
    ? u.searchParams.set("category", cat)
    : u.searchParams.delete("category");
  min
    ? u.searchParams.set("priceMin", String(min))
    : u.searchParams.delete("priceMin");
  max
    ? u.searchParams.set("priceMax", String(max))
    : u.searchParams.delete("priceMax");
  sort
    ? u.searchParams.set("sort", sort)
    : u.searchParams.delete("sort");

  history.replaceState({}, "", u.pathname + (u.search ? u.search : ""));

  state.view = view;
  state.page = 1;
  renderPage();
}


        function renderPagination(total, cur, per) {
          if (!PAGING) return;
          const pages = Math.max(1, Math.ceil(total / per));
          if (pages <= 1) {
            PAGING.innerHTML = "";
            PAGING.parentElement.style.display = "none";
            return;
          }
          PAGING.parentElement.style.display = "flex";

          const li = (p, label, active = false, disabled = false) => {
            const cls = [
              "page-item",
              active ? "active" : "",
              disabled ? "disabled" : "",
            ]
              .filter(Boolean)
              .join(" ");
            return `<li class="${cls}"><a class="page-link" href="#" ${
              disabled ? "" : `data-page="${p}"`
            }>${label}</a></li>`;
          };

          let html = "";
          html += li(cur - 1, "‹", false, cur === 1);

          const win = 2;
          let from = Math.max(1, cur - win);
          let to = Math.min(pages, cur + win);

          if (from > 1) {
            html += li(1, "1", cur === 1);
            if (from > 2)
              html +=
                '<li class="page-item disabled"><span class="page-link">…</span></li>';
          }
          for (let i = from; i <= to; i++) {
            html += li(i, String(i), i === cur);
          }
          if (to < pages) {
            if (to < pages - 1)
              html +=
                '<li class="page-item disabled"><span class="page-link">…</span></li>';
            html += li(pages, String(pages), cur === pages);
          }
          html += li(cur + 1, "›", false, cur === pages);
          PAGING.innerHTML = html;
        }

        function renderPage() {
          const { view, page, per } = state;
          const pages = Math.max(1, Math.ceil(view.length / per));
          const cur = Math.min(Math.max(1, page), pages);
          const start = (cur - 1) * per;
          const slice = view.slice(start, start + per);
          ROOT.innerHTML = slice.map(renderCard).join("");
          renderPagination(view.length, cur, per);
          state.page = cur;
        }
                // BẮT SỰ KIỆN CLICK PHÂN TRANG
        if (PAGING) {
          PAGING.addEventListener("click", function (e) {
            const link = e.target.closest(".page-link[data-page]");
            if (!link) return;

            e.preventDefault();

            const p = parseInt(link.getAttribute("data-page"), 10);
            if (!p || p === state.page) return;

            state.page = p;
            renderPage();

            // Scroll lên lưới sản phẩm cho đẹp
            if (ROOT) {
              const top = ROOT.getBoundingClientRect().top + window.scrollY - 100;
              window.scrollTo({ top, behavior: "smooth" });
            }
          });
        }


        /* 8. init từ URL */
        (function initFilters() {
          const u = new URL(location.href);
          if (qEl) qEl.value = u.searchParams.get("q") || "";
          if (catSelect)
            catSelect.value = u.searchParams.get("category") || "all";
          if (minEl) minEl.value = u.searchParams.get("priceMin") || "";
          if (maxEl) maxEl.value = u.searchParams.get("priceMax") || "";
          if (sortEl) sortEl.value = u.searchParams.get("sort") || "";

          FORM?.addEventListener("submit", applyFilters);
          sortEl?.addEventListener("change", applyFilters);
          resetBtn?.addEventListener("click", () => {
            qEl.value = "";
            catSelect.value = "all";
            minEl.value = "";
            maxEl.value = "";
            sortEl.value = "";
            FORM.dispatchEvent(new Event("submit", { cancelable: true }));
          });

          applyFilters();
        })();

        /* 9. ADD TO CART – GIỚI HẠN = TỒN KHO */
        function getCurrentUserEmail() {
          try {
            if (w.AUTH?.getCurrentUser)
              return w.AUTH.getCurrentUser()?.email || null;
            if (w.AUTH?.currentUser) return w.AUTH.currentUser.email || null;
          } catch {}
          return null;
        }
        function redirectToLogin() {
          const back = location.pathname + location.search + location.hash;
          location.href =
            "../account/login.html?redirect=" + encodeURIComponent(back);
        }

        function getProductById(id) {
          const list = getAllProducts();
          return list.find((p) => String(p.id) === String(id)) || null;
        }

        // Lấy số lượng hiện có trong giỏ cho 1 sản phẩm
        function getCartQty(id) {
          try {
            if (w.SVStore?.getCart) {
              const cart = w.SVStore.getCart() || [];
              const f = cart.find(
                (i) => String(i.id ?? i.productId) === String(id)
              );
              if (f) return Number(f.qty ?? f.quantity ?? 0) || 0;
            }
          } catch {}
          // fallback: theo email
          try {
            const email = getCurrentUserEmail();
            if (email) {
              const key = "sv_cart_user_" + email.toLowerCase();
              const cart = JSON.parse(localStorage.getItem(key) || "[]");
              const f = cart.find(
                (i) => String(i.id ?? i.productId) === String(id)
              );
              if (f) return Number(f.qty ?? f.quantity ?? 0) || 0;
            }
          } catch {}
          return 0;
        }

        // Thêm 1 sp vào giỏ nhưng không vượt quá tồn kho
        function addToCartWithCheck(id) {
          const p = getProductById(id);
          if (!p) {
            alert("Sản phẩm không tồn tại hoặc đã bị xoá.");
            return false;
          }
          const stock = getStock(p);
          if (stock <= 0) {
            alert("Sản phẩm đã hết hàng.");
            return false;
          }
          const inCart = getCartQty(id);
          if (inCart >= stock) {
            alert("Bạn đã thêm tối đa số lượng hiện có.");
            return false;
          }

          // Gọi API giỏ hàng chung
          if (w.SVStore?.addToCart) {
            w.SVStore.addToCart(id, 1);
          } else if (w.SVCart?.add) {
            w.SVCart.add(id, 1);
          } else {
            alert("Chưa cấu hình giỏ hàng.");
            return false;
          }
          return true;
        }

        d.addEventListener(
          "click",
          function (e) {
            const btn = e.target.closest(
              ".btn-add-cart, .btn-cart, [data-add-to-cart], .js-add-to-cart, a.quick-add"
            );
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation(); // chặn handler add-to-cart khác (trong ui.js) chạy thêm lần nữa

            const email = getCurrentUserEmail();
            const logged =
              email ||
              (w.AUTH && (w.AUTH.loggedIn || w.AUTH.isAuthenticated?.()));
            if (!logged) {
              alert("Vui lòng đăng nhập để thêm sản phẩm vào giỏ!");
              redirectToLogin();
              return;
            }

            let id = btn.dataset.id || "";
            if (!id) {
              const href = btn.getAttribute("href") || "";
              if (href.includes("id=")) {
                try {
                  id = new URL(href, location.origin).searchParams.get("id") || "";
                } catch {}
              }
            }
            if (!id) return;

            const before = getCartQty(id); // trước khi thêm
            const ok = addToCartWithCheck(id); // có thể ALERT & không thêm nếu đã max
            const after = getCartQty(id); // sau khi thử thêm

            if (!ok || after <= before) {
              // hoặc lỗi, hoặc đã max → không show "Đã thêm"
              return;
            }

            // UI feedback: chỉ khi THỰC SỰ thêm được
            const prev = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-check"></i> Đã thêm';
            setTimeout(() => {
              btn.disabled = false;
              btn.innerHTML = prev;
            }, 900);
          },
          true
        );

        // cập nhật khi storage đổi
        w.addEventListener("storage", (e) => {
          if (e.key && e.key.startsWith("sv_cart_user_")) {
            w.SVUI?.updateCartCount?.();
          }
          if (
            e.key === "sv_products_v1" ||
            e.key === "catalog.bump" ||
            e.key === "admin.categories"
          ) {
            fillCategorySelect(true);
            applyFilters();
          }
        });

        w.addEventListener("cart:changed", () => w.SVUI?.updateCartCount?.());
      })(window, document);
    