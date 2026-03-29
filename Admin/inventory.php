<?php
require_once __DIR__ . '/../includes/common.php';
require_admin();
$admin = admin_user();

// ===== Embed dữ liệu inventory vào JS trực tiếp =====
// (bypass API auth check bằng cách embed ở PHP level)
$output_products = [];
$output_categories = [];
$output_transactions = [];

try {
    require_once __DIR__ . '/../includes/db.php';

    // === PRODUCTS ===
    $sql_products = "
        SELECT id, sku as code, name, category_id as categoryId, quantity 
        FROM products 
        WHERE status = 'selling'
        ORDER BY name ASC
    ";
    $products = db_fetch_all($sql_products);
    
    $output_products = array_map(function ($p) {
        return [
            'id' => (int)$p['id'],
            'code' => $p['code'],
            'name' => $p['name'],
            'categoryId' => (int)$p['categoryId'],
            'qty' => (int)$p['quantity'],
        ];
    }, $products);

    // === CATEGORIES ===
    $sql_categories = "
        SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC
    ";
    $categories = db_fetch_all($sql_categories);
    
    $output_categories = array_map(function ($c) {
        return [
            'id' => (int)$c['id'],
            'name' => $c['name'],
            'status' => 'active',
        ];
    }, $categories);

    // === STOCK MOVEMENTS ===
    $sql_movements = "
      SELECT
        id,
        movement_type AS type,
        product_id AS productId,
        quantity AS qty,
        occurred_at AS createdAt,
        ref_code AS code
      FROM stock_movements
      ORDER BY occurred_at DESC, id DESC
    ";
    $all_tx = db_fetch_all($sql_movements);

    $output_transactions = array_map(function ($t) {
        return [
            'id' => (int)$t['id'],
            'type' => $t['type'],
            'productId' => (int)$t['productId'],
            'qty' => (int)$t['qty'],
            'createdAt' => $t['createdAt'],
            'date' => $t['createdAt'],
            'code' => $t['code'],
        ];
    }, $all_tx);

} catch (Exception $e) {
    error_log('ERROR loading inventory data: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản lý tồn kho & Báo cáo | Nobility 1800s Admin</title>

    <!-- CSS admin chung -->
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />

    <!-- Font Awesome -->
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
        margin: 8px 0 16px;
      }
      .page-header h1 {
        font-size: 30px;
        margin: 0;
        color: #000;
      }
      .toolbar {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 10px;
        align-items: end;
        margin: 12px 0;
      }
      .box {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 12px;
      }
      .box h3 {
        margin: 0 0 8px;
        font-size: 16px;
      }
      .input,
      select,
      input[type="date"],
      input[type="number"],
      input[type="datetime-local"] {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fff;
        width: 100%;
        outline: none;
      }
      .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fff;
        font-weight: 700;
        cursor: pointer;
        font-size: 13px;
      }
      .btn.primary {
        background: #111827;
        color: #fff;
        border-color: #111827;
      }
      .btn.ghost {
        background: #fff;
      }
      /* Override để cột Tồn tab 2 không bị min-width 280px */
      #report-body tr td:last-child {
        min-width: auto;
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
        padding: 10px;
        color: #1f2a44;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
      }
      tr:hover {
        background: #fafafa;
      }
      /* Inventory table - refined styling */
      .inventory-table {
        width: 100%;
      }
      .inventory-table thead td {
        font-weight: 700;
        font-size: 12px;
      }
      /* All columns: left-align by default */
      .inventory-table td {
        text-align: left;
      }
      /* Numeric columns: right-align */
      .inventory-table td.num {
        text-align: right;
        font-variant-numeric: tabular-nums;
      }
      /* Tồn column - refined emphasis: bold text only */
      .inventory-table tbody td:nth-child(7),
      .inventory-table--low tbody td:nth-child(5) {
        font-weight: 600;
      }
      /* Column widths - balanced and intentional */
      .inventory-table td:nth-child(1) {
        width: 35px;
      }
      .inventory-table td:nth-child(2) {
        width: 60px;
      }
      .inventory-table td:nth-child(3) {
        flex: 1;
        min-width: 200px;
      }
      .inventory-table td:nth-child(4) {
        width: 100px;
      }
      /* Numeric columns: consistent width */
      .inventory-table--report td:nth-child(5),
      .inventory-table--report td:nth-child(6),
      .inventory-table--report td:nth-child(7) {
        width: 75px;
      }
      .inventory-table--low td:nth-child(5),
      .inventory-table--low td:nth-child(6) {
        width: 100px;
      }
      .num {
        text-align: right;
        white-space: nowrap;
      }
      .status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        font-size: 12px;
      }
      .status.low {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
      }
      .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
      }
      .grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
      }
      .label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 6px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
      }
      .muted {
        color: #6b7280;
        font-size: 12px;
      }
      .right {
        margin-left: auto;
        text-align: right;
      }
      .nowrap {
        white-space: nowrap;
      }
    </style>
      <link rel="stylesheet" href="../assets/css/clean-styles.css" />
      <link rel="stylesheet" href="./assets/css/admin-unified-theme.css" />
