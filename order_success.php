<?php
require_once __DIR__ . '/includes/common.php';
$orderNumber = trim($_GET['order_number'] ?? '');
if ($orderNumber === '') {
    http_response_code(404);
    echo '<p>Đơn hàng không tồn tại.</p>';
    exit;
}
$order = db_fetch_one_prepared('SELECT o.*, o.full_name AS recipient_name, o.phone AS recipient_phone, o.shipping_address AS recipient_address, u.full_name AS account_name FROM orders o JOIN users u ON u.id = o.user_id WHERE o.order_number = ? LIMIT 1', 's', [$orderNumber]);
if (!$order) {
    http_response_code(404);
    echo '<p>Đơn hàng không tồn tại.</p>';
    exit;
}
$items = db_fetch_all("SELECT oi.*, p.name, p.sku FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = " . (int)$order['id'] . " ORDER BY oi.id");
$statusLabels = [
    'new' => 'Chờ xử lý',
    'confirmed' => 'Đã xác nhận',
    'shipped' => 'Đã giao',
    'cancelled' => 'Đã huỷ',
];
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Đặt hàng thành công</title>
  <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./bootstrap-4.6.2-dist/css/bootstrap.css')); ?>">
  <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>">
  <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/base.css')); ?>">
  <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./fontawesome-free-6.7.2-web/css/all.min.css')); ?>" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    body {
      margin: 0;
      background: #f9fafb;
      color: #1a1a1a;
      font-family: system-ui, 'Segoe UI', Roboto, Arial, sans-serif;
    }

    .success-wrap {
      max-width: 1080px;
      margin: 20px auto;
      padding: 0 14px;
    }

    .success-card {
      border-radius: 12px;
      border: 1px solid #f0f1f3;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
      background: #fff;
      padding: 22px;
    }

    .success-title {
      font-weight: 700;
      color: #111;
      margin: 0 0 8px;
      font-size: 28px;
    }

    .order-meta {
      display: grid;
      grid-template-columns: 1fr;
      gap: 10px;
      margin-bottom: 18px;
      color: #555;
      font-size: 14px;
    }

    .order-meta p {
      margin: 0;
      line-height: 1.5;
    }

    .order-meta strong {
      color: #111;
      font-weight: 700;
    }

    .status-chip {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 999px;
      background: #eef5ff;
      color: #0d6efd;
      border: 1px solid #d0e1ff;
      font-weight: 600;
      font-size: 12px;
    }

    .table.success-table {
      margin-bottom: 0;
      border-color: #e5e7eb;
      font-size: 13px;
    }

    .success-table thead th {
      background: #fafbfc;
      color: #333;
      border-bottom-width: 1px;
      border-bottom-color: #e5e7eb;
      font-weight: 700;
      white-space: nowrap;
      padding: 10px;
    }

    .success-table td {
      border-color: #e5e7eb;
      vertical-align: middle;
      padding: 10px;
      color: #555;
    }

    .success-table td strong {
      color: #111;
      font-weight: 700;
    }

    .total-box {
      font-size: 14px;
      color: #333;
      text-align: right;
    }

    .total-box strong {
      color: #d32f2f;
      font-weight: 700;
      font-size: 16px;
    }

    .btn-outline-secondary {
      color: #555;
      border-color: #ddd;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      padding: 10px 16px;
    }

    .btn-outline-secondary:hover,
    .btn-outline-secondary:focus {
      background: #f5f5f5;
      border-color: #bbb;
      color: #333;
    }

    @media (max-width: 768px) {
      .success-wrap { padding: 0 12px; margin: 16px auto; }
      .success-card { padding: 16px; }
      .success-title { font-size: 24px; }
      .total-box { margin-top: 12px; width: 100%; }
      .d-flex { flex-direction: column; gap: 12px; }
      .d-flex > div { width: 100%; }
    }
  </style>
  <script>
    // Clear cart phía client sau khi checkout thành công
    (function() {
      // Lấy email từ cookie SV_AUTH_EMAIL (được set bởi common.php)
      function getCookie(name) {
        const value = '; ' + document.cookie;
        const parts = value.split('; ' + name + '=');
        if (parts.length === 2) return parts.pop().split(';').shift();
        return '';
      }

      const email = getCookie('SV_AUTH_EMAIL');
      if (email) {
        // Xóa localStorage key của user này
        const cartKey = 'sv_cart_user_' + email.toLowerCase();
        try {
          localStorage.removeItem(cartKey);
          console.log('[OrderSuccess] Cleared', cartKey);
        } catch (e) {
          console.warn('[OrderSuccess] Failed to clear localStorage:', e);
        }
      }

      // Reset server cart count
      window.SERVER_CART_COUNT = 0;

      // Trigger event để refresh UI
      try {
        window.dispatchEvent(new CustomEvent('cart:changed'));
      } catch (e) {
        console.warn('[OrderSuccess] Failed to dispatch cart:changed:', e);
      }
    })();
  </script>
