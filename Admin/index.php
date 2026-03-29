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

    <!-- Font Awesome (đường dẫn TƯƠNG ĐỐI) -->
    <link
      rel="stylesheet"
      href="../fontawesome-free-6.7.2-web/css/all.min.css"
    />

    <!-- CSS admin chung (TƯƠNG ĐỐI) -->
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />

    <title>Bảng điều khiển dành cho quản trị viên</title>

    <!-- Một chút CSS tinh chỉnh cho Dashboard -->
    <style>
      :root {
        --bg-1: #f4f7fb;
        --bg-2: #eef3ff;
        --text: #0f172a;
        --muted: #64748b;
        --line: #e2e8f0;
        --card: #ffffff;
        --brand: #2b3ea8;
        --brand-2: #1877f2;
        --teal: #0f766e;
        --amber: #b45309;
      }

      body {
        background:
          radial-gradient(900px 420px at 92% -8%, rgba(24, 119, 242, 0.12), transparent 70%),
          radial-gradient(800px 360px at -4% 6%, rgba(43, 62, 168, 0.1), transparent 68%),
          linear-gradient(180deg, var(--bg-1) 0%, var(--bg-2) 100%);
      }

      .main {
        padding: 16px;
      }

      .dashboard-page {
        padding: 8px 8px 24px;
        animation: dashboard-fade-up 0.45s ease;
      }

      .dashboard-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
      }
      .dashboard-header h1 {
        margin: 0;
        font-size: 24px;
        color: var(--text);
      }
      .dashboard-header .sub {
        margin-top: 4px;
        font-size: 13px;
        color: var(--muted);
      }
      .dashboard-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
      }
      .dashboard-actions .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 999px;
        border: 1px solid #d7e3ff;
        background: #ffffff;
        font-size: 12px;
        cursor: pointer;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.08);
        transition: transform 0.16s ease, box-shadow 0.2s ease, border-color 0.2s ease;
      }
      .dashboard-actions .btn.primary {
        background: linear-gradient(135deg, var(--brand), var(--brand-2));
        border-color: transparent;
        color: #ffffff;
      }
      .dashboard-actions .btn:hover {
        transform: translateY(-1px);
        border-color: #bfd4ff;
        box-shadow: 0 10px 18px rgba(24, 119, 242, 0.15);
      }
      .dashboard-actions .btn i {
        font-size: 13px;
      }

      /* Làm cardBox nhìn đều & thoáng hơn */
      .cardBox {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 22px;
      }
      .card {
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        border: 1px solid #dbe6ff;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        transition: transform 0.22s ease, box-shadow 0.24s ease;
      }
      .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 36px rgba(30, 64, 175, 0.16);
      }
      .card .numbers {
        font-size: 25px;
        color: #111827;
      }
      .card .cardName {
        font-size: 13px;
        color: #64748b;
      }
      .card .iconBx {
        font-size: 30px;
        opacity: 0.85;
        color: #365ccf;
      }

      .cardBox .card:nth-child(2) .iconBx {
        color: #0f766e;
      }
      .cardBox .card:nth-child(3) .iconBx {
        color: #b45309;
      }
      .cardBox .card:nth-child(4) .iconBx {
        color: #7c3aed;
      }

      /* Bảng dưới: thêm nền nhẹ, bo góc */
      .details {
        display: grid;
        grid-template-columns: minmax(0, 2.2fr) minmax(0, 1.3fr);
        gap: 18px;
      }
      @media (max-width: 960px) {
        .details {
          grid-template-columns: 1fr;
        }
      }
      .recentOrders,
      .recentCustomers {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #dbe4f5;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
        padding: 14px 14px 6px;
      }
      .recentOrders table,
      .recentCustomers table {
        margin-top: 8px;
      }
      .recentOrders .cardHeader,
      .recentCustomers .cardHeader {
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .recentOrders .cardHeader h2,
      .recentCustomers .cardHeader h2 {
        margin: 0;
        font-size: 16px;
        color: #0f172a;
      }
      .recentOrders .btn,
      .recentCustomers .btn {
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        border: 1px solid #d6e3ff;
        background: #f8fbff;
        color: #1e3a8a;
      }

      .recentOrders tbody tr,
      .recentCustomers tr {
        transition: background-color 0.18s ease;
      }
      .recentOrders tbody tr:hover,
      .recentCustomers tr:hover {
        background: #f8fbff;
      }

      .recentOrders thead td {
        font-weight: 700;
        color: #1e293b;
      }

      .recentOrders .status {
        font-weight: 700;
        border-width: 1px;
      }

      .recentOrders .status.delivered {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
      }
      .recentOrders .status.returned {
        background: #eef2ff;
        color: #374151;
        border: 1px solid #c7d2fe;
      }
      .recentOrders .status.inPending {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
      }
      .recentOrders .status.inProgress {
        background: #eef2ff;
        color: #3730a3;
        border: 1px solid #c7d2fe;
      }

      /* Chào mừng admin trong sidebar: thêm hover nhẹ */
      .welcome-admin-tile > div {
        transition: box-shadow 0.2s ease, transform 0.15s ease;
      }
      .welcome-admin-tile > div:hover {
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.2);
        transform: translateY(-1px);
      }

      @keyframes dashboard-fade-up {
        from {
          opacity: 0;
          transform: translateY(8px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
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

          <li class="hovered" id="nav-dashboard">
            <a href="./index.php">
              <span class="icon">
                <i class="fa-solid fa-house"></i>
              </span>
              <span class="title">Bảng điều khiển</span>
            </a>
          </li>

          <li id="nav-customers">
            <a href="./users.php">
              <span class="icon">
                <i class="fa-solid fa-user-group"></i>
              </span>
              <span class="title">Quản lý khách hàng</span>
            </a>
          </li>

          <li id="nav-categories">
            <a href="./categories.php">
              <span class="icon">
                <i class="fa-solid fa-layer-group"></i>
              </span>
              <span class="title">Quản lý danh mục</span>
            </a>
          </li>

          <li id="nav-products">
            <a href="./products.php">
              <span class="icon">
                <i class="fa-solid fa-tags"></i>
              </span>
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
              <span class="icon">
                <i class="fa-solid fa-box-open"></i>
              </span>
              <span class="title">Phiếu nhập hàng</span>
            </a>
          </li>

          <li id="nav-inventory">
            <a href="./inventory.php">
              <span class="icon">
                <i class="fa-solid fa-warehouse"></i>
              </span>
              <span class="title">Quản lý hàng tồn kho</span>
            </a>
          </li>

          <li id="nav-orders">
            <a href="./orders.php">
              <span class="icon">
                <i class="fa-solid fa-receipt"></i>
              </span>
              <span class="title">Quản lý đơn hàng</span>
            </a>
          </li>

          <hr />

          <!-- Chào mừng Admin -->
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
                  class="inline-clean-8"><?php echo h($admin['name'] ?? $admin['username'] ?? 'Admin'); ?></span>
                <span
                  id="sb-admin-role"
                  class="inline-clean-9"><?php echo h((($admin['role'] ?? 'admin') === 'admin') ? 'Quản trị viên' : 'Khách hàng'); ?></span>
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
      <div class="dashboard-page">
        <!-- HEADER TRONG MAIN -->
        <div class="dashboard-header">
          <div>
            <h1>Bảng điều khiển</h1>
            <div class="sub">
              Tổng quan nhanh về lượt xem, đơn hàng và khách hàng của Nobility
              1800s.
            </div>
          </div>
          <div class="dashboard-actions">
            <button
              class="btn primary"
              type="button"
              onclick="location.href='./orders.php';"
            >
              <i class="fa-solid fa-receipt"></i>
              <span>Xem đơn hàng</span>
            </button>
            <button
              class="btn"
              type="button"
              onclick="location.href='./products.php';"
            >
              <i class="fa-solid fa-tags"></i>
              <span>Quản lý sản phẩm</span>
            </button>
          </div>
        </div>

        <!-- THỐNG KÊ NHANH -->
        <div class="cardBox">
          <div class="card">
            <div>
              <div class="numbers">1,504</div>
              <div class="cardName">Lượt xem theo ngày</div>
            </div>
            <div class="iconBx">
              <ion-icon name="eye-outline"></ion-icon>
            </div>
          </div>

          <div class="card">
            <div>
              <div class="numbers">80</div>
              <div class="cardName">Đơn hàng trong ngày</div>
            </div>
            <div class="iconBx">
              <ion-icon name="cart-outline"></ion-icon>
            </div>
          </div>

          <div class="card">
            <div>
              <div class="numbers">284</div>
              <div class="cardName">Bình luận / đánh giá</div>
            </div>
            <div class="iconBx">
              <ion-icon name="chatbubbles-outline"></ion-icon>
            </div>
          </div>

          <div class="card">
            <div>
              <div class="numbers">36.363.363đ</div>
              <div class="cardName">Doanh thu hôm nay</div>
            </div>
            <div class="iconBx">
              <ion-icon name="cash-outline"></ion-icon>
            </div>
          </div>
        </div>

        <!-- KHU VỰC BẢNG -->
        <div class="details">
          <!-- ĐƠN HÀNG GẦN ĐÂY -->
          <div class="recentOrders">
            <div class="cardHeader">
              <h2>Đơn hàng gần đây</h2>
              <a href="./orders.php" class="btn">Xem tất cả</a>
            </div>
            <table>
              <thead>
                <tr>
                  <td>Tên sản phẩm</td>
                  <td>Giá</td>
                  <td>Thanh toán</td>
                  <td>Trạng thái</td>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Sáp vuốt tóc Apestomen Nitro</td>
                  <td>300.000đ</td>
                  <td>Đã thanh toán</td>
                  <td><span class="status delivered">Đã vận chuyển</span></td>
                </tr>
                <tr>
                  <td>Sáp By Vilain Gold Digger</td>
                  <td>449.000đ</td>
                  <td>Chưa thanh toán</td>
                  <td><span class="status returned">Đã trả hàng</span></td>
                </tr>
                <tr>
                  <td>Gôm xịt tóc Kevin Murphy Session</td>
                  <td>467.000đ</td>
                  <td>Đã thanh toán</td>
                  <td>
                    <span class="status inPending">Đang chờ xử lý</span>
                  </td>
                </tr>
                <tr>
                  <td>Bột tạo phồng Patricks HP1 Hair Powder</td>
                  <td>1.000.000đ</td>
                  <td>Đã thanh toán</td>
                  <td><span class="status inProgress">Đang xử lý</span></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- KHÁCH HÀNG GẦN ĐÂY -->
          <div class="recentCustomers">
            <div class="cardHeader">
              <h2>Khách hàng gần đây</h2>
            </div>
            <table>
              <tr>
                <td width="60px">
                  <div class="imgBx">
                    <img src="./assets/img/customer/John.jpg" alt="John" />
                  </div>
                </td>
                <td>
                  <h4>John Doe <br /><span>Vương quốc Anh</span></h4>
                </td>
              </tr>
              <tr>
                <td width="60px">
                  <div class="imgBx">
                    <img src="./assets/img/customer/David.jpg" alt="David" />
                  </div>
                </td>
                <td>
                  <h4>David <br /><span>Ý</span></h4>
                </td>
              </tr>
              <tr>
                <td width="60px">
                  <div class="imgBx">
                    <img src="./assets/img/customer/David.jpg" alt="David" />
                  </div>
                </td>
                <td>
                  <h4>David <br /><span>Ý</span></h4>
                </td>
              </tr>
              <tr>
                <td width="60px">
                  <div class="imgBx">
                    <img src="./assets/img/customer/Emily.jpg" alt="Emily" />
                  </div>
                </td>
                <td>
                  <h4>Emily <br /><span>Pháp</span></h4>
                </td>
              </tr>
            </table>
          </div>
        </div>

        <!-- Khối demo khách hàng (ẩn) -->
        <div id="customers-content"  class="inline-clean-3">
          <div class="details customer-list">
            <div class="recentOrders">
              <div class="cardHeader">
                <h2>Danh sách Khách hàng</h2>
                <a class="btn" href="./users.php">Sang trang quản lý</a>
              </div>
              <table>
                <thead>
                  <tr>
                    <td>Tên</td>
                    <td>Email</td>
                    <td>Số điện thoại</td>
                    <td>Trạng thái</td>
                    <td>Hành động</td>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>John Doe</td>
                    <td>john.doe@email.com</td>
                    <td>0901234567</td>
                    <td>
                      <span class="status delivered">Hoạt động</span>
                    </td>
                    <td>
                      <a href="#" class="btn btn-action btn-reset">Reset MK</a>
                      <a href="#" class="btn btn-action btn-lock">Khóa</a>
                    </td>
                  </tr>
                  <tr>
                    <td>David</td>
                    <td>david.ita@email.com</td>
                    <td>0907654321</td>
                    <td>
                      <span class="status returned">Đã khóa</span>
                    </td>
                    <td>
                      <a href="#" class="btn btn-action btn-reset">Reset MK</a>
                      <a href="#" class="btn btn-action btn-unlock">Mở khóa</a>
                    </td>
                  </tr>
                  <tr>
                    <td>Emily</td>
                    <td>emily.fr@email.com</td>
                    <td>0988888888</td>
                    <td>
                      <span class="status delivered">Hoạt động</span>
                    </td>
                    <td>
                      <a href="#" class="btn btn-action btn-reset">Reset MK</a>
                      <a href="#" class="btn btn-action btn-lock">Khóa</a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- JS TƯƠNG ĐỐI -->
    <script src="./assets/js/main.js"></script>

    <!-- Ionicons (TƯƠNG ĐỐI, đã copy về assets/js) -->
    <script type="module" src="./assets/js/ionicons.esm.js"></script>
    <script nomodule src="./assets/js/ionicons.js"></script>

    <!-- Đăng xuất -->
    <script>
      (function () {
        const btn2 = document.getElementById("btn-logout-side");
        if (btn2) {
          btn2.addEventListener("click", function (e) {
            e.preventDefault();
            location.href = "./logout.php";
          });
        }
      })();
    </script>
  </body>
</html>

