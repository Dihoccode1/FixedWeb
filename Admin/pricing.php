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
    <!-- Font Awesome local -->
    <link
      rel="stylesheet"
      href="../fontawesome-free-6.7.2-web/css/all.min.css"
    />

    <title>Quản lý giá bán | Nobility 1800s Admin</title>

    <!-- CSS admin chung -->
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />

    <!-- Font Awesome CDN (fallback) -->
    <link
      rel="stylesheet"
      href="../fontawesome-free-6.7.2-web/css/all.min.css"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />

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
        margin: 0;
        color: #000;
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
        padding: 8px 10px;
        min-width: 160px;
        background: #fff;
        color: #111;
        outline: none;
        font-size: 14px;
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
        padding: 10px 12px;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
        font-size: 14px;
      }
      tr:hover {
        background: #fafafa;
      }
      .btn-action {
        padding: 6px 10px;
        border-radius: 8px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid #e5e7eb;
        background: #fff;
      }
      .margin-input {
        text-align: right;
      }
      .price-display {
        font-weight: 700;
      }
      /* Định dạng cột số tiền - căn phải và font tabular-nums */
      .pricing-table tbody td:nth-child(5),
      .pricing-table tbody td:nth-child(7) {
        text-align: right;
        font-variant-numeric: tabular-nums;
      }
      .pricing-table thead td:nth-child(5),
      .pricing-table thead td:nth-child(7) {
        text-align: right;
      }
      /* Cột % Lợi nhuận căn giữa */
      .pricing-table tbody td:nth-child(6),
      .pricing-table thead td:nth-child(6) {
        text-align: center;
      }
    </style>
      <link rel="stylesheet" href="../assets/css/clean-styles.css" />
      <link rel="stylesheet" href="./assets/css/admin-unified-theme.css" />
</head>

  <body>
    <div class="container">
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

          <!-- Màn quản lý giá bán -->
          <li class="hovered" id="nav-pricing">
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
      <div class="topbar">
        <!-- Nút menu đã bị loại bỏ theo yêu cầu -->
        <!-- chỗ này nếu bạn có user info / search thì giữ nguyên như các trang khác -->
      </div>

      <div class="page">
        <div class="page-header">
          <h1>Quản lý giá bán</h1>
        </div>

        <!-- Toolbar: Search + Filter (responsive row layout) -->
        <div class="pricing-toolbar">
          <input
            id="q"
            class="input"
            type="text"
            placeholder="Tìm theo mã / tên sản phẩm..."
          />
          <select id="filter-cat" class="input pricing-filter-cat" >
            <option value="">— Tất cả loại —</option>
          </select>
        </div>

        <div class="details">
          <!-- Pricing Table Section - Full Width Scoped -->
          <div class="pricing-table-section">
            <div class="recentOrders inline-clean-10" >
              <div class="cardHeader"><h2>Bảng giá sản phẩm</h2></div>
              <table class="pricing-table">
                <thead>
                  <tr>
                    <td class="col-id">#</td>
                    <td class="col-code">Mã</td>
                    <td class="col-name">Tên</td>
                    <td class="col-cat">Loại</td>
                    <td class="col-cost">Giá vốn (đ)</td>
                    <td class="col-markup">% Lợi nhuận</td>
                    <td class="col-price">Giá bán (đ)</td>
                    <td class="col-status">Trạng thái</td>
                    <td class="col-actions">Hành động</td>
                  </tr>
                </thead>
                <tbody id="pricing-body">
                  <tr>
                    <td
                      colspan="9"
                      
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
    </div>

    <!-- JS chung -->
    <script src="./assets/js/main.js"></script>

    <!-- Logic quản lý giá bán -->
    <script src="./assets/js/pricing.js"></script>

    <script>
      // Logout
      document
        .getElementById("btn-logout-side")
        ?.addEventListener("click", (e) => {
          e.preventDefault();
          sessionStorage.removeItem("session.user");
          location.href = "./login.php";
        });
    </script>
  </body>
</html>

