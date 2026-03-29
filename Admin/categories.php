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
    <link
      rel="stylesheet"
      href="../fontawesome-free-6.7.2-web/css/all.min.css"
      integrity="sha512-..."
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <!-- Font Awesome -->

    <title>Quản lý danh mục | Nobility 1800s Admin</title>
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />

    <style>
      .page {
        padding: 14px 16px;
      }
      .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0 0 12px;
      }
      .page-header h1 {
        font-size: 30px;
        color: #000;
        margin: 0;
      }
      .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
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
      }
      .btn.primary {
        background: #1f235a;
        color: #fff;
        border-color: #1f235a;
      }
      .input {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fff;
        min-width: 220px;
        outline: none;
      }
      .toolbar {
        display: flex;
        gap: 10px;
        align-items: center;
        margin: 12px 0;
        flex-wrap: wrap;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
      }
      thead td {
        background: #f8fafc;
        font-weight: 800;
        color: #111;
        border-bottom: 1px solid #e5e7eb;
      }
      td {
        padding: 12px;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
      }
      tr:hover {
        background: #fafafa;
      }
      .badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 12px;
        border: 1px solid #e5e7eb;
        color: #6b7280;
      }

      /* Drawer */
      .drawer-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        display: none;
        z-index: 998;
      }
      .drawer-backdrop.show {
        display: block;
      }
      .drawer {
        position: fixed;
        top: 0;
        right: 0;
        height: 100dvh;
        width: min(440px, 92vw);
        background: #fff;
        border-left: 1px solid #e5e7eb;
        z-index: 999;
        transform: translateX(100%);
        transition: transform 0.25s ease;
        display: flex;
        flex-direction: column;
        box-shadow: -24px 0 60px rgba(0, 0, 0, 0.08);
      }
      .drawer.open {
        transform: translateX(0);
      }
      .drawer .cardHeader {
        padding: 14px 16px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .drawer .drawer-body {
        padding: 12px 14px;
        overflow: auto;
      }
      .close-x {
        appearance: none;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 10px;
        padding: 8px 10px;
        cursor: pointer;
        font-weight: 700;
      }
      .form-col label.small {
        font-size: 12px;
        color: #6b7280;
        margin: 10px 0 6px;
        display: block;
      }
      .help {
        color: #6b7280;
        font-size: 12px;
        margin: 4px 0 10px;
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

          <li id="nav-customers">
            <a href="./users.php">
              <span class="icon"><i class="fa-solid fa-user"></i></span>
              <span class="title">Quản lý khách hàng</span>
            </a>
          </li>

          <li class="hovered" id="nav-categories">
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
                <span
                  id="sb-admin-username"
                  
                   class="inline-clean-8"><?php echo h($admin['name'] ?? $admin['username'] ?? 'Admin'); ?></span
                >
                <span
                  id="sb-admin-role"
                  
                   class="inline-clean-9"><?php echo h((($admin['role'] ?? 'admin') === 'admin') ? 'Quản trị viên' : 'Khách hàng'); ?></span
                >
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
          <h1>Quản lý danh mục</h1>
          <div class="actions">
            <input
              id="q"
              class="input"
              type="text"
              placeholder="Tìm theo tên / mô tả…"
            />
            <button class="btn primary" id="btn-new">+ Thêm danh mục</button>
          </div>
        </div>

        <div class="details">
          <!-- Categories Table Section - Full Width Scoped Fix -->
          <div class="categories-table-section">
            <div class="recentOrders inline-clean-10" >
              <div class="cardHeader">
                <h2>Danh sách danh mục</h2>
                <span id="cat-count" class="badge">0 danh mục</span>
              </div>

              <table class="categories-table">
                <thead>
                  <tr>
                    <td>Tên danh mục</td>
                    <td>Hành động</td>
                  </tr>
                </thead>
                <tbody id="cat-body">
                  <tr>
                    <td
                      colspan="2"
                      
                     class="inline-clean-11">
                      Đang tải…
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Drawer: Form danh mục -->
      <div id="drawer-backdrop" class="drawer-backdrop"></div>
      <aside id="cat-drawer" class="drawer" aria-hidden="true">
        <div class="cardHeader">
          <h2 id="form-title"  class="inline-clean-12">Thêm danh mục</h2>
          <button type="button" class="close-x" id="btn-close-drawer">✕</button>
        </div>
        <div class="drawer-body form-col">
          <form id="cat-form">
            <input type="hidden" id="id" />

            <label class="small">Tên danh mục</label>
            <input
              id="name"
              class="input"
              type="text"
              required
              placeholder="Ví dụ: Sáp vuốt tóc"
            />

            <div class="help">
              Slug danh mục sẽ được sinh tự động từ tên. Trạng thái mặc định: đang sử dụng.
            </div>

            <div class="inline-clean-13">
              <button class="btn primary" type="submit">Lưu</button>
              <button class="btn" id="btn-cancel" type="button">Hủy</button>
            </div>
          </form>
        </div>
      </aside>
    </div>

    <!-- JS chung -->
    <script src="./assets/js/main.js"></script>

    <!-- Logic danh mục -->
    <script src="./assets/js/categories.js"></script>

    <!-- Drawer handlers + Logout -->
    <script>
      // logout
      document
        .getElementById("btn-logout-side")
        ?.addEventListener("click", (e) => {
          e.preventDefault();
          location.href = "./logout.php";
        });

      // Drawer show/hide
      const drawer = document.getElementById("cat-drawer");
      const backdrop = document.getElementById("drawer-backdrop");
      const openBtn = document.getElementById("btn-new");
      const closeBtn = document.getElementById("btn-close-drawer");
      const cancelBtn = document.getElementById("btn-cancel");

      function openDrawer() {
        drawer.classList.add("open");
        backdrop.classList.add("show");
        drawer.setAttribute("aria-hidden", "false");
      }
      function closeDrawer() {
        drawer.classList.remove("open");
        backdrop.classList.remove("show");
        drawer.setAttribute("aria-hidden", "true");
      }

      openBtn?.addEventListener("click", openDrawer);
      closeBtn?.addEventListener("click", closeDrawer);
      cancelBtn?.addEventListener("click", closeDrawer);
      backdrop?.addEventListener("click", closeDrawer);
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeDrawer();
      });

      // Cho JS mở form khi bấm "Sửa"
      window.AdminCategoryDrawer = { open: openDrawer, close: closeDrawer };
    </script>
  </body>
</html>

