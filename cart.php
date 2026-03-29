<?php
require_once __DIR__ . '/includes/common.php';

function getProductStock(int $productId): int {
    $productId = max(0, $productId);
    if ($productId <= 0) {
        return 0;
    }
    $row = db_fetch_one("SELECT quantity FROM products WHERE id = $productId LIMIT 1");
    return $row ? max(0, (int)$row['quantity']) : 0;
}

function addCartNotice(string $message): void {
    if (!isset($_SESSION['cart_messages']) || !is_array($_SESSION['cart_messages'])) {
        $_SESSION['cart_messages'] = [];
    }
    $_SESSION['cart_messages'][] = $message;
}

function isAjaxCartRequest(): bool {
  $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
  $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  $redirect = strtolower((string)($_POST['redirect'] ?? $_GET['redirect'] ?? ''));
  return $xhr || strpos($accept, 'application/json') !== false || $redirect === 'none';
}

function cartJsonResponse(bool $success, string $message = '', int $addedQty = 0): void {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'success' => $success,
    'message' => $message,
    'added_qty' => max(0, $addedQty),
    'cart_count' => (int)cart_count(),
  ]);
  exit;
}

$action = $_REQUEST['action'] ?? '';
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
  $isAjax = isAjaxCartRequest();
    if (!$user) {
    if ($isAjax) {
    cartJsonResponse(false, 'Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.');
    }
      flash_message('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.');
      redirect(app_rel_url('account/login.php') . '?redirect=' . urlencode(app_url('cart.php')));
    }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $qty = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;
    $addedQty = 0;
    $notice = '';
        if ($id > 0) {
            $stock = getProductStock($id);
            if ($stock <= 0) {
        $notice = 'Sản phẩm hiện hết hàng, không thể thêm vào giỏ.';
            } else {
        $currentCart = get_cart();
        $inCart = max(0, (int)($currentCart[$id] ?? 0));
        $remain = max(0, $stock - $inCart);
        if ($remain <= 0) {
          $notice = 'Sản phẩm chỉ còn ' . $stock . ' cái và bạn đã thêm tối đa trong giỏ.';
        } else {
          $addedQty = min($qty, $remain);
          cart_add_item($id, $addedQty);
          if ($addedQty < $qty) {
            $notice = 'Số lượng vượt quá tồn kho. Đã thêm ' . $addedQty . ' sản phẩm, tối đa còn lại trong kho là ' . $stock . '.';
          }
        }
            }
        }
    if ($notice !== '') {
      addCartNotice($notice);
    }

    if ($isAjax) {
      cartJsonResponse($addedQty > 0, $notice, $addedQty);
    }

        $redirect = $_POST['redirect'] ?? 'cart';
        if ($redirect === 'checkout') {
            redirect(app_url('checkout.php'));
        }
        redirect(app_url('cart.php'));
    }
    if ($action === 'update') {
        $quantities = $_POST['qty'] ?? [];
        if (is_array($quantities)) {
            foreach ($quantities as $id => $qty) {
                $id = (int)$id;
                $qty = max(0, (int)$qty);
                if ($id <= 0) {
                    continue;
                }
                $stock = getProductStock($id);
                if ($stock <= 0) {
                    cart_remove_item($id);
                    addCartNotice('Sản phẩm đã hết hàng và được xóa khỏi giỏ.');
                    continue;
                }
                if ($qty < 1) {
                    // Nếu qty < 1, xóa sản phẩm khỏi giỏ
                    cart_remove_item($id);
                    continue;
                }
                if ($qty > $stock) {
                    // Không update nếu vượt tồn kho, giữ giỏ cũ
                    addCartNotice('Sản phẩm chỉ còn ' . $stock . '. Không cập nhật số lượng.');
                    continue;
                }
                // Qty hợp lệ: 1 <= qty <= stock
                cart_update_item($id, $qty);
            }
        }
        redirect(app_url('cart.php'));
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'remove') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            cart_remove_item($id);
        }
        redirect(app_url('cart.php'));
    }
    if ($action === 'clear') {
        cart_clear();
        redirect(app_url('cart.php'));
    }
    if ($action === 'cancel_order') {
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if ($orderId > 0 && $user && !empty($user['id'])) {
            $userId = (int)$user['id'];
            $order = db_fetch_one("SELECT id, status FROM orders WHERE id = $orderId AND user_id = $userId LIMIT 1");
            if ($order && $order['status'] === 'new') {
          $stmt = db_prepare('UPDATE orders SET status = ? WHERE id = ? AND user_id = ?');
          $cancelledStatus = 'cancelled';
          $stmt->bind_param('sii', $cancelledStatus, $orderId, $userId);
          $stmt->execute();
          addCartNotice('Đơn hàng đã được huỷ. Dữ liệu đơn vẫn được lưu trong hệ thống.');
            } else {
                addCartNotice('Không thể huỷ đơn hàng này, hoặc đơn hàng đã được xử lý.');
            }
        }
        redirect(app_url('cart.php'));
    }
    if ($action === 'add') {
      $isAjax = isAjaxCartRequest();
      if (!$user) {
      if ($isAjax) {
        cartJsonResponse(false, 'Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.');
      }
        flash_message('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.');
        redirect(app_rel_url('account/login.php') . '?redirect=' . urlencode(app_url('cart.php')));
      }
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $qty = isset($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1;
      $addedQty = 0;
      $notice = '';
        if ($id > 0) {
            $stock = getProductStock($id);
            if ($stock <= 0) {
          $notice = 'Sản phẩm hiện hết hàng, không thể thêm vào giỏ.';
            } else {
          $currentCart = get_cart();
          $inCart = max(0, (int)($currentCart[$id] ?? 0));
          $remain = max(0, $stock - $inCart);
          if ($remain <= 0) {
            $notice = 'Sản phẩm chỉ còn ' . $stock . ' cái và bạn đã thêm tối đa trong giỏ.';
          } else {
            $addedQty = min($qty, $remain);
            if ($addedQty < $qty) {
              $notice = 'Số lượng đã được điều chỉnh xuống ' . $addedQty . ' do tồn kho còn lại không đủ.';
            }
            cart_add_item($id, $addedQty);
                }
            }
        }
      if ($notice !== '') {
        addCartNotice($notice);
      }
      if ($isAjax) {
        cartJsonResponse($addedQty > 0, $notice, $addedQty);
      }
        redirect(app_url('cart.php'));
    }
}

  // Auto-sync stale cart quantities to current stock and show a clear notice.
  $cartSnapshot = get_cart();
  if (!empty($cartSnapshot)) {
    $ids = array_map('intval', array_keys($cartSnapshot));
    $ids = array_values(array_filter($ids, function ($v) { return $v > 0; }));
    if (!empty($ids)) {
      $idList = implode(',', $ids);
      $productRows = db_fetch_all("SELECT id, name, sku, quantity FROM products WHERE id IN ($idList)");
      $productMap = [];
      foreach ($productRows as $r) {
        $productMap[(int)$r['id']] = $r;
      }

      foreach ($cartSnapshot as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        $row = $productMap[$pid] ?? null;
        $stock = $row ? max(0, (int)$row['quantity']) : 0;

        if ($stock <= 0) {
          if ($qty > 0) {
            cart_remove_item($pid);
            addCartNotice('Sản phẩm "' . ($row['name'] ?? ('Mã ' . $pid)) . '" hiện đã hết hàng, đã được xóa khỏi giỏ.');
          }
          continue;
        }

        if ($qty > $stock) {
          cart_update_item($pid, $stock);
          addCartNotice('Sản phẩm "' . ($row['name'] ?? ('Mã ' . $pid)) . '" chỉ còn ' . $stock . ' sản phẩm, giỏ hàng đã giảm về mức tối đa.');
        }
      }
    }
  }

