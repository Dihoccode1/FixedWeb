/* ============================================================
   IMPORTS.JS — Quản lý phiếu nhập hàng (100% API Database)
   ============================================================ */

document.addEventListener("DOMContentLoaded", function () {
  // Biến toàn cục lưu dữ liệu từ API để dùng cho các nút Xem/Sửa
  window.DB_RECEIPTS = [];

  const PROD_KEY = "admin.products"; // Tạm thời vẫn giữ product ở LocalStorage nếu bác chưa có API Product

  // ===== Helpers =====
  const $ = (s, ctx = document) => ctx.querySelector(s);
  const money = (x) => (Number(x) || 0).toLocaleString("vi-VN");
  const moneyInput = (x) => {
    const n = Number(x) || 0;
    if (Number.isInteger(n)) return n.toLocaleString("vi-VN");
    return n.toLocaleString("vi-VN", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    });
  };
  const parseMoneyInput = (raw) => {
    const s = String(raw || "").trim();
    if (!s) return 0;

    // Keep only digits, separators and minus then normalize decimal separator.
    const cleaned = s.replace(/[^0-9,.-]/g, "");
    const hasComma = cleaned.includes(",");
    const hasDot = cleaned.includes(".");

    if (hasComma && hasDot) {
      // vi-VN style: 1.234,56
      return Number(cleaned.replace(/\./g, "").replace(",", ".")) || 0;
    }
    if (hasComma) {
      const parts = cleaned.split(",");
      if (parts.length === 2 && parts[1].length <= 2) {
        return Number(parts[0].replace(/\./g, "") + "." + parts[1]) || 0;
      }
      return Number(cleaned.replace(/,/g, "")) || 0;
    }

    return Number(cleaned.replace(/,/g, "")) || 0;
  };
  const today = () => new Date().toISOString().slice(0, 10);
  const nextCode = () => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    const seq = String(Math.floor(Math.random() * 1000)).padStart(3, "0");
    return `PN-${y}${m}${day}-${seq}`;
  };
  function esc(s) {
    return String(s).replace(
      /[&<>"]/g,
      (c) =>
        ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c] || c,
    );
  }

  // ===== Products Repo (Tạm dùng LocalStorage và API) =====
  const PRODUCT_API = "./api/products.php?action=list";
  window.ALL_PRODUCTS = [];

  async function loadProducts() {
    try {
      const res = await fetch(PRODUCT_API);
      const data = await res.json();
      if (data.success && Array.isArray(data.products)) {
        window.ALL_PRODUCTS = data.products;
        localStorage.setItem(PROD_KEY, JSON.stringify(data.products));
      } else {
        window.ALL_PRODUCTS = listProducts();
      }
    } catch {
      window.ALL_PRODUCTS = listProducts();
    }
  }

  async function syncInventoryCaches() {
    try {
      const res = await fetch("./api/inventory.php?action=all-data");
      const data = await res.json();
      if (!res.ok || !data.success) return;

      localStorage.setItem(
        "admin.products",
        JSON.stringify(data.products || []),
      );
      localStorage.setItem(
        "admin.categories",
        JSON.stringify(data.categories || []),
      );
      localStorage.setItem(
        "admin.stock",
        JSON.stringify(data.transactions || []),
      );

      // Cập nhật danh sách sản phẩm đang dùng trong màn hình hiện tại
      window.ALL_PRODUCTS = data.products || window.ALL_PRODUCTS;
    } catch (_) {
      // Không chặn flow hoàn thành phiếu nếu sync cache thất bại
    }
  }

  function listProducts() {
    if (window.ALL_PRODUCTS && window.ALL_PRODUCTS.length)
      return window.ALL_PRODUCTS;
    try {
      return JSON.parse(localStorage.getItem(PROD_KEY) || "[]");
    } catch {
      return [];
    }
  }
  function getProductById(id) {
    return listProducts().find((p) => String(p.id) === String(id));
  }
  function searchProducts(q) {
    const products = listProducts();
    if (!q) return products;
    q = q.trim().toLowerCase();
    return products.filter((p) =>
      `${p.sku || p.code} ${p.name}`.toLowerCase().includes(q),
    );
  }

  // ===== DOM Elements =====
  const $tbody = $("#rcp-body");
  const $fQuery = $("#f_q");
  const $fStatus = $("#f_status");
  const $fDate = $("#f_date");
  const $prodSuggestions = $("#prod-suggestions");
  const $btnFilter = $("#btnFilter");
  const $btnNew = $("#btn-new");
  const $modal = $("#pn-modal");
  const $title = $("#pn-title");
  const $date = $("#pn_date");
  const $note = $("#pn_note");
  const $sprod = $("#s_prod");
  const $btnAddLine = $("#btnAddLine");
  const $sumQty = $("#sumQty");
  const $sumCost = $("#sumCost");
  const $meta = $("#pn-meta");
  const $btnSave = $("#btnSave");
  const $btnComplete = $("#btnComplete");
  const $btnClose = $("#btn-close");
  const $lines = $("#tblLines tbody");

  let STATE = { id: null, status: "draft", items: [] };

  function renderSummary() {
    const sumQ = STATE.items.reduce((s, it) => s + Number(it.quantity || 0), 0);
    const sumC = STATE.items.reduce(
      (s, it) => s + Number(it.quantity || 0) * Number(it.costPrice || 0),
      0,
    );
    $sumQty.textContent = money(sumQ);
    $sumCost.textContent = money(sumC);
    $meta.innerHTML = STATE.id
      ? `Mã phiếu: <b>${esc(STATE.code)}</b> – Trạng thái: <b>${esc(STATE.status)}</b> | Lần nhập: ${STATE.items.length}`
      : `Lần nhập: ${STATE.items.length}`;
  }

  // ===== LOAD DỮ LIỆU TỪ API =====
  function loadDataFromAPI() {
    $tbody.innerHTML =
      '<tr><td colspan="8" style="text-align:center;">Đang tải dữ liệu...</td></tr>';
    const params = new URLSearchParams();
    const q = ($fQuery.value || "").trim();
    if (q) params.set("q", q);
    if ($fStatus.value) params.set("status", $fStatus.value);
    if ($fDate.value) params.set("created_at", $fDate.value);

    const url =
      "./api/imports.php?action=list_detail" +
      (params.toString() ? "&" + params.toString() : "");
    fetch(url)
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) throw new Error("Lỗi tải dữ liệu");
        window.DB_RECEIPTS = data.receipts || [];
        renderTable(window.DB_RECEIPTS);
      })
      .catch(() => {
        $tbody.innerHTML =
          '<tr><td colspan="7" style="text-align:center;color:red;">Lỗi kết nối cơ sở dữ liệu!</td></tr>';
      });
  }

  // ===== RENDER BẢNG NGOÀI =====
  function renderTable(receipts) {
    if (!receipts.length) {
      $tbody.innerHTML =
        '<tr><td colspan="7" style="text-align:center;color:#aaa">Không có phiếu nhập nào.</td></tr>';
      return;
    }

    $tbody.innerHTML = receipts
      .map((r) => {
        const sp = r.items
          .map((i) => `${i.sku} – ${i.product_name}`)
          .join("<br>");
        const total_qty = r.items.reduce((s, i) => s + Number(i.quantity), 0);
        const total_money = r.items.reduce(
          (s, i) => s + Number(i.quantity) * Number(i.unit_cost),
          0,
        );

        const statusLabel =
          r.status === "draft"
            ? "Chưa nhập"
            : r.status === "completed"
              ? "Hoàn thành"
              : esc(r.status);
        return `<tr>
        <td><b>${esc(r.receipt_code)}</b></td>
        <td>${esc((r.receipt_date || "").slice(0, 10))}</td>
        <td>${sp}</td>
        <td class='num'>${money(total_qty)}</td>
        <td class='num'>${money(total_money)}</td>
        <td>${statusLabel}</td>
        <td>
          ${r.status === "draft" ? `<button data-act="edit" data-id="${r.receipt_id}" class="btn sm">Sửa</button>` : ""}
          ${r.status === "draft" ? `<button data-act="complete" data-id="${r.receipt_id}" class="btn sm primary">Hoàn thành</button>` : ""}
        </td>
      </tr>`;
      })
      .join("");
  }

  // ===== RENDER CHI TIẾT TRONG MODAL =====
  function renderLines() {
    if (!STATE.items.length) {
      $lines.innerHTML = `
        <tr>
          <td colspan="4" style="text-align:center;color:#777;padding:24px;">Chưa có sản phẩm. Nhập mã/tên sản phẩm rồi nhấn Thêm để tạo dòng nhập.</td>
        </tr>
      `;
    } else {
      $lines.innerHTML = STATE.items
        .map((it, i) => {
          const p = getProductById(it.productId) || { name: it.productName };
          const name = p.name || "(Đã xóa)";
          return `
          <tr data-idx="${i}">
            <td title="${esc(it.productCode)} – ${esc(name)}">${esc(it.productCode)} – ${esc(name)}</td>
            <td><input data-f="cost" class="input" type="text" inputmode="decimal" autocomplete="off" value="${moneyInput(it.costPrice)}" placeholder="0"></td>
            <td><input data-f="qty" class="input" type="number" min="1" step="1" value="${it.quantity}" placeholder="1"></td>
            <td><button type="button" data-act="remove" class="btn sm">Xóa</button></td>
          </tr>
        `;
        })
        .join("");
    }

    renderSummary();
  }

  // ===== MỞ FORM MODAL =====
  function openForm(id, readonly = false) {
    let cur = null;
    if (id) {
      // Tìm phiếu trong biến toàn cục vừa fetch từ DB
      cur = window.DB_RECEIPTS.find((r) => String(r.receipt_id) === String(id));
    }

    if (cur) {
      STATE = {
        id: cur.receipt_id,
        status: cur.status,
        code: cur.receipt_code,
        // Chuyển đổi format items từ DB sang format của Form
        items: cur.items.map((i) => ({
          productId: i.product_id,
          productCode: i.sku,
          productName: i.product_name,
          costPrice: i.unit_cost,
          quantity: i.quantity,
        })),
      };
    } else {
      STATE = { id: null, status: "draft", code: nextCode(), items: [] };
    }

    $title.textContent = cur
      ? readonly
        ? "Xem phiếu nhập"
        : "Sửa phiếu nhập"
      : "Tạo phiếu nhập";
    $date.min = today();
    $date.value = cur ? cur.receipt_date.slice(0, 10) : today();
    $note.value = cur?.note || "";
    const statusLabel = cur
      ? cur.status === "draft"
        ? "Chưa nhập"
        : cur.status === "completed"
          ? "Hoàn thành"
          : esc(cur.status)
      : "";
    $meta.innerHTML = cur
      ? `Mã phiếu: <b>${esc(cur.receipt_code)}</b> – Trạng thái: <b>${statusLabel}</b>`
      : "";

    const editable = !readonly && (!cur || cur.status === "draft");
    $btnSave.style.display = editable ? "inline-flex" : "none";
    if ($btnComplete)
      $btnComplete.style.display =
        cur && cur.status === "draft" ? "inline-flex" : "none";

    renderLines();
    $modal.classList.add("show");
  }

  // ===== EVENTS =====
  $btnClose?.addEventListener("click", () => $modal.classList.remove("show"));
  $btnNew?.addEventListener("click", () => openForm(null, false));
  $btnFilter?.addEventListener("click", loadDataFromAPI); // Nút filter tạm thời reload lại toàn bộ dữ liệu DB
  $fQuery?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      loadDataFromAPI();
    }
  });

  function addProductLine(p) {
    if (!p) return alert("Không tìm thấy sản phẩm trong hệ thống");

    const existing = STATE.items.find(
      (it) => String(it.productId) === String(p.id),
    );
    if (existing) {
      existing.quantity = Number(existing.quantity || 0) + 1;
      renderLines();
      return;
    }

    STATE.items.push({
      productId: p.id,
      productCode: p.sku || p.code,
      productName: p.name,
      costPrice: Number(p.cost_price || 0),
      quantity: 1,
    });
    renderLines();
  }

  function pickProductByKeyword() {
    const kw = ($sprod.value || "").trim();
    if (!kw) return alert("Nhập mã hoặc tên sản phẩm để thêm.");
    const found = searchProducts(kw);
    if (!found.length) return alert("Không tìm thấy sản phẩm trong hệ thống");
    addProductLine(found[0]);
  }

  function showProductSuggestions() {
    const q = ($sprod.value || "").trim();
    const items = searchProducts(q);
    if (!items.length) {
      $prodSuggestions.innerHTML = "";
      return;
    }
    $prodSuggestions.innerHTML = `<div class="suggestions-list">${items
      .map(
        (p) =>
          `<div data-id="${p.id}">${esc(p.sku || p.code)} — ${esc(p.name)} (${esc(p.supplier || "NCC không rõ")})</div>`,
      )
      .join("")}</div>`;
  }

  $btnAddLine?.addEventListener("click", pickProductByKeyword);
  $sprod?.addEventListener("input", showProductSuggestions);
  $sprod?.addEventListener("click", showProductSuggestions);
  $sprod?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      pickProductByKeyword();
    }
  });

  document.addEventListener("click", (e) => {
    if (
      !e.target.closest("#prod-suggestions") &&
      !e.target.closest("#s_prod")
    ) {
      $prodSuggestions.innerHTML = "";
    }
  });

  // Clicking a suggestion only fills the input, doesn't add the product yet
  // User must click "Thêm" button or press Enter to actually add the product
  $prodSuggestions?.addEventListener("click", (e) => {
    const item = e.target.closest("div[data-id]");
    if (!item) return;
    const prod = listProducts().find(
      (p) => String(p.id) === String(item.dataset.id),
    );
    if (!prod) return;
    // Fill the input with the selected product
    $sprod.value = `${prod.sku || prod.code} ${prod.name}`;
    // Close suggestions dropdown
    $prodSuggestions.innerHTML = "";
    // Product will be added when user clicks "Thêm" button or presses Enter
  });

  // Sửa số lượng, giá và xóa dòng
  $lines?.addEventListener("input", (e) => {
    const tr = e.target.closest("tr");
    if (!tr) return;
    const idx = Number(tr.dataset.idx);
    const f = e.target.dataset.f;

    if (f === "cost") {
      const price = Math.max(0, parseMoneyInput(e.target.value));
      STATE.items[idx].costPrice = price;
      // Real-time validation: price must be > 0
      if (price <= 0) {
        e.target.classList.add("error");
      } else {
        e.target.classList.remove("error");
      }
      renderSummary();
    }
    if (f === "qty") {
      const qty = Math.max(1, Number(e.target.value || 0));
      STATE.items[idx].quantity = qty;
      // Real-time validation: quantity must be >= 1
      if (qty < 1) {
        e.target.classList.add("error");
      } else {
        e.target.classList.remove("error");
      }
      renderSummary();
    }
  });

  $lines?.addEventListener(
    "blur",
    (e) => {
      const input = e.target;
      if (!input || input.dataset.f !== "cost") return;
      const tr = input.closest("tr");
      if (!tr) return;
      const idx = Number(tr.dataset.idx);
      STATE.items[idx].costPrice = Math.max(0, parseMoneyInput(input.value));
      input.value = moneyInput(STATE.items[idx].costPrice);
      renderSummary();
    },
    true,
  );

  $lines?.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-act]");
    if (!btn) return;
    if (btn.dataset.act !== "remove") return;
    const tr = btn.closest("tr");
    if (!tr) return;
    const idx = Number(tr.dataset.idx);
    STATE.items.splice(idx, 1);
    renderLines();
  });

  // GỌI API: LƯU PHIẾU
  $btnSave?.addEventListener("click", async () => {
    if (!STATE.items.length) return alert("Vui lòng thêm ít nhất 1 sản phẩm!");

    // Validate: price > 0 (not <= 0), quantity >= 1
    const invalidItem = STATE.items.find(
      (it) => Number(it.quantity) < 1 || Number(it.costPrice) <= 0,
    );
    if (invalidItem) return alert("Số lượng phải >= 1 và giá nhập phải > 0.");

    const selectedDate = $date.value || today();
    if (selectedDate < today()) {
      return alert("Ngày nhập phải là hôm nay hoặc ngày tương lai.");
    }

    const formData = new FormData();
    formData.append("code", STATE.code);
    formData.append("created_at", selectedDate + " 00:00:00");
    formData.append("note", $note.value);

    const productsDetail = STATE.items.map((it) => ({
      product_id: it.productId,
      quantity: it.quantity,
      unit_cost: it.costPrice,
    }));
    formData.append("products", JSON.stringify(productsDetail));

    let url = "./api/imports.php?action=create";
    if (STATE.id) {
      url = "./api/imports.php?action=update";
      formData.append("receipt_id", STATE.id);
    }

    try {
      const res = await fetch(url, { method: "POST", body: formData });
      const text = await res.text();
      let result;
      try {
        result = JSON.parse(text);
      } catch (parseError) {
        return alert("Lỗi server: response không phải JSON:\n" + text);
      }
      if (result.success) {
        alert(result.message);
        $modal.classList.remove("show");
        loadDataFromAPI(); // Cập nhật lại bảng
      } else {
        alert("Lỗi server: " + result.message);
      }
    } catch (e) {
      alert("Lỗi kết nối: " + e.message);
    }
  });

  // GỌI API: HOÀN THÀNH PHIẾU
  $btnComplete?.addEventListener("click", async () => {
    if (!STATE.id) return alert("Hãy lưu phiếu trước");
    if (!confirm("Hoàn thành phiếu? Phiếu sẽ không thể sửa được nữa.")) return;

    const formData = new FormData();
    formData.append("receipt_id", STATE.id);

    try {
      const res = await fetch("./api/imports.php?action=complete", {
        method: "POST",
        body: formData,
      });
      const result = await res.json();
      if (result.success) {
        await syncInventoryCaches();
        alert(result.message);
        $modal.classList.remove("show");
        loadDataFromAPI();
      } else {
        alert("Lỗi server: " + result.message);
      }
    } catch (e) {
      alert("Lỗi kết nối: " + e.message);
    }
  });

  // Bắt sự kiện click các nút trên bảng
  $tbody?.addEventListener("click", async (e) => {
    const btn = e.target.closest("button[data-act]");
    if (!btn) return;
    const id = btn.dataset.id;
    const act = btn.dataset.act;

    if (act === "view") openForm(id, true);
    if (act === "edit") openForm(id, false);
    if (act === "complete") {
      if (!confirm(`Xác nhận hoàn thành phiếu này?`)) return;
      const formData = new FormData();
      formData.append("receipt_id", id);
      try {
        const res = await fetch("./api/imports.php?action=complete", {
          method: "POST",
          body: formData,
        });
        const result = await res.json();
        if (result.success) {
          await syncInventoryCaches();
          alert("Đã hoàn thành");
          loadDataFromAPI();
        } else {
          alert("Lỗi: " + result.message);
        }
      } catch (err) {
        alert(err);
      }
    }
  });

  // Chạy lần đầu
  $date.min = today();
  $date.value = today();
  loadProducts().then(loadDataFromAPI);
});
