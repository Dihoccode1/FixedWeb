(function () {
  const API_URL = "./api/orders.php";
  const $ = (s, r = document) => r.querySelector(s);

  const money = (n) => (Number(n) || 0).toLocaleString("vi-VN");

  const STATUS_TEXT = {
    new: "Chưa xử lý",
    confirmed: "Đã xác nhận",
    delivered: "Đã giao thành công",
    canceled: "Đã huỷ",
  };

  const STATUS_CLASS = {
    new: "status-chip st-new",
    confirmed: "status-chip st-confirmed",
    delivered: "status-chip st-delivered",
    canceled: "status-chip st-canceled",
  };

  const FLOW = {
    new: ["confirmed", "canceled"],
    confirmed: ["delivered", "canceled"],
    delivered: [],
    canceled: [],
  };

  function esc(s) {
    return String(s || "").replace(/[&<>\"']/g, (c) => {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[c];
    });
  }

  async function apiGet(params) {
    const usp = new URLSearchParams(params);
    const res = await fetch(`${API_URL}?${usp.toString()}`, {
      method: "GET",
      credentials: "same-origin",
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok || !body.success) {
      throw new Error(body.message || "Lỗi tải dữ liệu đơn hàng");
    }
    return body;
  }

  async function apiPost(formDataObj) {
    const fd = new FormData();
    Object.entries(formDataObj || {}).forEach(([k, v]) => fd.append(k, v));

    const res = await fetch(API_URL, {
      method: "POST",
      body: fd,
      credentials: "same-origin",
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok || !body.success) {
      throw new Error(body.message || "Lỗi cập nhật đơn hàng");
    }
    return body;
  }

  const tb = $("#od-body");
  const qInput = $("#q");
  const fromInput = $("#from");
  const toInput = $("#to");
  const stSelect = $("#status");
  const sortWard = $("#sortWard");
  const btnFilter = $("#btnFilter");

  const modal = $("#od-modal");
  const mTitle = $("#od-title");
  const mStatusSel = $("#od-status");
  const mBtnUpdate = $("#btnUpdateStatus");
  const mBtnClose = $("#btnClose");

  const vCode = $("#vCode");
  const vDate = $("#vDate");
  const vStatus = $("#vStatus");
  const vNote = $("#vNote");
  const vCus = $("#vCus");
  const vPhone = $("#vPhone");
  const vAddr = $("#vAddr");
  const vItems = $("#vItems");
  const vTotal = $("#vTotal");

  let state = {
    list: [],
    modalId: null,
  };

  function canTransition(current, next) {
    if (current === next) return true;
    return (FLOW[current] || []).includes(next);
  }

  function applyModalStatusRules(currentStatus) {
    if (!mStatusSel) return;
    const allowed = new Set([currentStatus, ...(FLOW[currentStatus] || [])]);
    Array.from(mStatusSel.options).forEach((opt) => {
      opt.disabled = !allowed.has(opt.value);
    });
  }

  function renderRows() {
    if (!state.list.length) {
      tb.innerHTML =
        '<tr><td colspan="8" style="text-align:center;color:#9aa3ad;padding:20px">Không có dữ liệu</td></tr>';
      return;
    }

    tb.innerHTML = state.list
      .map((o) => {
        const st = String(o.status || "new").toLowerCase();
        const stClass = STATUS_CLASS[st] || STATUS_CLASS.new;
        const stText = o.status_text || STATUS_TEXT[st] || STATUS_TEXT.new;
        const dateText = o.created_at
          ? new Date(o.created_at).toLocaleString("vi-VN")
          : "";

        let actionBtns = "";
        
        if (st === "new") {
          actionBtns = `<button class="btn" data-act="confirm" data-id="${o.id}">Xác nhận</button> <button class="btn" data-act="cancel" data-id="${o.id}">Huỷ</button>`;
        } else if (st === "confirmed") {
          actionBtns = `<button class="btn" data-act="deliver" data-id="${o.id}">Giao xong</button> <button class="btn" data-act="cancel" data-id="${o.id}">Huỷ</button>`;
        }

        return `
          <tr>
            <td><strong>${esc(o.code)}</strong></td>
            <td class="small">${esc(dateText)}</td>
            <td>
              <div>${esc(o.full_name || "")}</div>
              <div class="small">${esc(o.phone || "")}</div>
            </td>
            <td class="small">${esc(o.shipping_address || "")}</td>
            <td style="text-align:right">${Number(o.total_qty || 0)}</td>
            <td style="text-align:right">${money(o.total_amount)}</td>
            <td><span class="${stClass}">${esc(stText)}</span></td>
            <td style="display:flex;gap:4px;flex-direction:column;align-items:flex-start">
              <div style="display:flex;gap:4px">
                ${actionBtns}
              </div>
              <a class="btn" href="./orders.php?order_id=${o.id}" data-act="view-link" data-id="${o.id}">Xem chi tiết</a>
            </td>
          </tr>
        `;
      })
      .join("");
  }

  async function loadList() {
    const params = {
      action: "list",
      q: (qInput?.value || "").trim(),
      from: fromInput?.value || "",
      to: toInput?.value || "",
      status: stSelect?.value || "",
      sort_ward: sortWard?.value || "",
    };

    const body = await apiGet(params);
    state.list = Array.isArray(body.orders) ? body.orders : [];
    renderRows();
  }

  async function openModal(orderId) {
    const body = await apiGet({ action: "detail", id: String(orderId) });
    const o = body.order;
    if (!o) return;

    state.modalId = String(o.id);

    mTitle.textContent = `Chi tiết đơn #${o.code || o.id || "—"}`;
    vCode.textContent = o.code || "—";
    vDate.textContent = o.created_at
      ? new Date(o.created_at).toLocaleString("vi-VN")
      : "—";
    vStatus.textContent = o.status_text || STATUS_TEXT[o.status] || "—";
    vNote.textContent = o.note || "—";
    vCus.textContent = o.full_name || "—";
    vPhone.textContent = o.phone || "—";
    vAddr.textContent = o.shipping_address || "—";

    const st = String(o.status || "new").toLowerCase();
    if (mStatusSel) mStatusSel.value = st;
    applyModalStatusRules(st);

    vItems.innerHTML = (o.items || [])
      .map((it) => {
        return `
          <tr>
            <td>${esc(it.sku || "")}</td>
            <td>${esc(it.name || "")}</td>
            <td style="text-align:right">${money(it.unit_price)}</td>
            <td style="text-align:right">${Number(it.quantity || 0)}</td>
            <td style="text-align:right">${money(it.line_total)}</td>
          </tr>
        `;
      })
      .join("");

    vTotal.textContent = money(o.total_amount || 0);

    modal.classList.add("show");
    modal.setAttribute("aria-hidden", "false");
  }

  function closeModal() {
    modal.classList.remove("show");
    modal.setAttribute("aria-hidden", "true");
    state.modalId = null;
    const url = new URL(window.location.href);
    url.searchParams.delete("order_id");
    window.history.replaceState({}, "", url.toString());
  }

  async function updateStatus(orderId, status) {
    await apiPost({ action: "update_status", id: String(orderId), status });
    await loadList();
  }

  mBtnClose?.addEventListener("click", closeModal);
  modal?.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
  });

  mBtnUpdate?.addEventListener("click", async () => {
    if (!state.modalId) return;
    try {
      const row = state.list.find(
        (x) => String(x.id) === String(state.modalId),
      );
      const current = String(row?.status || "new").toLowerCase();
      const next = String(mStatusSel?.value || "new").toLowerCase();
      if (!canTransition(current, next)) {
        alert("Không thể quay lui trạng thái hoặc chuyển sai luồng.");
        return;
      }

      await updateStatus(state.modalId, next);
      await openModal(state.modalId);
      alert("Đã cập nhật trạng thái");
    } catch (e) {
      alert(e.message || "Lỗi cập nhật trạng thái");
    }
  });

  document.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-act]");
    if (!btn) return;

    const act = btn.getAttribute("data-act");
    const id = btn.getAttribute("data-id");
    if (!id) return;

    if (act === "view-link") {
      e.preventDefault();
      const url = new URL(window.location.href);
      url.searchParams.set("order_id", id);
      window.history.replaceState({}, "", url.toString());
      try {
        await openModal(id);
      } catch (err) {
        alert(err.message || "Không tải được chi tiết đơn");
      }
      return;
    }

    try {
      if (act === "confirm") {
        await updateStatus(id, "confirmed");
        return;
      }
      if (act === "deliver") {
        await updateStatus(id, "delivered");
        return;
      }
      if (act === "cancel") {
        if (!confirm("Xác nhận huỷ đơn này?")) return;
        await updateStatus(id, "canceled");
      }
    } catch (err) {
      alert(err.message || "Lỗi thao tác đơn hàng");
    }
  });

  btnFilter?.addEventListener("click", loadList);
  qInput?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") loadList();
  });

  (async function init() {
    try {
      await loadList();
      const orderId = new URLSearchParams(window.location.search).get(
        "order_id",
      );
      if (orderId) {
        await openModal(orderId);
      }
    } catch (e) {
      tb.innerHTML =
        '<tr><td colspan="8" style="text-align:center;color:#c0392b;padding:20px">Không tải được dữ liệu đơn hàng</td></tr>';
    }
  })();
})();
