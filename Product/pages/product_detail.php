<?php
require_once __DIR__ . '/../../includes/common.php';


// Trả về ../../assets/... cho trang chi tiết sản phẩm
function assetUrl($path) {
  if (!$path) {
    return '';
  }
  if (preg_match('#^(?:https?:)?//#', $path)) {
    return $path;
  }
  if (preg_match('#^(?:/|(?:\.\./|\./))*assets/#', $path)) {
    return preg_replace('#^(?:/|(?:\.\./|\./))*assets/#', '../../assets/', $path);
  }
  return '../../assets/images/product/' . ltrim($path, '/');
}

$user = current_user();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = db_fetch_one("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = $id AND p.status = 'selling' AND c.status = 'active' LIMIT 1");
if (!$product) {
    http_response_code(404);
    echo '<p>Không tìm thấy sản phẩm.</p>';
    exit;
}

// Check if product is out of stock
$stock = (int)($product['quantity'] ?? 0);
$isOutOfStock = $stock <= 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo h($product['name']); ?></title>
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('../../bootstrap-4.6.2-dist/css/bootstrap.css')); ?>" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('../../fontawesome-free-6.7.2-web/css/all.min.css')); ?>" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('../../assets/css/normalize.min.css')); ?>" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('../../assets/css/base.css')); ?>" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('../../assets/css/style.css')); ?>" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('../../assets/css/clean-styles.css')); ?>" />
    <style>
      :root {
        --pd-radius: 14px;
        --pd-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        --pd-border: #e5e7eb;
        --pd-muted: #6b7280;
        --pd-primary: #0d6efd;
      }
      body {
        margin: 0;
        background: #f9fafb;
        font-family: system-ui, 'Segoe UI', Roboto, Arial, sans-serif;
      }
      .pd-wrap {
        max-width: 1100px;
        margin: 0 auto;
        padding: 24px 16px 36px;
      }
      .pd-card {
        background: #ffffff;
        border: 1px solid #f0f1f3;
        border-radius: var(--pd-radius);
        box-shadow: var(--pd-shadow);
        overflow: hidden;
      }
      .pd-card-grid {
        display: grid;
        grid-template-columns: 1fr 1.05fr;
        gap: 20px;
        padding: 24px;
      }
      .pd-image-panel {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .gallery-main {
        background: #fafbfc;
        border: 1px solid #e8eaed;
        border-radius: 14px;
        height: 380px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
      }
      .gallery-main img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
      }
      .gallery-note {
        padding: 10px 13px;
        border-radius: 10px;
        background: #f5f7f9;
        border: 1px solid #e5e7eb;
        color: #666;
        font-size: 12px;
        line-height: 1.4;
      }
      .pd-summary {
        background: #fafbfc;
        border-radius: 14px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      .pd-summary-inner {
        width: 100%;
      }
      .pd-badges {
        margin-bottom: 12px;
      }
      .pd-badges .pd-badge {
        display: inline-flex;
        align-items: center;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        padding: 5px 10px;
        margin-right: 6px;
        border-radius: 999px;
      }
      .pd-badge.sale { background: #ef4444; }
      .pd-badge.new { background: #22c55e; }
      .pd-badge.oos { background: #6b7280; }
      .pd-title {
        font-size: 26px;
        font-weight: 700;
        line-height: 1.2;
        margin: 0 0 12px;
        color: #1a1a1a;
      }
      .pd-short {
        color: #555;
        font-size: 14px;
        line-height: 1.6;
        margin: 0 0 16px;
      }
      .pd-price {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        margin-bottom: 6px;
        flex-wrap: wrap;
      }
      .pd-price .new {
        font-size: 24px;
        font-weight: 500;
        color: #d32f2f;
      }
      .pd-price .old {
        font-size: 13px;
        text-decoration: line-through;
        color: #999;
      }
      .pd-price .off {
        font-size: 11px;
        font-weight: 700;
        color: #ef4444;
        background: #fee2e2;
        padding: 3px 7px;
        border-radius: 999px;
      }
      .pd-availability {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        margin-bottom: 12px;
      }
      .pd-out-of-stock {
        color: #dc2626;
        font-size: 13px;
        padding: 8px 0;
        font-weight: 700;
      }
      .pd-actions {
        margin-top: 14px;
      }
      .pd-actions form {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
      }
      .pd-qty {
        width: 100px;
        min-width: 100px;
      }
      .pd-actions .pd-qty label { display: none !important; }
      .pd-actions .btn {
        min-width: 130px;
        padding: 11px 16px;
        font-size: 13px;
      }
      .pd-qty input {
        width: 100%;
        max-width: 100%;
        border-radius: 10px;
        padding: 9px 10px;
        font-size: 13px;
      }
      .pd-trust {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 16px;
        font-size: 12px;
        color: #555;
      }
      .pd-trust span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
      }
      .pd-trust i {
        color: #2563eb;
        font-size: 13px;
      }
      .pd-extra {
        padding: 0 24px 24px;
      }
      .pd-section {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #e8eaed;
      }
      .pd-section h2 {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 11px;
        color: #1a1a1a;
      }
      .pd-description {
        color: #555;
        font-size: 14px;
        line-height: 1.6;
        margin: 0;
      }
      .specs dt {
        font-weight: 600;
        font-size: 13px;
        color: #1a1a1a;
      }
      .specs dd {
        font-size: 13px;
        color: #666;
        margin-bottom: 8px;
      }
      .btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
      }
      @media (max-width: 991px) {
        .pd-card-grid { grid-template-columns: 1fr; }
        .pd-summary { border-radius: 0 0 14px 14px; }
      }
      @media (max-width: 767px) {
        .pd-wrap { padding: 18px 12px 28px; }
        .pd-card-grid { padding: 18px; gap: 16px; }
        .pd-summary { padding: 16px; }
        .gallery-main { height: 300px; }
        .pd-actions form { flex-direction: column; align-items: stretch; }
        .pd-actions .btn { width: 100%; }
        .pd-qty { width: 100%; min-width: auto; }
        .pd-extra { padding: 0 16px 16px; }
        .pd-section { margin-top: 14px; padding-top: 14px; }
      }
      @media (max-width: 767.98px) {
        .header-left,.search-bar{display:none !important;}
        .header-main{display:flex !important;align-items:center !important;justify-content:space-between !important;padding:8px 10px !important;gap:8px !important;}
        .header-center{flex:0 0 auto;max-width:40% !important;overflow:hidden;}
        .header-center .logo img{max-width:120px !important;height:auto !important;display:block;}
        .header-right{flex:0 0 auto;}
        .header-right .cart-link{display:inline-flex !important;align-items:center;gap:4px;padding:4px 6px !important;margin:0 !important;font-size:12px !important;white-space:nowrap !important;}
        .header-right .cart-link i{font-size:15px !important;}
      }
    </style>
</head>
<body>
  <?php render_topbar(); ?>
            </div>
        </div>
    </div>
  </header>
  <header class="mid-header">
    <div class="container">
      <div class="header-main">
        <div class="header-left">
          <form action="../product.php" method="get" class="search-bar" autocomplete="off">
            <input type="text" name="q" placeholder="Tìm kiếm" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" inputmode="search" />
            <button type="submit" aria-label="Tìm kiếm"><i class="fa-solid fa-magnifying-glass"></i></button>
          </form>
        </div>
        <div class="header-center"><a href="../../index.php" class="logo"><img src="../../assets/images/logo.jpg" alt="GENTLEMAN" /></a></div>
        <div class="header-right"><a href="../../cart.php" class="cart-link"><i class="fa-solid fa-cart-shopping"></i> GIỎ HÀNG (<span class="cart-count"><?php echo cart_count(); ?></span>)</a></div>
      </div>
    </div>
  </header>
  <nav class="main-nav">
    <ul>
      <li><a href="../../index.php">TRANG CHỦ</a></li>
      <li><a href="../../about.php">GIỚI THIỆU</a></li>
      <li><a href="../product.php" class="js-products-url">SẢN PHẨM</a></li>
      <li><a href="../../news.php">TIN TỨC</a></li>
      <li><a href="../../contact.php">LIÊN HỆ</a></li>
    </ul>
  </nav>
  <script>
    (function (w, d) {
      function getProductsURL() {
        var a = d.querySelector('nav.main-nav a.js-products-url') || d.querySelector('nav.main-nav a[href*="product"]');
        return a && a.getAttribute('href') ? a.getAttribute('href') : '../product.php';
      }
      var form = d.querySelector('.search-bar');
      if (!form) return;
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var input = form.querySelector('input[name="q"], input[name="query"]');
        var q = (input && input.value || '').trim().replace(/\s+/g, ' ');
        var raw = getProductsURL();
        var url = new URL(raw, w.location.href);
        if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
        w.location.href = url.pathname + (url.search ? url.search : '');
      }, { passive: false });
    })(window, document);
  </script>
  <div class="container pd-wrap">
    <section class="bread-crumb">
      <div class="container">
        <ul class="breadcrumb">
          <li class="home"><a href="../../index.php"><span>Trang chủ</span></a></li>
          <li class="sep"><i class="fa-solid fa-angle-right"></i></li>
          <li><a href="../product.php"><span>Sản phẩm</span></a></li>
          <li class="sep"><i class="fa-solid fa-angle-right"></i></li>
          <li><strong><span><?php echo h($product['name']); ?></span></strong></li>
        </ul>
      </div>
    </section>

    <div class="pd-card">
      <div class="pd-card-grid">
        <div class="pd-image-panel">
          <div class="gallery-main"><img id="mainImg" src="<?php echo h(assetUrl($product['image'] ?: '../../assets/images/product/sample1.jpg')); ?>" alt="<?php echo h($product['name']); ?>" /></div>
          <div class="gallery-note">Hình ảnh thực tế, chất lượng sản phẩm luôn được kiểm duyệt kỹ.</div>
        </div>

        <div class="pd-summary">
          <div class="pd-summary-inner">
            <div class="pd-badges">
              <?php if ($isOutOfStock): ?><span class="pd-badge oos">HẾT HÀNG</span><?php else: ?><span class="pd-badge new"><?php echo h($product['category_name']); ?></span><?php endif; ?>
            </div>
            <h1 class="pd-title"><?php echo h($product['name']); ?></h1>
            <p class="pd-short"><?php echo h($product['description']); ?></p>
            <div class="pd-price">
              <span class="new"><?php echo number_format($product['sale_price'], 0, ',', '.'); ?>₫</span>
            </div>
            <?php if ($isOutOfStock): ?><div class="pd-out-of-stock">⚠ Sản phẩm hiện đã hết hàng</div><?php endif; ?>
            <div class="pd-availability">Tồn kho: <?php echo $stock; ?> • Đơn vị: <?php echo h($product['unit']); ?></div>
            <div class="pd-actions">
              <form method="post" action="../../cart.php?action=add" id="addToCartForm" <?php echo $isOutOfStock ? 'onsubmit="return false;"' : ''; ?>>
                <input type="hidden" name="id" value="<?php echo h($product['id']); ?>" />
                <div class="pd-qty">
                  <label class="form-label visually-hidden" for="qty">Số lượng</label>
                  <input type="number" id="qty" name="qty" min="1" max="<?php echo h($stock); ?>" value="1" class="form-control" data-max-stock="<?php echo h($stock); ?>" <?php echo $isOutOfStock ? 'disabled' : ''; ?> />
                </div>
                <button type="submit" name="redirect" value="cart" class="btn btn-primary" <?php echo $isOutOfStock ? 'disabled' : ''; ?>>Thêm vào giỏ</button>
                <button type="submit" name="redirect" value="checkout" class="btn btn-outline-secondary" <?php echo $isOutOfStock ? 'disabled' : ''; ?>>Mua ngay</button>
                <div id="qtyErrorMsg" style="color: #dc2626; font-size: 12px; display: none; white-space: nowrap;"></div>
              </form>
            </div>
            <div class="pd-trust">
              <span><i class="fa-solid fa-check"></i> Giao hàng nhanh</span>
              <span><i class="fa-solid fa-shield-halved"></i> Bảo mật thông tin</span>
            </div>
          </div>
        </div>
      </div>

      <div class="pd-extra">
        <section class="pd-section">
          <h2>Thông tin sản phẩm</h2>
          <p class="pd-description"><?php echo h($product['description']); ?></p>
        </section>

        <section class="pd-section">
          <h2>Thông số kỹ thuật</h2>
          <dl class="row specs mt-3">
            <dt class="col-sm-4">Mã sản phẩm</dt><dd class="col-sm-8"><?php echo h($product['sku']); ?></dd>
            <dt class="col-sm-4">Nhà cung cấp</dt><dd class="col-sm-8"><?php echo h($product['supplier']); ?></dd>
          </dl>
        </section>
      </div>
    </div>
  </div>
  <!-- Footer -->
  <footer class="footer">
    <div class="site-footer">
      <div class="top-footer">
        <div class="container">
          <div class="row">
            <section class="widget-ft">
              <h4 class="title-menu">Thông tin</h4>
              <ul class="list-menu">
                <li class="li_menu"><a href="../../index.php">Trang chủ</a></li>
                <li class="li_menu"><a href="../../about.php">Giới thiệu</a></li>
                <li class="li_menu"><a href="../product.php">Sản phẩm</a></li>
                <li class="li_menu"><a href="../../news.php">Tin tức</a></li>
                <li class="li_menu"><a href="../../contact.php">Liên hệ</a></li>
              </ul>
            </section>
            <section class="widget-ft">
              <h4 class="title-menu">Hỗ trợ</h4>
              <ul class="list-menu">
                <li class="li_menu"><a href="../../index.php">Trang chủ</a></li>
                <li class="li_menu"><a href="../../about.php">Giới thiệu</a></li>
                <li class="li_menu"><a href="../product.php">Sản phẩm</a></li>
                <li class="li_menu"><a href="../../news.php">Tin tức</a></li>
                <li class="li_menu"><a href="../../contact.php">Liên hệ</a></li>
              </ul>
            </section>
            <section class="widget-ft">
              <h4 class="title-menu">Hướng dẫn</h4>
              <ul class="list-menu">
                <li class="li_menu"><a href="../../index.php">Trang chủ</a></li>
                <li class="li_menu"><a href="../../about.php">Giới thiệu</a></li>
                <li class="li_menu"><a href="../product.php">Sản phẩm</a></li>
                <li class="li_menu"><a href="../../news.php">Tin tức</a></li>
                <li class="li_menu"><a href="../../contact.php">Liên hệ</a></li>
              </ul>
            </section>
            <section class="widget-ft">
              <h4 class="title-menu">Chính sách</h4>
              <ul class="list-menu">
                <li class="li_menu"><a href="../../index.php">Trang chủ</a></li>
                <li class="li_menu"><a href="../../about.php">Giới thiệu</a></li>
                <li class="li_menu"><a href="../product.php">Sản phẩm</a></li>
                <li class="li_menu"><a href="../../news.php">Tin tức</a></li>
                <li class="li_menu"><a href="../../contact.php">Liên hệ</a></li>
              </ul>
            </section>
            <section class="wg-logo">
              <h4 class="title-menu">Liên hệ</h4>
              <ul class="contact">
                <li><span class="txt_content_child"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> 140B-Tổ 3, Ấp Xóm Chùa,Tỉnh Tây Ninh</span></li>
                <li class="sdt"><span><i class="fa-solid fa-phone" aria-hidden="true"></i></span><a href="tel:0338286525">0338286525</a></li>
                <li class="sdt"><span><i class="fa-solid fa-envelope"></i></span><a href="mailto:thanhloc29052006@gmail.com">thanhloc29052006@gmail.com</a></li>
              </ul>
            </section>
          </div>
        </div>
      </div>

      <div class="mid-footer">
        <div class="container">
          <div class="row">
            <div class="fot_copyright"><span class="wsp"><span>Cung cấp bởi <a href="javascript:;">Nhóm 7</a></span></span></div>
            <nav class="fot_menu_copyright">
              <ul class="ul_menu_fot">
                <li><a href="../../index.php" title="Trang chủ">Trang chủ</a></li>
                <li><a href="../../about.php" title="Giới thiệu">Giới thiệu</a></li>
                <li><a href="../product.php" title="Sản phẩm">Sản phẩm</a></li>
                <li><a href="../../news.php" title="Tin tức">Tin tức</a></li>
                <li><a href="../../contact.php" title="Liên hệ">Liên hệ</a></li>
              </ul>
            </nav>
            <div class="pay_footer">
              <ul class="follow_option">
                <li><a href="#"><img src="../../assets/images/pay_1.webp" alt="Payment" /></a></li>
                <li><a href="#"><img src="../../assets/images/pay_2.webp" alt="Payment" /></a></li>
                <li><a href="#"><img src="../../assets/images/pay_3.webp" alt="Payment" /></a></li>
                <li><a href="#"><img src="../../assets/images/pay_4.webp" alt="Payment" /></a></li>
                <li><a href="#"><img src="../../assets/images/pay_5.webp" alt="Payment" /></a></li>
                <li><a href="#"><img src="../../assets/images/pay_6.webp" alt="Payment" /></a></li>
                <li><a href="#"><img src="../../assets/images/pay_7.webp" alt="Payment" /></a></li>
              </ul>
            </div>
            <a href="#" id="back-to-top" class="backtop" title="Lên đầu trang"><i class="fa-solid fa-angle-up" aria-hidden="true"></i></a>
          </div>
        </div>
      </div>
    </div>
  </footer>
  <?php if ($user): ?>
    <script>
      window.SERVER_AUTH_STATE = {
        loggedIn: true,
        user: {
          name: <?php echo json_encode($user['full_name'] ?? $user['name'] ?? ''); ?>,
          email: <?php echo json_encode($user['email'] ?? ''); ?>
        }
      };
    </script>
  <?php endif; ?>
  <script src="<?php echo h(asset_versioned_url('../../assets/js/auth.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('../../assets/js/auth.modal.bridge.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('../../assets/js/products.seed.js')); ?>"></script>
  <script>
    (function () {
      try {
        const fromAdmin = JSON.parse(localStorage.getItem('sv_products_v1') || '[]');
        if (Array.isArray(fromAdmin) && fromAdmin.length) {
          window.SV_PRODUCT_SEED = fromAdmin;
        }
      } catch (e) {}

      // Validate quantity with Vietnamese messages using setCustomValidity
      const form = document.getElementById('addToCartForm');
      const qtyInput = document.getElementById('qty');
      const errorMsg = document.getElementById('qtyErrorMsg');

      function validateQty() {
        if (!qtyInput) return;
        
        const maxStock = parseInt(qtyInput.dataset.maxStock || qtyInput.max, 10) || 0;
        const value = qtyInput.value.trim();
        let errorMessage = '';

        // Check if empty
        if (value === '') {
          errorMessage = 'Vui lòng nhập số lượng.';
        } else {
          const qty = parseInt(value, 10);
          
          // Check if < 1 (min=1)
          if (isNaN(qty) || qty < 1) {
            errorMessage = 'Số lượng phải lớn hơn hoặc bằng 1.';
          }
          // Check if > max stock
          else if (qty > maxStock) {
            errorMessage = 'Sản phẩm chỉ còn ' + maxStock + '.';
          }
        }

        // Set custom validity for browser validation
        qtyInput.setCustomValidity(errorMessage);

        // Also update our custom error display
        if (errorMsg) {
          if (errorMessage) {
            errorMsg.textContent = errorMessage;
            errorMsg.style.display = 'inline';
          } else {
            errorMsg.textContent = '';
            errorMsg.style.display = 'none';
          }
        }
      }

      if (qtyInput) {
        // Validate on form submit
        if (form) {
          form.addEventListener('submit', function(e) {
            validateQty();
            if (qtyInput.validity.valid === false) {
              e.preventDefault();
              qtyInput.reportValidity();
              return false;
            }
          });
        }

        // Real-time validation as user types or changes value
        qtyInput.addEventListener('input', validateQty);
        qtyInput.addEventListener('change', validateQty);
        qtyInput.addEventListener('blur', validateQty);
      }
    })();
  </script>
  <script src="<?php echo h(asset_versioned_url('../../assets/js/store.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('../../assets/js/ui.js')); ?>"></script>
  <script src="<?php echo h(asset_versioned_url('../../assets/js/jquery-3.6.0.min.js')); ?>" crossorigin="anonymous"></script>
  <script src="<?php echo h(asset_versioned_url('../../assets/js/bootstrap.bundle.min.js')); ?>" crossorigin="anonymous"></script>
  <script src="<?php echo h(asset_versioned_url('../../assets/js/auth.kick.guard.js')); ?>"></script>
</body>
</html>