</head>
<body>
  <div class="container py-5 success-wrap">
    <div class="card success-card">
      <h1 class="success-title">Đặt hàng thành công!</h1>
      <div class="order-meta">
        <p>Mã đơn của bạn: <strong><?php echo h($order['order_number']); ?></strong></p>
        <p>Trạng thái đơn: <span class="status-chip"><?php echo h($statusLabels[$order['status']] ?? ucfirst($order['status'])); ?></span></p>
        <p>Tên khách: <strong><?php echo h($order['recipient_name'] ?? $order['full_name']); ?></strong></p>
        <p>Điện thoại: <strong><?php echo h($order['recipient_phone'] ?? $order['phone']); ?></strong></p>
        <p>Địa chỉ giao hàng: <strong><?php echo nl2br(h($order['recipient_address'] ?? $order['shipping_address'])); ?></strong></p>
      </div>
      <div class="table-responsive mt-3">
        <table class="table table-bordered success-table">
          <thead>
            <tr>
              <th>Mã SP</th>
              <th>Sản phẩm</th>
              <th class="text-center">Số lượng</th>
              <th class="text-end">Đơn giá</th>
              <th class="text-end">Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?php echo h($item['sku']); ?></td>
                <td><?php echo h($item['name']); ?></td>
                <td class="text-center"><?php echo h($item['quantity']); ?></td>
                <td class="text-end"><?php echo number_format($item['unit_price'], 0, ',', '.'); ?>₫</td>
                <td class="text-end"><?php echo number_format($item['total_price'], 0, ',', '.'); ?>₫</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-4">
        <div>
          <a href="./Product/product.php" class="btn btn-outline-secondary">Tiếp tục mua sắm</a>
        </div>
        <div class="total-box">Tổng đơn: <strong><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>₫</strong></div>
      </div>
    </div>
  </div>