// Đồng bộ lại cookie count sau khi đã auto-sync giỏ hàng theo tồn kho.
setcookie('SV_CART_COUNT', (string)cart_count(), 0, '/');

// Xóa auto-sync messages để không hiển thị trên trang cart
unset($_SESSION['cart_messages']);

$user = current_user();
$items = cart_items();
$total = cart_total();
$query = trim($_GET['query'] ?? $_GET['q'] ?? '');

$orderHistory = [];
$orderItemsByOrder = [];
if ($user && !empty($user['id'])) {
    $userId = (int)$user['id'];
    $orderHistory = db_fetch_all("SELECT o.* FROM orders o WHERE o.user_id = $userId ORDER BY o.created_at DESC LIMIT 5");
    $orderIds = array_column($orderHistory, 'id');
    if (!empty($orderIds)) {
        $orderIdsList = implode(',', array_map('intval', $orderIds));
        $rows = db_fetch_all("SELECT oi.*, p.name, p.sku FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($orderIdsList) ORDER BY oi.order_id, oi.id");
        foreach ($rows as $row) {
            $orderItemsByOrder[$row['order_id']][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="./bootstrap-4.6.2-dist/css/bootstrap.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="./fontawesome-free-6.7.2-web/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./assets/css/normalize.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Site CSS -->
    <link rel="stylesheet" href="./assets/css/base.css" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />
    <style>
      body { margin: 0; background: #f8fafc; font-family: system-ui,Segoe UI,Roboto,Arial,sans-serif; }
      .cart-container { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }
      .topbar { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; }
      .topbar .container { display: flex; align-items: center; justify-content: flex-end; }
      .topbar-right { margin-left: auto; display: flex; gap: 12px; }
      .topbar-right a { margin-left: 10px; padding: 5px 12px; font-size: 13px; border-radius: 4px; transition: .3s; text-decoration: none; }
      .btn-outline { border: 1px solid #333; color: #333; background: #fff; }
      .btn-outline:hover { background: #333; color: #fff; }
      .btn-primary, .btn-logout { background: #333 !important; color: #fff !important; border: 1px solid #333 !important; border-radius: 4px !important; padding: 5px 12px !important; font-size: 13px !important; transition: .3s !important; display: inline-block !important; }
      .btn-primary:hover, .btn-logout:hover { background: #555 !important; }
      .cart-summary { background: #fff; padding: 20px; border-radius: 18px; border: 1px solid #e5e7eb; }
      .cart-table img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; }
      .table td { vertical-align: middle; }
      .cart-qty-cell { text-align: center; vertical-align: middle; padding: 0.75rem; }
      .cart-qty-box { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; }
      .cart-qty-input { width: 84px; height: 40px; text-align: center; border: 1px solid #d9dee7; border-radius: 12px; padding: 0.25rem 0.5rem; box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06); }
      .cart-qty-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
      .cart-stock-note { font-size: 12px; color: #64748b; line-height: 1.3; }
      .cart-actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: flex-start; align-items: center; }
      .cart-actions .btn { min-width: 180px; }
      .cart-grid { display: grid; grid-template-columns: 1.7fr 0.9fr; gap: 24px; align-items: flex-start; }
      .cart-main { display: flex; flex-direction: column; gap: 18px; }
      .cart-aside { position: sticky; top: 24px; align-self: start; }
      .cart-table { border: none; border-radius: 24px; overflow: hidden; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); }
      .cart-table thead { background: #f8fafc; }
      .cart-table th, .cart-table td { border: none; padding: 18px 16px; }
      .cart-table th { white-space: nowrap; }
      .cart-table tbody tr { border-bottom: 1px solid #eceff3; }
      .cart-table tbody tr:last-child { border-bottom: none; }
      .cart-table img { width: 96px; height: 96px; border-radius: 18px; object-fit: cover; background: #f8fafc; }
      .cart-product { display: flex; gap: 16px; align-items: center; }
      .cart-product-image { display: inline-flex; width: 96px; height: 96px; border-radius: 20px; overflow: hidden; flex-shrink: 0; background: #f8fafc; box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.08); }
      .cart-product-image img { width: 100%; height: 100%; object-fit: cover; }
      .cart-product-info { display: flex; flex-direction: column; gap: 6px; }
      .cart-product-title { margin: 0; font-size: 16px; line-height: 1.35; font-weight: 700; }
      .cart-product-title a { color: #111827; text-decoration: none; transition: color 0.2s ease; }
      .cart-product-title a:hover { color: #2563eb; }
      .cart-product-meta { font-size: 13px; color: #64748b; line-height: 1.5; }
      .cart-product-meta span { display: inline-block; margin-right: 10px; }
      .cart-table .text-muted { color: #6b7280; }
      .cart-summary { background: #fff; padding: 26px; border-radius: 24px; border: 1px solid #e5e7eb; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06); }
      .cart-summary h3 { font-size: 18px; margin-bottom: 18px; }
      .cart-summary .summary-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
      .cart-summary .summary-row:last-child { border-bottom: none; }
      .cart-summary .summary-row.total { font-size: 18px; font-weight: 700; color: #111827; padding-top: 18px; }
      .cart-summary .summary-note { font-size: 13px; color: #475569; margin-top: 16px; }
      .cart-summary .btn-checkout { width: 100%; }
      .cart-summary .btn-clear { width: 100%; }
      .cart-title-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
      .cart-title-bar h1 { margin: 0; }
      @media (max-width: 991.98px) { .cart-grid { grid-template-columns: 1fr; } .cart-aside { position: static; } }
      @media (max-width: 767.98px) { .cart-actions { width: 100%; flex-direction: column; } .cart-actions .btn { width: 100%; min-width: auto; } }
      .order-history-section { margin-top: 40px; }
      .order-preview-card { border: 1px solid #e5e7eb; border-radius: 18px; background: #fff; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); }
      .order-preview-card .card-header { padding: 18px 22px; border-bottom: 1px solid #f1f5f9; background: #fafbfc; }
      .order-preview-card .card-body { padding: 18px 22px; }
      .order-preview-card .order-meta { gap: 16px; display: flex; flex-wrap: wrap; align-items: center; }
      .order-preview-card .order-meta .badge { font-size: 0.75rem; padding: 0.55em 0.8em; }
      .order-preview-card .order-product-list th,
      .order-preview-card .order-product-list td { vertical-align: middle; }
      .order-preview-empty { padding: 22px; }
    </style>
</head>
<body>
    <?php render_topbar(); ?>

  <header class="mid-header">
    <div class="container">
      <div class="header-main">
        <div class="header-left">
          <form action="./Product/product.php" method="get" class="search-bar" autocomplete="off">
            <input type="text" name="query" placeholder="Tìm kiếm" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" inputmode="search" />
            <button type="submit" aria-label="Tìm kiếm"><i class="fa-solid fa-magnifying-glass"></i></button>
          </form>
        </div>
        <div class="header-center">
          <a href="./index.php" class="logo"><img src="./assets/images/logo.jpg" alt="GENTLEMAN" /></a>
        </div>
        <div class="header-right">
          <a href="./cart.php" class="cart-link"><i class="fa-solid fa-cart-shopping"></i> GIỎ HÀNG (<span class="cart-count"><?php echo cart_count(); ?></span>)</a>
        </div>
      </div>
    </div>
  </header>

  <nav class="main-nav">
    <ul>
      <li><a href="./index.php">TRANG CHỦ</a></li>
      <li><a href="./about.php">GIỚI THIỆU</a></li>
      <li><a href="./Product/product.php" class="js-products-url">SẢN PHẨM</a></li>
      <li><a href="./news.php">TIN TỨC</a></li>
      <li><a href="./contact.php">LIÊN HỆ</a></li>
    </ul>
  </nav>

  <script>
    (function (w, d) {
      function getProductsURL() {
        var a = d.querySelector('nav.main-nav a.js-products-url') || d.querySelector('nav.main-nav a[href*="product"]');
        return a && a.getAttribute('href') ? a.getAttribute('href') : './Product/product.php';
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

  <main class="cart-container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-column flex-md-row gap-3">
      <div>
        <h1 class="h3">Giỏ hàng</h1>
        <p class="text-muted mb-0">Kiểm tra số lượng và tiến hành thanh toán.</p>
      </div>
      <div><a href="./Product/product.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Tiếp tục mua sắm</a></div>
    </div>
    <!-- Disable cart_messages display on cart page -->
    <?php /* if (!empty($_SESSION['cart_messages']) && is_array($_SESSION['cart_messages'])): ?>
      <?php foreach ($_SESSION['cart_messages'] as $message): ?>
        <div class="alert alert-warning"><?php echo h($message); ?></div>
      <?php endforeach; ?>
      <?php unset($_SESSION['cart_messages']); ?>
    <?php endif; */ ?>

    <?php if ($user): ?>
      <div class="alert alert-info mb-3 inline-clean-3" id="userInfo">
        <i class="fas fa-user"></i> Giỏ hàng của:
        <strong id="userName"><?php echo h($user['full_name'] ?? $user['name'] ?? 'Khách'); ?></strong>
        (<span id="userEmail"><?php echo h($user['email'] ?? ''); ?></span>)
      </div>
    <?php else: ?>
      <div class="alert alert-warning mb-3">Bạn chưa đăng nhập. <a href="<?php echo h(app_rel_url('account/login.php') . '?redirect=' . urlencode(app_url('cart.php'))); ?>">Đăng nhập</a> để thanh toán nhanh hơn.</div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
      <div class="alert alert-warning">Giỏ hàng trống. Bạn có thể thêm sản phẩm từ <a href="./Product/product.php">trang sản phẩm</a>.</div>
    <?php else: ?>
      <div class="cart-grid">
        <div class="cart-main">
          <form method="post" action="cart.php?action=update">
            <div class="table-responsive mb-4">
              <table class="table cart-table bg-white">
                <thead class="thead-light">
                  <tr>
                    <th>Sản phẩm</th>
                    <th class="text-center">Đơn giá</th>
                    <th class="text-center">Số lượng</th>
                    <th class="text-end">Thành&nbsp;tiền</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                    <tr class="cart-item-row" data-item-id="<?php echo h($item['id']); ?>" data-price="<?php echo (float)$item['sale_price']; ?>" data-stock="<?php echo (int)$item['quantity']; ?>">
                      <td>
                        <div class="cart-product">
                          <a href="./Product/pages/product_detail.php?id=<?php echo h($item['id']); ?>" class="cart-product-image">
                            <img src="<?php echo h(normalizeAssetUrl($item['image'] ?: 'assets/images/product/sample1.jpg')); ?>" alt="<?php echo h($item['name']); ?>" />
                          </a>
                          <div class="cart-product-info">
                            <h3 class="cart-product-title"><a href="./Product/pages/product_detail.php?id=<?php echo h($item['id']); ?>"><?php echo h($item['name']); ?></a></h3>
                            <div class="cart-product-meta">
                              <span>Mã: <?php echo h($item['sku']); ?></span>
                              <span><?php echo h($item['category_name']); ?></span>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="text-center align-middle"><?php echo number_format($item['sale_price'], 0, ',', '.'); ?>₫</td>
                      <td class="text-center align-middle cart-qty-cell">
                        <div class="cart-qty-box">
                          <input type="number" name="qty[<?php echo h($item['id']); ?>]" min="1" max="<?php echo (int)$item['quantity']; ?>" value="<?php echo max(1, min((int)$item['cart_quantity'], (int)$item['quantity'])); ?>" class="cart-qty-input" data-item-id="<?php echo h($item['id']); ?>" />
                          <div class="cart-stock-note">Còn <?php echo (int)$item['quantity']; ?> sản phẩm</div>
                          <div class="text-danger small cart-qty-error" style="display:none;"></div>
                        </div>
                      </td>
                      <td class="text-end align-middle fw-bold cart-line-total" data-item-id="<?php echo h($item['id']); ?>"><?php echo number_format($item['line_total'], 0, ',', '.'); ?>₫</td>
                      <td class="text-center align-middle"><a href="cart.php?action=remove&id=<?php echo h($item['id']); ?>" class="btn btn-outline-danger btn-sm">Xóa</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="cart-actions mb-4">
              <a href="cart.php?action=clear" class="btn btn-outline-danger" onclick="return confirm('Xóa toàn bộ giỏ hàng?');">Xóa giỏ hàng</a>
              <button type="submit" class="btn btn-secondary">Cập nhật giỏ hàng</button>
            </div>
          </form>
        </div>

        <aside class="cart-aside">
          <div class="cart-summary">
            <h3>Tóm tắt đơn hàng</h3>
            <div class="summary-row">
              <div class="text-muted">Tổng số sản phẩm:</div>
              <div class="fw-bold"><span id="cart-total-count"><?php echo cart_count(); ?></span></div>
            </div>
            <div class="summary-row total">
              <div class="text-muted">Tổng tạm tính:</div>
              <div class="fw-bold"><span id="cart-total-amount"><?php echo number_format($total, 0, ',', '.'); ?></span>₫</div>
            </div>
            <?php if (!$user): ?>
              <div class="alert alert-info mb-3">Bạn cần <a href="<?php echo h(app_rel_url('account/login.php') . '?redirect=' . urlencode(app_url('cart.php'))); ?>">đăng nhập</a> để thanh toán.</div>
            <?php endif; ?>
            <div class="d-grid gap-3">
              <a href="checkout.php" class="btn btn-success btn-checkout">Tiến hành thanh toán</a>
              <a href="./Product/product.php" class="btn btn-outline-secondary btn-clear">Thêm sản phẩm</a>
            </div>
            <p class="summary-note">Bạn có thể điều chỉnh số lượng sản phẩm, sau đó bấm Cập nhật giỏ hàng để lưu thay đổi chính xác.</p>
          </div>
        </aside>
      </div>
    <?php endif; ?>

    <?php if ($user): ?>
      <section class="order-history-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="h5 mb-1">Lịch sử đơn hàng</h2>
            <p class="text-muted mb-0">Xem đơn hàng đã mua gần đây ngay trên trang giỏ hàng.</p>
          </div>
        </div>
        <?php if (!empty($orderHistory)): ?>
          <?php foreach ($orderHistory as $order): ?>
            <div class="order-preview-card mb-3">
              <div class="card-header d-flex justify-content-between align-items-center flex-column flex-md-row gap-2">
                <div>
                  <div class="fw-bold">Đơn hàng <?php echo h($order['order_number']); ?></div>
                  <div class="text-muted small">Ngày: <?php echo h($order['created_at']); ?></div>
                </div>
                <div class="order-meta d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge bg-secondary">
                    <?php echo h($order['status'] === 'new' ? 'Chờ xử lý' : ($order['status'] === 'confirmed' ? 'Đã xác nhận' : ($order['status'] === 'shipped' ? 'Đã giao' : ($order['status'] === 'cancelled' ? 'Đã huỷ' : ucfirst($order['status']))))); ?>
                  </span>
                  <span class="text-muted small">Tổng: <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>₫</span>
                  <?php if ($order['status'] === 'new'): ?>
                    <a href="cart.php?action=cancel_order&order_id=<?php echo h($order['id']); ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bạn có chắc muốn huỷ đơn hàng này?');">Huỷ đơn</a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm order-product-list mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Sản phẩm</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-end">Thành&nbsp;tiền</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($orderItemsByOrder[$order['id']] ?? [] as $item): ?>
                        <tr>
                          <td>
                            <?php echo h($item['name']); ?>
                            <div class="text-muted small">Mã: <?php echo h($item['sku']); ?></div>
                          </td>
                          <td class="text-center"><?php echo (int)$item['quantity']; ?></td>
                          <td class="text-end"><?php echo number_format($item['total_price'], 0, ',', '.'); ?>₫</td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="order-preview-card order-preview-empty">
            <div class="text-muted">Bạn chưa có đơn hàng nào. Sau khi mua, lịch sử đơn hàng sẽ xuất hiện ở đây.</div>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const cartCountHeader = document.querySelector('.cart-count');
      const cartTotalCount = document.getElementById('cart-total-count');
      const cartTotalAmount = document.getElementById('cart-total-amount');
      const cartItemRows = document.querySelectorAll('.cart-item-row');

      // Format giá tiền VND
      function formatCurrency(amount) {
        return Math.round(amount).toLocaleString('vi-VN');
      }

      // Tính tổng tiền và số lượng
      function updateCartTotals() {
        let totalQty = 0;
        let totalAmount = 0;

        cartItemRows.forEach(row => {
          const input = row.querySelector('.cart-qty-input');
          const qty = parseInt(input.value, 10) || 0;
          const price = parseFloat(row.dataset.price);
          const lineTotal = qty * price;

          totalQty += qty;
          totalAmount += lineTotal;
        });

        // Update header icon giỏ hàng
        if (cartCountHeader) {
          cartCountHeader.textContent = totalQty;
        }

        // Update tóm tắt
        cartTotalCount.textContent = totalQty;
        cartTotalAmount.textContent = formatCurrency(totalAmount);
      }

      // Bắt change event của mỗi input số lượng
      document.querySelectorAll('.cart-qty-input').forEach(input => {
        input.addEventListener('change', function() {
          const row = this.closest('.cart-item-row');
          const itemId = this.dataset.itemId;
          const stock = parseInt(row.dataset.stock, 10);
          const price = parseFloat(row.dataset.price);
          const errorDiv = row.querySelector('.cart-qty-error');
          
          let qty = parseInt(this.value, 10) || 0;
          let errorMsg = '';

          // Validate
          if (isNaN(qty) || qty < 1) {
            qty = 1;
            errorMsg = 'Số lượng phải lớn hơn hoặc bằng 1.';
          } else if (qty > stock) {
            qty = stock;
            errorMsg = 'Sản phẩm chỉ còn ' + stock + '.';
          }

          // Cập nhật input value
          this.value = qty;

          // Hiện lỗi nếu có
          if (errorMsg) {
            errorDiv.textContent = errorMsg;
            errorDiv.style.display = 'block';
            setTimeout(() => {
              errorDiv.style.display = 'none';
            }, 3000);
          } else {
            errorDiv.style.display = 'none';
          }

          // Update thành tiền dòng = qty * price
          const lineTotal = qty * price;
          const lineTotalSpan = row.querySelector('.cart-line-total');
          lineTotalSpan.textContent = formatCurrency(lineTotal) + '₫';

          // Update tổng tiền & số lượng
          updateCartTotals();

          // AJAX save: POST lên server để lưu thay đổi (không reload)
          if (!errorMsg && qty > 0) {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('qty[' + itemId + ']', qty);

            fetch('cart.php', {
              method: 'POST',
              body: formData
            }).catch(err => {
              console.error('Error saving cart:', err);
            });
          }
        });

        // Cũng bắt blur event để validate khi người dùng rời khỏi input
        input.addEventListener('blur', function() {
          const row = this.closest('.cart-item-row');
          const stock = parseInt(row.dataset.stock, 10);
          let qty = parseInt(this.value, 10) || 1;

          if (qty < 1) qty = 1;
          if (qty > stock) qty = stock;

          if (this.value !== qty.toString()) {
            this.value = qty;
            this.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
      });

      // Initialize totals on page load
      updateCartTotals();
    });
  </script>
      <footer class="footer">
        <div class="site-footer">
            <div class="top-footer">
                <div class="container">
                    <div class="row">
                        <section class="widget-ft">
                            <h4 class="title-menu">Thông tin</h4>
                            <ul class="list-menu">
                                <li class="li_menu">
                                    <a href="./index.php">Trang chủ</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./about.php">Giới thiệu</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./Product/product.php">Sản phẩm</a>
                                </li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Hỗ trợ</h4>
                            <ul class="list-menu">
                                <li class="li_menu">
                                    <a href="./index.php">Trang chủ</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./about.php">Giới thiệu</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./Product/product.php">Sản phẩm</a>
                                </li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Hướng dẫn</h4>
                            <ul class="list-menu">
                                <li class="li_menu">
                                    <a href="./index.php">Trang chủ</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./about.php">Giới thiệu</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./Product/product.php">Sản phẩm</a>
                                </li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Chính sách</h4>
                            <ul class="list-menu">
                                <li class="li_menu">
                                    <a href="./index.php">Trang chủ</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./about.php">Giới thiệu</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./Product/product.php">Sản phẩm</a>
                                </li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="wg-logo">
                            <h4 class="title-menu">Liên hệ</h4>
                            <ul class="contact">
                                <li>
                                    <span class="txt_content_child">
                      <span
                        ><i
                          class="fa-solid fa-location-dot"
                          aria-hidden="true"
                        ></i
                      ></span> 140B-Tổ 3, Ấp Xóm Chùa,Tỉnh Tây Ninh
                                    </span>
                                </li>
                                <li class="sdt">
                                    <span><i class="fa-solid fa-phone" aria-hidden="true"></i
                    ></span>
                                    <a href="tel:0338286525">0338286525</a>
                                </li>
                                <li class="sdt">
                                    <span><i class="fa-solid fa-envelope"></i></span>
                                    <a href="mailto:thanhloc29052006@gmail.com">thanhloc29052006@gmail.com</a
                    >
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
                <span class="wsp"
                  ><span
                    >Cung cấp bởi <a href="javascript:;">Nhóm 7</a></span>
                                    </span>
                    </div>
                    <nav class="fot_menu_copyright">
                        <ul class="ul_menu_fot">
                            <li>
                                <a href="./index.php" title="Trang chủ">Trang chủ</a>
                            </li>
                            <li>
                                <a href="./about.php" title="Giới thiệu">Giới thiệu</a>
                            </li>
                            <li>
                                <a href="./Product/product.php" title="Sản phẩm">Sản phẩm</a
                    >
                  </li>
                  <li><a href="./news.php" title="Tin tức">Tin tức</a></li>
                            <li><a href="./contact.php" title="Liên hệ">Liên hệ</a></li>
                        </ul>
                    </nav>
                    <div class="pay_footer">
                        <ul class="follow_option">
                            <li>
                                <a href="#"><img src="./assets/images/pay_1.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_2.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_3.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_4.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_5.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_6.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_7.webp" alt="Payment" /></a>
                            </li>
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
        <script src="<?php echo h(asset_versioned_url('./assets/js/auth.js')); ?>"></script>
    <script src="./assets/js/auth.security.enforcer.js"></script>
    <script src="./assets/js/auth.security.enforcer.js"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/auth.modal.bridge.js')); ?>"></script>

    <script src="./assets/js/products.seed.js"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/store.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/ui.js')); ?>"></script>
    <script>
      (function (w, d) {
        var serverCount = <?php echo (int)cart_count(); ?>;
        var isEmptyCart = <?php echo empty($items) ? 'true' : 'false'; ?>;

        // Trang cart luôn ưu tiên số lượng do server render.
        w.SERVER_CART_COUNT = serverCount;
        w.SERVER_CART_COUNT_SOURCE = 'server';
        d.querySelectorAll('.cart-count, #cartCount').forEach(function (el) {
          if (el) el.textContent = String(serverCount);
        });

        // Nếu giỏ server đã trống thì dọn localStorage theo user để tránh kéo badge cũ.
        if (isEmptyCart && w.AUTH?.getCurrentUser) {
          try {
            var user = w.AUTH.getCurrentUser();
            var email = user && user.email ? String(user.email).toLowerCase() : '';
            if (email) {
              localStorage.removeItem('sv_cart_user_' + email);
            }
          } catch (_) {}
        }

        try {
          w.dispatchEvent(new CustomEvent('cart:changed'));
        } catch (_) {}
      })(window, document);
    </script>

</body>
</html>