</head>

  <body>
    <div class="container">
      <!-- Sidebar -->
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

          <li id="nav-inventory" class="hovered">
            <a href="./inventory.php">
              <span class="icon"
                ><i class="fa-solid fa-boxes-stacked"></i
              ></span>
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
          <h1>Quản lý tồn kho &amp; Báo cáo</h1>
          <div id="summary" class="muted right"></div>
        </div>

        <!-- Khối 1: Tra cứu tồn tại thời điểm -->
        <div class="box inline-clean-21" >
          <h3>Tra cứu số lượng tồn tại một thời điểm</h3>
          <div class="toolbar">
            <div  class="inline-clean-22">
              <div class="label">Sản phẩm</div>
              <select id="f-product" class="input"></select>
            </div>
            <div  class="inline-clean-23">
              <div class="label">Loại</div>
              <select id="f-category" class="input"></select>
            </div>
            <div  class="inline-clean-23">
              <div class="label">Thời điểm</div>
              <input id="f-at" type="datetime-local" class="input" />
            </div>
            <div
              
             class="inline-clean-24">
              <button id="btn-check" class="btn primary">
                <i class="fa-solid fa-magnifying-glass-chart"></i>
                <span>Xem tồn</span>
              </button>
              <button id="btn-reset-check" class="btn ghost">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Xóa tìm</span>
              </button>
            </div>
          </div>
          <div id="at-result" class="muted"></div>
        </div>

        <!-- Khối 2: Báo cáo nhập – xuất – tồn -->
        <div class="box inline-clean-21" >
          <h3>Báo cáo nhập – xuất – tồn theo khoảng thời gian</h3>
          <div class="toolbar">
            <div  class="inline-clean-23">
              <div class="label">Từ ngày</div>
              <input id="r-from" type="date" class="input" />
            </div>
            <div  class="inline-clean-23">
              <div class="label">Đến ngày</div>
              <input id="r-to" type="date" class="input" />
            </div>
            <div  class="inline-clean-23">
              <div class="label">Loại</div>
              <select id="r-category" class="input"></select>
            </div>
            <div
              
             class="inline-clean-25">
              <button id="btn-filter-report" class="btn primary">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Tìm kiếm</span>
              </button>
              <button id="btn-reset-report" class="btn ghost">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Xóa tìm</span>
              </button>
            </div>
          </div>



          <div class="recentOrders inline-clean-26" >
            <table class="inventory-table inventory-table--report">
              <thead>
                <tr>
                  <td>#</td>
                  <td>Mã</td>
                  <td>Tên sản phẩm</td>
                  <td>Loại</td>
                  <td class="num">Nhập</td>
                  <td class="num">Xuất</td>
                  <td class="num">Tồn</td>
                </tr>
              </thead>
              <tbody id="report-body">
                <tr>
                  <td
                    colspan="7"
                    
                   class="inline-clean-27">
                    Chưa có dữ liệu
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Khối 3: Cảnh báo sắp hết -->
        <div class="box">
          <h3>Cảnh báo sắp hết hàng</h3>
          <div class="grid-3 inline-clean-28" >
            <div>
              <div class="label">Ngưỡng cảnh báo (≤)</div>
              <input
                id="low-threshold"
                type="number"
                class="input"
                min="0"
                step="1"
                placeholder="VD: 5"
              />
            </div>
            <div>
              <div class="label">Loại</div>
              <select id="low-category" class="input"></select>
            </div>
            <div  class="inline-clean-29">
              <button id="btn-check-low" class="btn primary">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Quét tồn hiện tại</span>
              </button>
              <span class="muted">
                Kiểm tra theo số tồn <strong>hiện tại</strong>.
              </span>
            </div>
          </div>

          <div class="recentOrders">
            <table class="inventory-table inventory-table--low">
              <thead>
                <tr>
                  <td>#</td>
                  <td>Mã</td>
                  <td>Tên sản phẩm</td>
                  <td>Loại</td>
                  <td class="num">Tồn hiện tại</td>
                  <td>Trạng thái</td>
                </tr>
              </thead>
              <tbody id="low-body">
                <tr>
                  <td
                    colspan="6"
                    
                   class="inline-clean-27">
                    Chưa quét
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- JS chung -->
    <script src="./assets/js/main.js"></script>

    <!-- Seed sản phẩm (dùng chung cho tồn kho / báo cáo) -->
    <script src="./assets/js/products.seed.js"></script>

    <!-- Embed inventory data trực tiếp từ PHP (bypass API auth) -->
    <script>
      window.INVENTORY_EMBEDDED_DATA = {
        products: <?php echo json_encode($output_products, JSON_UNESCAPED_UNICODE); ?>,
        categories: <?php echo json_encode($output_categories, JSON_UNESCAPED_UNICODE); ?>,
        transactions: <?php echo json_encode($output_transactions, JSON_UNESCAPED_UNICODE); ?>,
      };
      
      console.log('📦 EMBEDDED DATA FROM PHP:');
      console.log('  products:', window.INVENTORY_EMBEDDED_DATA.products.length, 'items');
      console.log('  categories:', window.INVENTORY_EMBEDDED_DATA.categories.length, 'items');
      console.log('  transactions:', window.INVENTORY_EMBEDDED_DATA.transactions.length, 'items');
      
      if (window.INVENTORY_EMBEDDED_DATA.products.length > 0) {
        console.log('  Sample product:', window.INVENTORY_EMBEDDED_DATA.products[0]);
      }
      if (window.INVENTORY_EMBEDDED_DATA.transactions.length > 0) {
        console.log('  Sample transaction:', window.INVENTORY_EMBEDDED_DATA.transactions[0]);
      }
      
      // Seed localStorage ngay lập tức từ embedded data
      if (window.INVENTORY_EMBEDDED_DATA.products.length > 0) {
        localStorage.setItem('admin.products', JSON.stringify(window.INVENTORY_EMBEDDED_DATA.products));
        console.log('✅ Seeded admin.products from embedded data');
      } else {
        console.warn('⚠️ NO PRODUCTS TO SEED - embedded data is empty!');
      }
      
      if (window.INVENTORY_EMBEDDED_DATA.categories.length > 0) {
        localStorage.setItem('admin.categories', JSON.stringify(window.INVENTORY_EMBEDDED_DATA.categories));
        console.log('✅ Seeded admin.categories from embedded data');
      } else {
        console.warn('⚠️ NO CATEGORIES TO SEED - embedded data is empty!');
      }
      
      if (window.INVENTORY_EMBEDDED_DATA.transactions.length > 0) {
        localStorage.setItem('admin.stock', JSON.stringify(window.INVENTORY_EMBEDDED_DATA.transactions));
        console.log('✅ Seeded admin.stock from embedded data');
      } else {
        console.warn('⚠️ NO TRANSACTIONS TO SEED - embedded data is empty!');
      }
    </script>

    <!-- Logic tồn kho & báo cáo -->
    <script src="./assets/js/inventory.js?v=20260331-3"></script>

    <!-- Debug: Kiểm tra trạng thái dữ liệu sau khi boot -->
    <script>
      setTimeout(() => {
        console.log("\n" + "=".repeat(60));
        console.log("📊 ===== DEBUG AFTER BOOT (1s later) ===== ");
        console.log("=".repeat(60));
        
        // Check what's in localStorage
        const prods = JSON.parse(localStorage.getItem('admin.products') || '[]');
        const cats = JSON.parse(localStorage.getItem('admin.categories') || '[]');
        const txs = JSON.parse(localStorage.getItem('admin.stock') || '[]');
        
        console.log('localStorage admin.products:', prods.length, 'items');
        console.log('localStorage admin.categories:', cats.length, 'items');
        console.log('localStorage admin.stock:', txs.length, 'items');
        
        if (prods.length === 0) {
          console.error('❌ admin.products is EMPTY - data not seeded!');
        } else {
          console.log('Sample products:');
          prods.slice(0, 2).forEach(p => console.log('  -', p.code, 'qty=' + p.qty));
        }
        
        if (txs.length === 0) {
          console.warn('⚠️ admin.stock is EMPTY - no transactions');
        } else {
          console.log('Sample transactions:');
          txs.slice(0, 2).forEach(t => console.log('  -', t.type, 't[productId=' + t.productId + '] qty=' + t.qty));
        }
        
        console.log("=".repeat(60) + "\n");
      }, 1000);
    </script>

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

