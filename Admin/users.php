<?php
require_once __DIR__ . '/../includes/common.php';
require_admin();
$admin = admin_user();
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Font Awesome (relative path) -->
    <link
      rel="stylesheet"
      href="../fontawesome-free-6.7.2-web/css/all.min.css"
    />

    <title>Quản lý người dùng | Nobility 1800s Admin</title>

    <!-- CSS admin chung (relative) -->
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />

    <!-- Font Awesome (relative, không dùng link tuyệt đối) -->
    <link
      rel="stylesheet"
      href="../fontawesome-free-6.7.2-web/css/all.min.css"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />

    <style>
      :root {
        --u-bg-1: #f5f8ff;
        --u-bg-2: #eef4ff;
        --u-text: #0f172a;
        --u-muted: #64748b;
        --u-line: #dbe5f5;
        --u-brand: #2b3ea8;
        --u-brand-2: #1877f2;
      }

      body {
        background:
          radial-gradient(720px 260px at 92% -8%, rgba(59, 130, 246, 0.14), transparent 70%),
          radial-gradient(560px 240px at 6% 4%, rgba(99, 102, 241, 0.12), transparent 72%),
          linear-gradient(180deg, var(--u-bg-1) 0%, var(--u-bg-2) 100%);
      }

      .page {
        padding: 14px 16px;
      }

      .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0 0 12px;
        gap: 14px;
        flex-wrap: wrap;
      }

      .page-header h1 {
        font-size: 30px;
        margin: 0;
        color: var(--u-text);
        letter-spacing: -0.01em;
      }

      .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
      }
      .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fff;
        cursor: pointer;
        font-weight: 700;
        font-size: 13px;
        transition: transform 0.18s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.08);
      }

      .btn:hover {
        transform: translateY(-1px);
        border-color: #bfd4ff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.16);
      }

      .btn.primary {
        background: linear-gradient(135deg, var(--u-brand), var(--u-brand-2));
        color: #fff;
        border-color: transparent;
      }

      .btn.danger {
        background: #ef4444;
        color: #fff;
        border-color: #ef4444;
      }

      .btn.icon {
        padding: 8px 10px;
      }

      .toolbar {
        display: flex;
        gap: 10px;
        align-items: center;
        margin: 12px 0;
        flex-wrap: wrap;
        background: #ffffff;
        border: 1px solid var(--u-line);
        border-radius: 14px;
        padding: 10px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
      }

      .input {
        border: 1px solid #d5deef;
        border-radius: 10px;
        padding: 9px 11px;
        min-width: 220px;
        background: #fff;
        color: #111;
        outline: none;
        font-size: 13px;
      }

      .input:focus {
        border-color: #9db9ff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.14);
      }

      table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        font-size: 13px;
        border: 1px solid #d8e2f2;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
      }

      thead td {
        background: linear-gradient(180deg, #f8fbff 0%, #f1f6ff 100%);
        font-weight: 800;
        color: #1f2a44;
        border-bottom: 1px solid #dbe5f5;
      }

      td {
        padding: 10px 10px;
        border-bottom: 1px solid #eef3fb;
        vertical-align: middle;
      }

      tbody tr {
        transition: background-color 0.16s ease;
      }

      tr:hover {
        background: #f7faff;
      }

      .status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        border: 1px solid #e5e7eb;
        font-weight: 700;
      }

      .status.active {
        background: #eafaf2;
        border-color: #9be7c0;
        color: #0b7a4f;
      }

      .status.locked {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #be123c;
      }

      .chip {
        display: inline-block;
        border: 1px solid #d9e3f3;
        border-radius: 999px;
        padding: 2px 8px;
        font-size: 11px;
        background: #f3f7ff;
        color: #1e3a8a;
        font-weight: 600;
      }

      .empty {
        color: #9aa3ad;
        text-align: center;
        padding: 20px;
      }

      .modal {
        position: fixed;
        inset: 0;
        background: rgba(12, 19, 44, 0.42);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 999;
        backdrop-filter: blur(2px);
      }

      .modal.show {
        display: flex;
      }

      .card {
        background: #fff;
        border: 1px solid #d9e4f6;
        border-radius: 18px;
        box-shadow: 0 24px 56px rgba(30, 64, 175, 0.18);
        width: 100%;
        max-width: 720px;
      }

      .card .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid #e3ebfa;
        background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%);
      }

      .card .card-body {
        padding: 16px;
      }

      .grid-2 {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
      }
      @media (min-width: 720px) {
        .grid-2 {
          grid-template-columns: 1fr 1fr;
        }
      }
      label.small {
        font-size: 11px;
        color: #64748b;
        margin-bottom: 4px;
        display: block;
        font-weight: 600;
      }

      .muted {
        color: #64748b;
        font-size: 11px;
      }

      .pill-note {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 999px;
        background: #eef4ff;
        color: #2563eb;
        font-size: 10px;
        margin-left: 4px;
        border: 1px solid #d5e3ff;
      }

      @media (max-width: 840px) {
        .toolbar {
          padding: 8px;
        }
        .input {
          min-width: 180px;
        }
      }
    </style>
      <link rel="stylesheet" href="../assets/css/clean-styles.css" />
      <link rel="stylesheet" href="./assets/css/admin-unified-theme.css" />