</body>
</html>
<?php exit; ?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Đặt hàng thành công</title>

  <!-- CSS -->
  <link rel="stylesheet" href="./bootstrap-4.6.2-dist/css/bootstrap.css">
  <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>">
  <link rel="stylesheet" href="./assets/css/base.css">
  <link rel="stylesheet" href="./fontawesome-free-6.7.2-web/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <!-- TOPBAR -->
  <?php render_topbar(); ?>

  <!-- MID HEADER -->
  <header class="mid-header">
    <div class="container">
      <div class="header-main">
        <div class="header-left">
          <!-- search: action dùng link tương đối -->
          <form action="./Product/product.php" method="get" class="search-bar" autocomplete="off">
            <input type="text" name="query" placeholder="Tìm kiếm"
                   autocomplete="off" autocapitalize="off" autocorrect="off"
                   spellcheck="false" inputmode="search" />
            <button type="submit" aria-label="Tìm kiếm">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </form>
        </div>

        <div class="header-center">
          <a href="./index.php" class="logo">
            <img src="./assets/images/logo.jpg" alt="GENTLEMAN">
          </a>
        </div>

        <div class="header-right">
          <a href="./cart.php" class="cart-link">
            <i class="fa-solid fa-cart-shopping"></i>
            GIỎ HÀNG (<span class="cart-count">0</span>)
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- MAIN NAV -->
  <nav class="main-nav">
    <ul>
      <li><a href="./index.php">TRANG CHỦ</a></li>
      <li><a href="./about.php">GIỚI THIỆU</a></li>
      <li><a href="./Product/product.php" class="js-products-url">SẢN PHẨM</a></li>
      <li><a href="./news.php">TIN TỨC</a></li>
      <li><a href="./contact.php">LIÊN HỆ</a></li>
    </ul>
  </nav>

  <!-- JS: search header → sang trang sản phẩm với ?q= -->
  <script>
  (function (w, d) {
    function getProductsURL() {
      var a = d.querySelector('nav.main-nav a.js-products-url')
            || d.querySelector('nav.main-nav a[href*="product"]');
      return (a && a.getAttribute('href')) ? a.getAttribute('href') : './Product/product.php';
    }

    var form = d.querySelector('.search-bar');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = form.querySelector('input[name="query"], input[name="q"]');
      var q = (input && input.value || '').trim().replace(/\s+/g, ' ');
      var raw = getProductsURL();
      var url = new URL(raw, w.location.href);
      if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
      w.location.href = url.pathname + (url.search ? url.search : '');
    }, { passive: false });
  })(window, document);
  </script>

  <!-- JS: welcome name bấm vào về profile -->
  <script>
  (function (w, d) {
    function makeWelcomeClickable() {
      var el = d.querySelector('.welcome-user');
      if (el) { el.setAttribute('href', './account/profile.php'); return; }
      var target = d.querySelector('#welcomeName, [data-welcome-name], .js-welcome-name');
      if (!target) return;
      if (target.tagName !== 'A') {
        var a = d.createElement('a');
        a.href = './account/profile.php';
        a.className = 'welcome-link';
        while (target.firstChild) a.appendChild(target.firstChild);
        target.appendChild(a);
      } else {
        target.setAttribute('href', './account/profile.php');
      }
    }
    d.addEventListener('auth:ready', makeWelcomeClickable);
    if (w.AUTH && w.AUTH.ready) makeWelcomeClickable();
  })(window, document);
  </script>

  <!-- CONTENT -->
  <div class="container py-5 text-center">
    <h3>Đặt hàng thành công!</h3>
    <p>Mã đơn của bạn: <strong id="oid">—</strong></p>
    <a class="btn btn-outline-secondary ml-2" href="./Product/product.php">Tiếp tục mua sắm</a>
  </div>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="site-footer">
      <div class="top-footer">
        <div class="container">
          <div class="row">
            <section class="widget-ft">
              <h4 class="title-menu">Thông tin</h4>
              <ul class="list-menu">
                <li class="li_menu"><a href="./index.php">Trang chủ</a></li>
                <li class="li_menu"><a href="./about.php">Giới thiệu</a></li>
                <li class="li_menu"><a href="./Product/product.php">Sản phẩm</a></li>
                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
              </ul>
            </section>

            <section class="widget-ft">
              <h4 class="title-menu">Hỗ trợ</h4>
              <ul class="list-menu">
                <li class="li_menu"><a href="./index.php">Trang chủ</a></li>
                <li class="li_menu"><a href="./about.php">Giới thiệu</a></li>
                <li class="li_menu"><a href="./Product/product.php">Sản phẩm</a></li>
                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
              </ul>
            </section>

            <section class="widget-ft">
              <h4 class="title-menu">Hướng dẫn</h4>
              <ul class="list-menu">
                <li class="li_menu"><a href="./index.php">Trang chủ</a></li>
                <li class="li_menu"><a href="./about.php">Giới thiệu</a></li>
                <li class="li_menu"><a href="./Product/product.php">Sản phẩm</a></li>
                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
              </ul>
            </section>

            <section class="widget-ft">
              <h4 class="title-menu">Chính sách</h4>
              <ul class="list-menu">
                <li class="li_menu"><a href="./index.php">Trang chủ</a></li>
                <li class="li_menu"><a href="./about.php">Giới thiệu</a></li>
                <li class="li_menu"><a href="./Product/product.php">Sản phẩm</a></li>
                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
              </ul>
            </section>

            <section class="wg-logo">
              <h4 class="title-menu">Liên hệ</h4>
              <ul class="contact">
                <li>
                  <span class="txt_content_child">
                    <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
                    140B-Tổ 3, Ấp Xóm Chùa,Tỉnh Tây Ninh
                  </span>
                </li>
                <li class="sdt">
                  <span><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
                  <a href="tel:0338286525">0338286525</a>
                </li>
                <li class="sdt">
                  <span><i class="fa-solid fa-envelope"></i></span>
                  <a href="mailto:thanhloc29052006@gmail.com">thanhloc29052006@gmail.com</a>
                </li>
              </ul>
            </section>
          </div>
        </div>
      </div>

      <div class="mid-footer">
        <div class="container">
          <div class="row">
            <div class="fot_copyright">
              <span class="wsp"><span>Cung cấp bởi <a href="javascript:;">Nhóm 7</a></span></span>
            </div>
            <nav class="fot_menu_copyright">
              <ul class="ul_menu_fot">
                <li><a href="./index.php" title="Trang chủ">Trang chủ</a></li>
                <li><a href="./about.php" title="Giới thiệu">Giới thiệu</a></li>
                <li><a href="./Product/product.php" title="Sản phẩm">Sản phẩm</a></li>
                <li><a href="./news.php" title="Tin tức">Tin tức</a></li>
                <li><a href="./contact.php" title="Liên hệ">Liên hệ</a></li>
              </ul>
            </nav>
            <div class="pay_footer">
              <ul class="follow_option">
                <li><a href="#"><img src="./assets/images/pay_1.webp" alt="Payment"></a></li>
                <li><a href="#"><img src="./assets/images/pay_2.webp" alt="Payment"></a></li>
                <li><a href="#"><img src="./assets/images/pay_3.webp" alt="Payment"></a></li>
                <li><a href="#"><img src="./assets/images/pay_4.webp" alt="Payment"></a></li>
                <li><a href="#"><img src="./assets/images/pay_5.webp" alt="Payment"></a></li>
                <li><a href="#"><img src="./assets/images/pay_6.webp" alt="Payment"></a></li>
                <li><a href="#"><img src="./assets/images/pay_7.webp" alt="Payment"></a></li>
              </ul>
            </div>
            <a href="#" id="back-to-top" class="backtop" title="Lên đầu trang">
              <i class="fa-solid fa-angle-up" aria-hidden="true"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <!-- JS CORE -->
  <script src="<?php echo h(asset_versioned_url('./assets/js/auth.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('./assets/js/auth.security.enforcer.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('./assets/js/auth.localstorage.bridge.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('./assets/js/auth.reactive.guard.v2.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('./assets/js/auth.modal.bridge.js')); ?>"></script>

  <script src="<?php echo h(asset_versioned_url('./assets/js/products.seed.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('./assets/js/store.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('./assets/js/ui.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('./assets/js/products.app.js')); ?>"></script>

  <!-- Hiển thị mã đơn & cập nhật badge giỏ -->
  <script>
    const usp = new URLSearchParams(location.search);
    document.getElementById('oid').textContent = usp.get('id') || '—';
    window.SVUI?.updateCartCount?.();
  </script>

  <!-- Footer accordion mobile -->
  <script>
  (function(){
    function isMobile(){ return window.matchMedia("(max-width: 767.98px)").matches; }

    function setInitialState(){
      document.querySelectorAll(".site-footer .widget-ft").forEach((box) => {
        if (isMobile()){
          if (box.querySelector(".title-menu")?.textContent.trim().toLowerCase().includes("liên hệ")){
            box.classList.add("is-open");
          } else {
            box.classList.remove("is-open");
          }
        } else {
          box.classList.add("is-open");
        }
      });
    }

    function bindClick(){
      document.querySelectorAll(".site-footer .widget-ft .title-menu").forEach(title => {
        title.addEventListener("click", () => {
          if (!isMobile()) return;
          const box = title.closest(".widget-ft");
          if (!box) return;
          box.classList.toggle("is-open");
        });
      });
    }

    document.addEventListener("DOMContentLoaded", () => {
      setInitialState();
      bindClick();
    });
    window.addEventListener("resize", setInitialState);
  })(window, document);
  </script>
</body>
</html>


