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
      integrity="..."
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />

    <title>Phiếu nhập hàng | Nobility 1800s Admin</title>

    <!-- CSS chung Admin -->
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
        margin: 0;
        color: #000;
      }
      .actions {
        display: flex;
        gap: 8px;
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
      .btn.sm {
        padding: 6px 8px;
        border-radius: 8px;
      }
      .input {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        min-width: 220px;
        background: #fff;
        color: #111;
        outline: none;
      }
      .modal .input {
        min-width: 0;
      }
      .table-wrap {
        overflow-x: auto;
      }
      .table-wrap input.input {
        width: 100%;
        min-width: 0;
        max-width: 100%;
      }
      .table-wrap th,
      .table-wrap td {
        word-break: break-word;
      }
      /* Modal item row styling */
      #tblLines {
        width: 100%;
        border-collapse: collapse;
      }
      #tblLines thead td {
        background: #f8fafc;
        font-weight: 700;
        padding: 10px 8px;
        font-size: 12px;
        border-bottom: 1px solid #e5e7eb;
      }
      #tblLines tbody td {
        padding: 8px;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
      }
      /* Column sizing for modal table (4 columns: Product, Price, Qty, Delete) */
      #tblLines tbody td:nth-child(1) {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      #tblLines tbody td:nth-child(2),
      #tblLines tbody td:nth-child(3) {
        width: 120px;
      }
      #tblLines tbody td:nth-child(4) {
        width: 70px;
        text-align: center;
      }
      /* Modal table inputs */
      #tblLines input.input {
        width: 100%;
        box-sizing: border-box;
        min-width: 0;
      }
      .table-wrap input.input.error {
        border-color: #dc2626;
        background: #fee2e2;
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

      /* Modal */
      .modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        display: none;
        z-index: 999;
        align-items: center;
        justify-content: center;
      }
      .modal.show {
        display: flex;
      }
      .box {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        width: min(98vw, 1200px);
        max-width: 98vw;
        max-height: 98vh;
        display: flex;
        flex-direction: column;
      }
      .boxHeader,
      .boxFoot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
      }
      .boxFoot {
        border-top: 1px solid #e5e7eb;
        border-bottom: 0;
      }
      .boxBody {
        padding: 12px;
        overflow: auto;
      }
      .grid {
        display: grid;
        gap: 12px;
      }
      .grid-2 {
        grid-template-columns: 1fr 1fr;
      }
      .grid-3 {
        grid-template-columns: 1fr 1fr 1fr;
      }
      .small {
        font-size: 12px;
        color: #6b7280;
      }
      .total {
        text-align: right;
        margin-top: 8px;
      }
      .table-wrap {
        overflow: auto;
      }
      .suggestions {
        position: relative;
        margin-top: 6px;
      }
      .suggestions-list {
        position: absolute;
        z-index: 10;
        width: 100%;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        max-height: 220px;
        overflow: auto;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      }
      .suggestions-list div {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f2f5;
      }
      .suggestions-list div:last-child {
        border-bottom: none;
      }
      .suggestions-list div:hover {
        background: #f5f7fb;
      }
      .nowrap {
        white-space: nowrap;
      }
      .num {
        text-align: right;
      }
      .muted {
        color: #6b7280;
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

          <li class="hovered" id="nav-imports">
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
          <h1>Phiếu nhập hàng</h1>
          <div class="actions">
            <button class="btn primary" id="btn-new">+ Tạo phiếu nhập</button>
          </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
          <input
            id="f_q"
            class="input"
            placeholder="Tìm mã phiếu / NCC / sản phẩm..."
          />
          <select id="f_status" class="input inline-clean-14" >
            <option value="">— Tất cả trạng thái —</option>
            <option value="draft">Chưa nhập</option>
            <option value="completed">Hoàn thành</option>
          </select>
          <input id="f_date" type="date" class="input" />
          <button class="btn" id="btnFilter">Tìm</button>
        </div>

        <div class="details">
          <div class="recentOrders inline-clean-10" >
            <div class="cardHeader">
              <h2>Danh sách phiếu nhập</h2>
            </div>
            <table>
              <thead>
                <tr>
                  <td>Mã phiếu</td>
                  <td>Ngày</td>
                  <td>Sản phẩm</td>
                  <td class="num">Tổng SL</td>
                  <td class="num">Tổng tiền</td>
                  <td>Trạng thái</td>
                  <td>Hành động</td>
                </tr>
              </thead>
              <tbody id="rcp-body">
                <!-- Dữ liệu sẽ được render bằng JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Phiếu nhập -->
    <div id="pn-modal" class="modal" aria-hidden="true">
      <div class="box">
        <div class="boxHeader">
          <h3 id="pn-title"  class="inline-clean-12">Tạo phiếu nhập</h3>
          <div>
            <button class="btn sm" id="btn-close" type="button">Đóng</button>
          </div>
        </div>

        <div class="boxBody">
          <div class="grid">
            <div>
              <label class="small">Ngày nhập</label>
              <input id="pn_date" type="date" class="input" min="" />
            </div>

            <div class="grid-2 inline-clean-15" >
              <div>
                <label class="small">Ghi chú</label>
                <input id="pn_note" class="input" placeholder="Ghi chú" />
              </div>
              <div>
                <label class="small">Tìm sản phẩm (mã/tên)</label>
                <div  class="inline-clean-16">
                  <input
                    id="s_prod"
                    class="input"
                    autocomplete="off"
                    placeholder="Nhập mã hoặc tên..."
                  />
                  <button class="btn" id="btnAddLine" type="button">
                    Thêm
                  </button>
                </div>
                <div id="prod-suggestions" class="suggestions"></div>
                <div class="small">
                  Tự chọn sản phẩm khớp đầu tiên theo từ khóa (có thể sửa trực
                  tiếp dòng).
                </div>
              </div>
            </div>
          </div>

          <div class="table-wrap inline-clean-17" >
            <table  id="tblLines" class="inline-clean-18">
              <thead>
                <tr>
                  <td  class="inline-clean-2">Sản phẩm</td>
                  <td class="num inline-clean-19" >Giá nhập</td>
                  <td class="num inline-clean-19" >Số lượng</td>
                  <td  class="inline-clean-20"></td>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div class="total">
            <b>Tổng SL:</b> <span id="sumQty">0</span>
            &nbsp; | &nbsp;
            <b>Tổng tiền:</b> <span id="sumCost">0</span>
          </div>
        </div>

        <div class="boxFoot">
          <div class="small" id="pn-meta"></div>
          <div>
            <button class="btn" id="btnSave" type="button">Lưu</button>

          </div>
        </div>
      </div>
    </div>

    <!-- JS chung -->
    <script src="./assets/js/main.js"></script>

    <!-- Ionicons nếu có -->
    <script type="module" src="./assets/js/ionicons.esm.js"></script>
    <script nomodule src="./assets/js/ionicons.js"></script>

    <!-- Logic Phiếu nhập -->
    <script src="./assets/js/imports.js?v=20260331-2"></script>

    <script>
      // Logout
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