</head>

  <body>
    <div class="container">
      <!-- SIDEBAR -->
      <div class="navagation">
        <ul>
          <li>
            <a href="./index.php">
              <span class="icon"><i class="fa-brands fa-apple"></i></span>
              <span class="title">Nobility 1800s</span>
            </a>
          </li>

          <li id="nav-dashboard">
            <a href="./index.php">
              <span class="icon"><i class="fa-solid fa-house"></i></span>
              <span class="title">Bảng điều khiển</span>
            </a>
          </li>

          <li class="hovered" id="nav-customers">
            <a href="./users.php">
              <span class="icon"><i class="fa-solid fa-user-group"></i></span>
              <span class="title">Quản lý khách hàng</span>
            </a>
          </li>

          <li id="nav-categories">
            <a href="./categories.php">
              <span class="icon"><i class="fa-solid fa-layer-group"></i></span>
              <span class="title">Quản lý danh mục</span>
            </a>
          </li>

          <li id="nav-products">
            <a href="./products.php">
              <span class="icon"><i class="fa-solid fa-tags"></i></span>
              <span class="title">Quản lý sản phẩm</span>
            </a>
          </li>

          <li id="nav-pricing">
            <a href="./pricing.php">
              <span class="icon"><i class="fa-solid fa-dollar-sign"></i></span>
              <span class="title">Quản lý giá bán</span>
            </a>
          </li>

          <li id="nav-imports">
            <a href="./imports.php">
              <span class="icon"><i class="fa-solid fa-box-open"></i></span>
              <span class="title">Phiếu nhập hàng</span>
            </a>
          </li>

          <li id="nav-inventory">
            <a href="./inventory.php">
              <span class="icon"><i class="fa-solid fa-warehouse"></i></span>
              <span class="title">Quản lý hàng tồn kho</span>
            </a>
          </li>

          <li id="nav-orders">
            <a href="./orders.php">
              <span class="icon"><i class="fa-solid fa-receipt"></i></span>
              <span class="title">Quản lý đơn hàng</span>
            </a>
          </li>

          <hr />

          <li class="welcome-admin-tile inline-clean-4" >
            <div
              
             class="inline-clean-5">
              <!-- Avatar tròn có chữ cái đầu -->
              <div
                id="sb-admin-avatar"
                
               class="inline-clean-6">
                A
              </div>

              <!-- Tên + role -->
              <div
                
               class="inline-clean-7">
                <span id="sb-admin-username" class="inline-clean-8"><?php echo h($admin['name'] ?? $admin['username'] ?? 'Admin'); ?></span>
                <span id="sb-admin-role" class="inline-clean-9"><?php echo h((($admin['role'] ?? 'admin') === 'admin') ? 'Quản trị viên' : 'Khách hàng'); ?></span>
              </div>
            </div>
          </li>

          <li>
            <a href="#" id="btn-logout-side">
              <span class="icon">
                <i class="fa-solid fa-right-from-bracket"></i>
              </span>
              <span class="title">Đăng xuất</span>
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="main">
      <div class="page">
        <div class="page-header">
          <div>
            <h1>Quản lý người dùng</h1>
            <div class="muted">
              Đổi mật khẩu & khóa tài khoản sẽ ép người dùng đăng nhập lại.
              <span class="pill-note">
                <i class=""></i>
              </span>
            </div>
          </div>
          <div class="actions">
            <button class="btn primary" id="btnAdd">
              <i class="fa-solid fa-user-plus"></i>
              <span>Thêm tài khoản</span>
            </button>
          </div>
        </div>

        <div class="toolbar">
          <input
            class="input"
            id="searchBox"
            placeholder="Tìm theo tên, email, SĐT..."
          />
          <select class="input inline-clean-36" id="filterStatus" >
            <option value="all">Tất cả trạng thái</option>
            <option value="active">Đang hoạt động</option>
            <option value="locked">Đã khóa</option>
          </select>
        </div>

        <!-- Users Table Section - Full Width -->
        <div class="users-table-section">
          <table class="users-table">
            <thead>
              <tr>
                <td>Họ tên</td>
                <td>Email</td>
                <td>SĐT</td>
                <td>Vai trò</td>
                <td>Trạng thái</td>
                <td>Tạo lúc</td>
                <td  class="inline-clean-37">Thao tác</td>
              </tr>
            </thead>
            <tbody id="tbodyUsers">
              <tr>
                <td colspan="7" class="empty">Đang tải...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Add/Edit -->
    <div class="modal" id="modalUser">
      <div class="card">
        <div class="card-header">
          <strong id="modalTitle">Thêm tài khoản</strong>
          <button class="btn icon" id="btnCloseModal" aria-label="Đóng">
            ✕
          </button>
        </div>
        <div class="card-body">
          <div class="grid-2">
            <div>
              <label class="small">Họ tên</label>
              <input class="input" id="uName" placeholder="Nguyễn Văn A" />
            </div>
            <div>
              <label class="small">Email</label>
              <input class="input" id="uEmail" placeholder="email@domain.com" />
            </div>
            <div>
              <label class="small">Số điện thoại</label>
              <input class="input" id="uPhone" placeholder="09xx..." />
            </div>
            <div>
              <label class="small">Địa chỉ</label>
              <input
                class="input"
                id="uAddress"
                placeholder="Địa chỉ giao hàng"
              />
            </div>
            <div>
              <label class="small">Mật khẩu</label>
              <input
                class="input"
                id="uPassword"
                placeholder="Mật khẩu khởi tạo"
                type="text"
              />
              <div class="muted inline-clean-38" >
                Để trống nếu không muốn đổi mật khẩu khi sửa.
              </div>
            </div>
            <div>
              <label class="small">Vai trò</label>
              <select class="input" id="uRole">
                <option value="member">Khách hàng</option>
                <option value="admin">Quản trị viên</option>
              </select>
            </div>
          </div>
          <div
            
           class="inline-clean-39">
            <button class="btn" id="btnCancel">Hủy</button>
            <button class="btn primary" id="btnSave">Lưu</button>
          </div>
        </div>
      </div>
    </div>

    <!-- JS chung admin -->
    <script src="./assets/js/main.js"></script>

    <script>
      (function () {
        const $ = (s) => document.querySelector(s);
        const API_URL = './api/users.php';
        let users = [];

        function toast(msg) {
          alert(msg);
        }

        function normalizeText(s) {
          return String(s || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
        }

        function matchField(field, qNorm) {
          const t = normalizeText(field);
          if (!t) return false;
          const parts = t.split(/[\s@._-]+/).filter(Boolean);
          return parts.some((p) => p.startsWith(qNorm));
        }

        async function apiRequest(action, data = {}) {
          const form = new FormData();
          form.append('action', action);
          Object.entries(data).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
              form.append(key, value);
            }
          });
          const response = await fetch(API_URL, {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
          });
          if (!response.ok) {
            const error = await response.json().catch(() => null);
            throw new Error(error?.message || 'Lỗi máy chủ');
          }
          const body = await response.json();
          if (!body.success) {
            throw new Error(body.message || 'Lỗi không xác định');
          }
          return body;
        }

        async function loadUsers() {
          const response = await fetch(`${API_URL}?action=list`, {
            method: 'GET',
            credentials: 'same-origin',
          });
          if (!response.ok) {
            throw new Error('Không tải được danh sách người dùng');
          }
          const body = await response.json();
          if (!body.success) {
            throw new Error(body.message || 'Lỗi khi tải danh sách');
          }
          users = Array.isArray(body.users) ? body.users : [];
          render();
        }

        const tbody = document.getElementById('tbodyUsers');
        const searchBox = document.getElementById('searchBox');
        const filterStatus = document.getElementById('filterStatus');
        const modal = document.getElementById('modalUser');
        const modalTitle = document.getElementById('modalTitle');
        const uName = $('#uName');
        const uEmail = $('#uEmail');
        const uPhone = $('#uPhone');
        const uAddress = $('#uAddress');
        const uPassword = $('#uPassword');
        const uRole = $('#uRole');

        function esc(s) {
          return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        }

        function formatDate(s) {
          const d = new Date(s || '');
          return isNaN(d) ? '—' : d.toLocaleString('vi-VN');
        }

        function render() {
          const qRaw = (searchBox.value || '').trim();
          const qNorm = normalizeText(qRaw);
          const status = filterStatus.value;
          let list = users.slice();

          if (qNorm) {
            list = list.filter((u) => {
              return (
                matchField(u.name, qNorm) ||
                matchField(u.email, qNorm) ||
                matchField(u.phone, qNorm)
              );
            });
          }

          if (status !== 'all') {
            list = list.filter((u) => (u.status || 'active') === status);
          }

          if (!list.length) {
            tbody.innerHTML =
              '<tr><td colspan="7" class="empty">Không có dữ liệu</td></tr>';
            return;
          }

          tbody.innerHTML = list
            .map((u) => {
              const isLocked = u.status === 'locked';
              const email = esc(u.email || '');
              const roleCode = u.role || 'member';
              const roleLabel = roleCode === 'admin' ? 'Quản trị viên' : 'Khách hàng';
              return `
                <tr>
                  <td><strong>${esc(u.name || '')}</strong></td>
                  <td>${email}</td>
                  <td>${esc(u.phone || '')}</td>
                  <td><span class="chip">${esc(roleLabel)}</span></td>
                  <td>
                    <span class="status ${isLocked ? 'locked' : 'active'}">
                      ${isLocked ? 'Đã khóa' : 'Đang hoạt động'}
                    </span>
                  </td>
                  <td>${formatDate(u.created_at)}</td>
                  <td>
                    <div class="inline-clean-40">
                      <button class="btn" onclick="UsersUI.promptReset('${email}')" title="Đổi mật khẩu">
                        <i class="fa-solid fa-key"></i><span>Đổi mật khẩu</span>
                      </button>
                      ${
                        isLocked
                          ? `<button class="btn" onclick="UsersUI.unlock('${email}')" title="Mở khóa">
                               <i class="fa-solid fa-lock-open"></i><span>Mở khóa</span>
                             </button>`
                          : `<button class="btn danger" onclick="UsersUI.lock('${email}')" title="Khóa tài khoản">
                               <i class="fa-solid fa-lock"></i><span>Khóa</span>
                             </button>`
                      }
                    </div>
                  </td>
                </tr>`;
            })
            .join('');
        }

        searchBox.addEventListener('input', render);
        filterStatus.addEventListener('change', render);

        function openModal() {
          modal.classList.add('show');
        }

        function closeModal() {
          modal.classList.remove('show');
        }

        document.getElementById('btnCloseModal').onclick = closeModal;
        document.getElementById('btnCancel').onclick = closeModal;

        document.getElementById('btnAdd').addEventListener('click', () => {
          modalTitle.textContent = 'Thêm tài khoản';
          uName.value = '';
          uEmail.value = '';
          uPhone.value = '';
          uAddress.value = '';
          uPassword.value = '';
          uRole.value = 'member';
          openModal();
        });

        document.getElementById('btnSave').addEventListener('click', async () => {
          const name = uName.value.trim();
          const email = (uEmail.value || '').trim().toLowerCase();
          const phone = uPhone.value.trim();
          const address = uAddress.value.trim();
          const pass = uPassword.value.trim();
          const role = uRole.value;

          if (!name || !email) return toast('Vui lòng nhập Họ tên và Email.');
          if (pass && pass.length < 4)
            return toast('Mật khẩu tối thiểu 4 ký tự.');

          try {
            await apiRequest('upsert', {
              name,
              email,
              phone,
              address,
              password: pass,
              role,
              status: 'active',
            });
            closeModal();
            await loadUsers();
            toast('Đã lưu tài khoản.');
          } catch (e) {
            toast(e.message || 'Lỗi không xác định');
          }
        });

        async function setStatus(email, status) {
          await apiRequest('set_status', { email, status });
          await loadUsers();
        }

        async function resetPassword(email, newPass) {
          await apiRequest('reset_password', { email, password: newPass });
          await loadUsers();
        }

        window.UsersUI = {
          promptReset(email) {
            const newPass = prompt('Nhập mật khẩu mới cho ' + email + ':');
            if (!newPass) return;
            if (newPass.length < 4) return toast('Mật khẩu tối thiểu 4 ký tự.');
            resetPassword(email, newPass)
              .then(() => toast('Đã đặt lại mật khẩu.'))
              .catch((e) => toast(e.message || 'Lỗi khi đặt lại mật khẩu.'));
          },
          lock(email) {
            if (!confirm('Khóa tài khoản này? Người dùng sẽ bị đăng xuất và không thể đăng nhập.')) return;
            setStatus(email, 'locked')
              .then(() => toast('Đã khóa tài khoản.'))
              .catch((e) => toast(e.message || 'Lỗi khi khóa tài khoản.'));
          },
          unlock(email) {
            if (!confirm('Mở khóa tài khoản này? Cho phép người dùng đăng nhập lại.')) return;
            setStatus(email, 'active')
              .then(() => toast('Đã mở khóa tài khoản.'))
              .catch((e) => toast(e.message || 'Lỗi khi mở khóa tài khoản.'));
          },
        };

        document
          .getElementById('btn-logout-side')
          ?.addEventListener('click', (e) => {
            e.preventDefault();
            location.href = './logout.php';
          });

        loadUsers().catch((err) => {
          tbody.innerHTML = '<tr><td colspan="7" class="empty">Không tải được dữ liệu</td></tr>';
          console.error(err);
        });
      })();
    </script>
  </body>
</html>

