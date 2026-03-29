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
    <title>Quản lý đơn hàng | Nobility 1800s Admin</title>

    <!-- CSS admin chung -->
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />

    <!-- Font Awesome (đồng bộ với các trang khác) -->
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
      .toolbar {
        display: flex;
        gap: 10px;
        align-items: center;
        margin: 12px 0;
        flex-wrap: wrap;
      }
      .input {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fff;
        color: #111;
        outline: none;
        font-size: 13px;
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
        vertical-align: top;
        font-size: 13px;
      }
      tr:hover {
        background: #fafafa;
      }
      .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 6px 9px;
        background: #fff;
        cursor: pointer;
        font-weight: 600;
        font-size: 12px;
        white-space: nowrap;
      }
      .btn.primary {
        background: #1f235a;
        color: #fff;
        border-color: #1f235a;
      }
      .status-chip {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        border: 1px solid #e5e7eb;
      }
      .st-new {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1e3a8a;
      }
      .st-confirmed {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
      }
      .st-delivered {
        background: #f0fdf4;
        border-color: #bbf7d0;
        color: #166534;
      }
      .st-canceled {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
      }
      .small {
        font-size: 12px;
        color: #6b7280;
      }

      /* Modal */
      .modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 998;
      }
      .modal.show {
        display: flex;
      }
      .modal .panel {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        width: min(920px, 96vw);
        max-height: 90vh;
        display: flex;
        flex-direction: column;
      }
      .panel .hd {
        padding: 14px 16px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
      }
      .panel .bd {
        padding: 12px 16px;
        overflow: auto;
      }
      .grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
      }
      .kv {
        display: flex;
        gap: 8px;
        margin-bottom: 4px;
      }
      .kv .k {
        width: 120px;
        color: #6b7280;
      }
      .line {
        height: 1px;
        background: #eef2f7;
        margin: 10px 0;
      }
      .cardHeader h2 {
        font-size: 18px;
        margin: 0 0 8px;
      }
      .details {
        margin-top: 4px;
      }

      /* Fix: Orders page table width bug */
      /* Override global .page table display: block rule */
      .page > .details > .recentOrders table {
        display: table !important;
        overflow-x: visible !important;
        width: 100% !important;
      }

      /* Ensure card and table stretch full width */
      .page > .details {
        padding: 0;
        margin-top: 12px;
      }

      .page > .details > .recentOrders {
        width: 100%;
        overflow: visible;
      }

      /* Remove double border from table */
      .page > .details > .recentOrders table {
        border: none;
        border-collapse: collapse;
        background: transparent;
      }

      /* Move border/border-radius to card, not table */
      .page > .details > .recentOrders {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
      }

      .page > .details > .recentOrders thead td {
        padding: 12px 16px;
      }

      .page > .details > .recentOrders tbody td {
        padding: 12px 16px;
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

          <li class="hovered" id="nav-orders">
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
          <h1>Quản lý đơn hàng</h1>
          <div class="actions"></div>
        </div>

        <div class="toolbar">
          <input
            id="q"
            class="input"
            type="text"
            placeholder="Tìm mã đơn / tên / SĐT / địa chỉ..."
          />
          <input id="from" class="input" type="date" />
          <input id="to" class="input" type="date" />
          <select id="status" class="input inline-clean-14" >
            <option value="">— Tất cả trạng thái —</option>
            <option value="new">Chưa xử lý</option>
            <option value="confirmed">Đã xác nhận</option>
            <option value="delivered">Đã giao thành công</option>
            <option value="canceled">Đã huỷ</option>
          </select>
          <select id="sortWard" class="input inline-clean-30" >
            <option value="">— Sắp xếp theo phường/xã —</option>
            <option value="asc">Phường/Xã (A → Z)</option>
            <option value="desc">Phường/Xã (Z → A)</option>
          </select>
          <button id="btnFilter" class="btn">
            <i class="fa-solid fa-filter"></i>
            <span>Tìm</span>
          </button>
        </div>

        <div class="details">
          <div class="recentOrders inline-clean-10" >
            <div class="cardHeader">
              <h2>Danh sách đơn hàng</h2>
            </div>
            <table>
              <thead>
                <tr>
                  <td>Mã đơn</td>
                  <td>Ngày</td>
                  <td>Khách hàng</td>
                  <td>Địa chỉ</td>
                  <td  class="inline-clean-31">SL</td>
                  <td  class="inline-clean-31">Tổng tiền</td>
                  <td>Trạng thái</td>
                  <td>Hành động</td>
                </tr>
              </thead>
              <tbody id="od-body">
                <!-- DỮ LIỆU MẪU TĨNH (luôn luôn có) -->
                <tr>
                  <td><b>OD-20241028-001</b></td>
                  <td>2024-10-28</td>
                  <td>
                    Nguyễn Văn A<br /><span class="small">0900000001</span>
                  </td>
                  <td>
                    12 Lê Lợi,<br />
                    Phường Bến Nghé, Quận 1, TP. HCM
                  </td>
                  <td  class="inline-clean-31">3</td>
                  <td  class="inline-clean-31">430.000</td>
                  <td>
                    <span class="status-chip st-new">Chưa xử lý</span>
                  </td>
                  <td>—</td>
                </tr>
                <tr>
                  <td><b>OD-20241029-002</b></td>
                  <td>2024-10-29</td>
                  <td>Trần Thị B<br /><span class="small">0900000002</span></td>
                  <td>
                    45 Nguyễn Huệ,<br />
                    Phường Bến Nghé, Quận 1, TP. HCM
                  </td>
                  <td  class="inline-clean-31">2</td>
                  <td  class="inline-clean-31">440.000</td>
                  <td>
                    <span class="status-chip st-new">Chưa xử lý</span>
                  </td>
                  <td>—</td>
                </tr>
                <tr>
                  <td><b>OD-20241101-003</b></td>
                  <td>2024-11-01</td>
                  <td>Lê Quốc C<br /><span class="small">0900000003</span></td>
                  <td>
                    23 Cách Mạng Tháng 8,<br />
                    Phường 11, Quận 3, TP. HCM
                  </td>
                  <td  class="inline-clean-31">1</td>
                  <td  class="inline-clean-31">250.000</td>
                  <td>
                    <span class="status-chip st-confirmed">Đã xác nhận</span>
                  </td>
                  <td>—</td>
                </tr>
                <tr>
                  <td><b>OD-20241102-004</b></td>
                  <td>2024-11-02</td>
                  <td>Phạm Thị D<br /><span class="small">0900000004</span></td>
                  <td>
                    89 Trường Chinh,<br />
                    Phường Tây Thạnh, Tân Phú, TP. HCM
                  </td>
                  <td  class="inline-clean-31">2</td>
                  <td  class="inline-clean-31">360.000</td>
                  <td>
                    <span class="status-chip st-delivered">Đã giao thành công</span>
                  </td>
                  <td>—</td>
                </tr>
                <tr>
                  <td><b>OD-20241103-005</b></td>
                  <td>2024-11-03</td>
                  <td>Đỗ Văn E<br /><span class="small">0900000005</span></td>
                  <td>
                    56 Phan Đăng Lưu,<br />
                    Phường 5, Phú Nhuận, TP. HCM
                  </td>
                  <td  class="inline-clean-31">1</td>
                  <td  class="inline-clean-31">270.000</td>
                  <td>
                    <span class="status-chip st-canceled">Đã huỷ</span>
                  </td>
                  <td>—</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal chi tiết đơn -->
    <div id="od-modal" class="modal" aria-hidden="true">
      <div class="panel">
        <div class="hd">
          <h3 id="od-title"  class="inline-clean-12">Chi tiết đơn</h3>
          <button type="button" id="btnClose" class="btn" style="background: #f0f2f5; border-color: #e5e7eb; color: #666;">✕</button>
        </div>
        <div class="bd">
          <div class="grid">
            <div>
              <div class="kv">
                <div class="k">Mã đơn</div>
                <div id="vCode">—</div>
              </div>
              <div class="kv">
                <div class="k">Ngày</div>
                <div id="vDate">—</div>
              </div>
              <div class="kv">
                <div class="k">Trạng thái</div>
                <div id="vStatus">—</div>
              </div>
              <div class="kv">
                <div class="k">Ghi chú</div>
                <div id="vNote">—</div>
              </div>
            </div>
            <div>
              <div class="kv">
                <div class="k">Khách hàng</div>
                <div id="vCus">—</div>
              </div>
              <div class="kv">
                <div class="k">SĐT</div>
                <div id="vPhone">—</div>
              </div>
              <div class="kv">
                <div class="k">Địa chỉ</div>
                <div id="vAddr">—</div>
              </div>
            </div>
          </div>
          <div class="line"></div>
          <h4  class="inline-clean-33">Sản phẩm</h4>
          <table  class="inline-clean-18">
            <thead>
              <tr>
                <td  class="inline-clean-19">Mã SP</td>
                <td  class="inline-clean-34">Tên SP</td>
                <td  class="inline-clean-31">Giá</td>
                <td  class="inline-clean-31">SL</td>
                <td  class="inline-clean-31">Thành tiền</td>
              </tr>
            </thead>
            <tbody id="vItems"></tbody>
            <tfoot>
              <tr>
                <td colspan="4"  class="inline-clean-35">
                  Tổng cộng
                </td>
                <td id="vTotal"  class="inline-clean-35">
                  0
                </td>
              </tr>
            </tfoot>
          </table>
          <div class="line"></div>
          <div style="display: flex; gap: 10px; align-items: center;">
            <label style="color: #666; font-weight: 600;">Cập nhật trạng thái:</label>
            <select id="od-status" class="input" style="padding: 8px 10px; font-size: 13px;">
              <option value="new">Chưa xử lý</option>
              <option value="confirmed">Đã xác nhận</option>
              <option value="delivered">Đã giao thành công</option>
              <option value="canceled">Đã huỷ</option>
            </select>
            <button id="btnUpdateStatus" class="btn primary" style="padding: 8px 12px;">Lưu</button>
          </div>
        </div>
      </div>
    </div>

    <!-- JS chung -->
    <script src="./assets/js/main.js"></script>

    <!-- Logic đơn hàng -->
    <script src="./assets/js/order.js"></script>

    <script>
      // Đăng xuất
      document
        .getElementById("btn-logout-side")
        ?.addEventListener("click", function (e) {
          e.preventDefault();
          sessionStorage.removeItem("session.user");
          location.href = "./login.php";
        });
    </script>
  </body>
</html>

