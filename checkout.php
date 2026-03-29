<?php
require_once __DIR__ . '/includes/common.php';

$user = current_user();
require_login();

$items = cart_items();
if (empty($items)) {
    redirect(app_url('cart.php'));
}

function load_shipping_profiles_by_user($userId) {
    return db_fetch_all_prepared(
        'SELECT id, slot_number, full_name, phone, email, shipping_address, note FROM user_shipping_profiles WHERE user_id = ? ORDER BY slot_number ASC, id DESC',
        'i',
        [(int)$userId]
    );
}

function build_default_user_address($user) {
    $parts = [
        trim((string)($user['street_address'] ?? '')),
        trim((string)($user['ward'] ?? '')),
        trim((string)($user['district'] ?? '')),
        trim((string)($user['city_province'] ?? '')),
    ];
    $parts = array_values(array_filter($parts, static function ($v) {
        return $v !== '';
    }));

    if (!empty($parts)) {
        return implode(', ', $parts);
    }

    return trim((string)($user['address'] ?? ''));
}

function map_profiles_to_slots(array $profiles) {
    $slots = [1 => null, 2 => null, 3 => null];
    foreach ($profiles as $profile) {
        $slot = (int)($profile['slot_number'] ?? 0);
        if ($slot >= 1 && $slot <= 3 && $slots[$slot] === null) {
            $slots[$slot] = $profile;
        }
    }
    return $slots;
}

$total = cart_total();
$errors = [];
$successMessage = '';
$addressType = 'home';
$payment_method = 'cash';
$selected_profile_id = 0;
$selected_source = 'profile';
$account_shipping_address = build_default_user_address($user);

$shippingProfiles = load_shipping_profiles_by_user($user['id']);
if (empty($shippingProfiles)) {
    $defaultName = trim((string)($user['full_name'] ?? ''));
    $defaultPhone = trim((string)($user['phone'] ?? ''));
    $defaultEmail = trim((string)($user['email'] ?? ''));
    $defaultAddress = build_default_user_address($user);

    $stmt = db_prepare('INSERT INTO user_shipping_profiles (user_id, slot_number, full_name, phone, email, shipping_address, note, is_default) VALUES (?, 1, ?, ?, ?, ?, ?, 1)');
    $emptyNote = '';
    $stmt->bind_param('isssss', $user['id'], $defaultName, $defaultPhone, $defaultEmail, $defaultAddress, $emptyNote);
    $stmt->execute();

    $shippingProfiles = load_shipping_profiles_by_user($user['id']);
}

$slots = map_profiles_to_slots($shippingProfiles);
foreach ($slots as $slotProfile) {
    if ($slotProfile) {
        $selected_profile_id = (int)$slotProfile['id'];
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addressType = $_POST['address_type'] ?? 'home';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $selected_profile_id = (int)($_POST['selected_profile_id'] ?? 0);
  $selected_source = ($_POST['selected_source'] ?? 'profile') === 'account' ? 'account' : 'profile';

    if (!in_array($addressType, ['home', 'office', 'business'], true)) {
        $addressType = 'home';
    }

    if (!in_array($payment_method, ['cash', 'bank', 'online'], true)) {
        $payment_method = 'cash';
    }

    $profileAction = $_POST['profile_action'] ?? '';
    if ($profileAction === 'save_slot') {
        $slotNumber = (int)($_POST['slot_number'] ?? 1);
        $profileFullName = trim((string)($_POST['profile_full_name'] ?? ''));
        $profilePhone = trim((string)($_POST['profile_phone'] ?? ''));
        $profileEmail = trim((string)($_POST['profile_email'] ?? ''));
        $profileAddress = trim((string)($_POST['profile_address'] ?? ''));
        $profileNote = trim((string)($_POST['profile_note'] ?? ''));

        if ($slotNumber < 1 || $slotNumber > 3) {
            $slotNumber = 1;
        }

        if (!preg_match('/^[A-Za-zÀ-ỹ\s]{2,50}$/u', $profileFullName)) {
            $errors[] = 'Họ tên chỉ gồm chữ và khoảng trắng, từ 2 đến 50 ký tự.';
        }

        if ($profilePhone === '' || !preg_match('/^(03|05|07|08|09)[0-9]{8}$/', $profilePhone)) {
            $errors[] = 'Số điện thoại không hợp lệ (ví dụ: 0901234567).';
        }

        if ($profileEmail !== '' && !filter_var($profileEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }

        if ($profileAddress === '') {
            $errors[] = 'Vui lòng nhập địa chỉ giao hàng.';
        }

        if (empty($errors)) {
            $existingSlot = db_fetch_one_prepared(
                'SELECT id FROM user_shipping_profiles WHERE user_id = ? AND slot_number = ? ORDER BY id DESC LIMIT 1',
                'ii',
                [$user['id'], $slotNumber]
            );

            if ($existingSlot) {
                $slotId = (int)$existingSlot['id'];
                $stmt = db_prepare('UPDATE user_shipping_profiles SET full_name = ?, phone = ?, email = ?, shipping_address = ?, note = ? WHERE id = ? AND user_id = ?');
                $stmt->bind_param('sssssii', $profileFullName, $profilePhone, $profileEmail, $profileAddress, $profileNote, $slotId, $user['id']);
                $stmt->execute();
                $selected_profile_id = $slotId;
            } else {
                $isDefault = 0;
                $stmt = db_prepare('INSERT INTO user_shipping_profiles (user_id, slot_number, full_name, phone, email, shipping_address, note, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('iisssssi', $user['id'], $slotNumber, $profileFullName, $profilePhone, $profileEmail, $profileAddress, $profileNote, $isDefault);
                $stmt->execute();
                $selected_profile_id = (int)db_insert_id();
            }

            $successMessage = 'Đã lưu mẫu thông tin ' . $slotNumber . '.';
        }

        $shippingProfiles = load_shipping_profiles_by_user($user['id']);
        $slots = map_profiles_to_slots($shippingProfiles);
    }

    if (isset($_POST['place_order'])) {
      $selectedProfile = null;
      $orderFullName = '';
      $orderPhone = '';
      $orderShippingAddress = '';
      $orderEmail = '';

      if ($selected_source === 'account') {
        $orderFullName = trim((string)($user['full_name'] ?? ''));
        $orderPhone = trim((string)($user['phone'] ?? ''));
        $orderShippingAddress = trim((string)$account_shipping_address);
        $orderEmail = trim((string)($user['email'] ?? ''));

        if ($orderFullName === '' || $orderPhone === '' || $orderShippingAddress === '') {
          $errors[] = 'Thông tin tài khoản chưa đủ để giao hàng. Vui lòng cập nhật hồ sơ hoặc chọn 1 trong 3 mẫu thông tin.';
        }
      } else {
        if ($selected_profile_id <= 0) {
          $errors[] = 'Vui lòng chọn mẫu thông tin trước khi đặt hàng.';
        }

        if (empty($errors)) {
          $selectedProfile = db_fetch_one_prepared(
            'SELECT id, full_name, phone, email, shipping_address FROM user_shipping_profiles WHERE id = ? AND user_id = ? LIMIT 1',
            'ii',
            [$selected_profile_id, $user['id']]
          );
          if (!$selectedProfile) {
            $errors[] = 'Không tìm thấy mẫu thông tin đã chọn.';
          }
        }

        if ($selectedProfile) {
          $orderFullName = (string)$selectedProfile['full_name'];
          $orderPhone = (string)$selectedProfile['phone'];
          $orderShippingAddress = (string)$selectedProfile['shipping_address'];
          $orderEmail = trim((string)($selectedProfile['email'] ?? ''));
          if ($orderEmail === '') {
            $orderEmail = (string)($user['email'] ?? '');
          }
        }
      }

      if (empty($errors)) {
            $totalAmount = cart_total();
            $orderNumber = 'ORD' . date('YmdHis') . rand(100, 999);

            $conn = db_connect();
            $conn->begin_transaction();

            try {
                $stmt = db_prepare('INSERT INTO orders (order_number, user_id, full_name, phone, email, shipping_address, address_type, payment_method, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sissssssd', $orderNumber, $user['id'], $orderFullName, $orderPhone, $orderEmail, $orderShippingAddress, $addressType, $payment_method, $totalAmount);
                $stmt->execute();
                $orderId = db_insert_id();

                $orderItemStmt = db_prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
                foreach ($items as $item) {
                    if ($item['cart_quantity'] > $item['quantity']) {
                        throw new RuntimeException('Sản phẩm "' . $item['name'] . '" chỉ còn ' . (int)$item['quantity'] . ' sản phẩm trong kho.');
                    }
                    $orderItemStmt->bind_param('iiidd', $orderId, $item['id'], $item['cart_quantity'], $item['sale_price'], $item['line_total']);
                    $orderItemStmt->execute();
                }

                $conn->commit();
                cart_clear();
                redirect(app_url('order_success.php?order_number=' . urlencode($orderNumber)));
            } catch (Throwable $ex) {
                $conn->rollback();
                $errors[] = 'Lỗi khi tạo đơn hàng: ' . $ex->getMessage();
            }
        }
    }
}

$shippingProfiles = load_shipping_profiles_by_user($user['id']);
$slots = map_profiles_to_slots($shippingProfiles);
if ($selected_source !== 'account' && $selected_profile_id <= 0) {
    foreach ($slots as $slotProfile) {
        if ($slotProfile) {
            $selected_profile_id = (int)$slotProfile['id'];
            break;
        }
    }
}

$selectedProfile = null;
if ($selected_source !== 'account') {
  foreach ($slots as $slotProfile) {
    if ($slotProfile && (int)$slotProfile['id'] === (int)$selected_profile_id) {
      $selectedProfile = $slotProfile;
            break;
        }
    }
  if (!$selectedProfile) {
    foreach ($slots as $slotProfile) {
      if ($slotProfile) {
        $selectedProfile = $slotProfile;
        $selected_profile_id = (int)$slotProfile['id'];
        break;
      }
    }
  }
}

$profilesForJs = [];
foreach ($slots as $slotNumber => $slotProfile) {
    if ($slotProfile) {
        $profilesForJs[$slotNumber] = [
            'id' => (int)$slotProfile['id'],
            'slot_number' => (int)$slotProfile['slot_number'],
            'full_name' => (string)$slotProfile['full_name'],
            'phone' => (string)$slotProfile['phone'],
            'email' => (string)($slotProfile['email'] ?? ''),
            'shipping_address' => (string)$slotProfile['shipping_address'],
            'note' => (string)($slotProfile['note'] ?? ''),
        ];
    }
}

$accountProfileForJs = [
  'full_name' => (string)($user['full_name'] ?? ''),
  'phone' => (string)($user['phone'] ?? ''),
  'email' => (string)($user['email'] ?? ''),
  'shipping_address' => (string)$account_shipping_address,
  'note' => '',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Thanh toán</title>
    <link rel="stylesheet" href="./bootstrap-4.6.2-dist/css/bootstrap.css" />
    <link rel="stylesheet" href="./fontawesome-free-6.7.2-web/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />
    <style>
      :root {
        --bg: #f9fafb;
        --surface: #ffffff;
        --surface-2: #fafbfc;
        --line: #e5e7eb;
        --text: #1a1a1a;
        --muted: #666666;
        --accent: #0d6efd;
        --accent-soft: #f0f6ff;
      }

      body {
        margin: 0;
        background: #f9fafb;
        font-family: system-ui, 'Segoe UI', Roboto, Arial, sans-serif;
        color: var(--text);
      }

      .checkout-page {
        max-width: 1080px;
        margin: 20px auto;
        padding: 0 14px;
      }

      .checkout-page h1 {
        font-size: 28px;
        font-weight: 700;
        color: #111;
        margin: 0 0 6px;
      }

      .checkout-page p {
        color: #999;
        font-size: 14px;
        margin: 0;
      }

      .checkbox-page > .d-flex {
        margin-bottom: 20px;
      }

      .checkout-page h4 {
        font-weight: 700;
        font-size: 18px;
        color: #111;
        margin: 0 0 16px;
      }

      .card,
      .order-summary {
        border-radius: 12px;
        border: 1px solid #f0f1f3;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        background: var(--surface);
      }

      .card {
        padding: 22px;
      }

      .order-summary {
        padding: 20px;
        display: flex;
        flex-direction: column;
        height: 100%;
      }

      .order-summary .item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 14px;
      }

      .order-summary .item strong {
        color: #111;
        font-weight: 700;
      }

      .order-summary .item .text-muted {
        color: #999;
        font-size: 13px;
      }

      .order-summary hr {
        margin: 12px 0;
        border-color: #e5e7eb;
      }

      .order-summary .fw-bold {
        font-weight: 700;
        color: #111;
      }

      .selected-profile-box {
        border: 1px solid #e5e7eb;
        border-left: 3px solid #0d6efd;
        border-radius: 10px;
        padding: 14px;
        background: #fafbfc;
        color: #333;
        font-size: 13px;
        line-height: 1.6;
        margin-bottom: 14px;
      }

      .selected-profile-box strong {
        color: #111;
        font-size: 14px;
        font-weight: 700;
      }

      .selected-profile-box div {
        color: #555;
      }

      .profile-actions {
        display: flex;
        gap: 10px;
        flex-wrap: nowrap;
        margin-bottom: 18px;
      }

      .profile-actions .btn {
        border-radius: 8px;
        font-weight: 600;
        padding: 10px 16px;
        flex: 1 1 0;
        white-space: nowrap;
        font-size: 13px;
        border: 1px solid #ddd;
      }

      .btn-outline-primary {
        color: #0d6efd;
        border-color: #ddd;
        background: #fff;
      }

      .btn-outline-primary:hover,
      .btn-outline-primary:focus {
        color: #0a58ca;
        border-color: #bbb;
        background: #f0f6ff;
      }

      .btn-outline-secondary {
        color: #555;
        border-color: #ddd;
        background: #fff;
      }

      .btn-outline-secondary:hover,
      .btn-outline-secondary:focus {
        background: #f5f5f5;
        border-color: #bbb;
        color: #333;
      }

      .btn-primary {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
      }

      .btn-primary:hover,
      .btn-primary:focus {
        background: #0a58ca;
        border-color: #0a58ca;
      }

      .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
      }

      .form-control,
      .form-select,
      select.form-select,
      select[name="address_type"],
      select[name="payment_method"] {
        border-radius: 8px;
        border: 1px solid #ddd;
        height: 38px;
        font-size: 13px;
        background-color: #fff;
      }

      .form-control:focus,
      .form-select:focus,
      select[name="address_type"]:focus,
      select[name="payment_method"]:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.1);
      }

      .custom-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(2px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1040;
        padding: 16px;
      }

      .custom-modal-backdrop.is-open { display: flex; }

      .custom-modal {
        width: 100%;
        max-width: 700px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
        overflow: hidden;
      }

      .custom-modal-header {
        padding: 18px 20px 12px;
        border-bottom: 1px solid #e5e7eb;
        position: relative;
        background: #fff;
      }

      .custom-modal-header h4 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 700;
        color: #111;
      }

      .custom-modal-header p {
        margin: 6px 0 0;
        color: #999;
        font-size: 13px;
      }

      .custom-modal-close {
        position: absolute;
        right: 14px;
        top: 12px;
        width: 32px;
        height: 32px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #fff;
        font-size: 18px;
        line-height: 1;
        color: #888;
      }

      .custom-modal-close:hover { background: #f5f5f5; }

      .custom-modal-body {
        padding: 16px 20px 18px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
      }

      .custom-modal-body::-webkit-scrollbar { width: 6px; }
      .custom-modal-body::-webkit-scrollbar-thumb { background: #ddd; border-radius: 999px; }

      .profile-template-item {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 10px;
        background: #fff;
        font-size: 13px;
      }

      .profile-template-item:last-child { margin-bottom: 0; }

      .profile-template-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
      }

      .profile-template-item strong {
        color: #111;
        font-size: 13px;
        font-weight: 700;
      }

      .profile-template-item div {
        color: #555;
        line-height: 1.5;
      }

      .profile-template-item .text-muted {
        color: #999;
      }

      .profile-template-item button {
        border: 1px solid #ddd;
        color: #0d6efd;
        background: #fff;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
      }

      .profile-template-item button:hover { background: #f0f6ff; }

      .profile-template-item button.is-disabled {
        border-color: #e5e7eb;
        color: #999;
        background: #fafbfc;
        cursor: not-allowed;
      }

      .modal-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
      .modal-form-grid .full { grid-column: 1 / -1; }

      .custom-modal-body .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
      }

      .alert {
        font-size: 13px;
        padding: 12px 16px;
        margin-bottom: 16px;
      }

      .alert-danger {
        background: #fee;
        border: 1px solid #fcc;
        color: #c33;
      }

      .alert-success {
        background: #efe;
        border: 1px solid #cfc;
        color: #3c3;
      }

      .alert-info {
        background: #eff;
        border: 1px solid #cff;
        color: #3cc;
      }

      @media (max-width: 768px) {
        .checkout-page { padding: 0 12px; }
        .checkout-page h1 { font-size: 24px; }
        .card { padding: 16px; }
        .custom-modal { max-width: 92vw; border-radius: 10px; }
        .custom-modal-header { padding: 14px 16px 10px; }
        .custom-modal-header h4 { font-size: 16px; }
        .custom-modal-body { padding: 12px 16px 14px; }
        .profile-actions { flex-wrap: wrap; }
        .profile-actions .btn { flex: 1 1 100%; }
        .profile-template-head { align-items: flex-start; }
        .modal-form-grid { grid-template-columns: 1fr; }
      }
    </style>
</head>
<body>
  <div class="checkout-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3">Thanh toán</h1>
        <p class="text-muted mb-0">Hoàn tất thông tin đặt hàng và thanh toán.</p>
      </div>
      <div>
        <a href="./cart.php" class="btn btn-outline-secondary">Quay lại giỏ hàng</a>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?php echo h($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
      <div class="alert alert-success"><?php echo h($successMessage); ?></div>
    <?php endif; ?>

    <div class="row g-4 align-items-stretch">
      <div class="col-lg-7">
        <div class="card">
          <h4>Thông tin giao hàng</h4>

          <div class="selected-profile-box mb-3" id="selected_profile_preview">
            <?php if ($selected_source === 'account'): ?>
              <div><strong>Thông tin tài khoản đang đăng nhập</strong></div>
              <div>Họ tên: <?php echo h((string)($user['full_name'] ?? '')); ?></div>
              <div>SĐT: <?php echo h((string)($user['phone'] ?? '')); ?></div>
              <div>Email: <?php echo h((string)($user['email'] ?? '')); ?></div>
              <div>Địa chỉ: <?php echo h((string)$account_shipping_address); ?></div>
              <div>Ghi chú: Không có</div>
            <?php elseif ($selectedProfile): ?>
              <div><strong>Mẫu thông tin <?php echo h((string)$selectedProfile['slot_number']); ?></strong></div>
              <div>Họ tên: <?php echo h((string)$selectedProfile['full_name']); ?></div>
              <div>SĐT: <?php echo h((string)$selectedProfile['phone']); ?></div>
              <div>Email: <?php echo h((string)($selectedProfile['email'] ?? '')); ?></div>
              <div>Địa chỉ: <?php echo h((string)$selectedProfile['shipping_address']); ?></div>
              <div>Ghi chú: <?php echo h((string)($selectedProfile['note'] !== '' ? $selectedProfile['note'] : 'Không có')); ?></div>
            <?php else: ?>
              <div>Chưa có thông tin giao hàng.</div>
            <?php endif; ?>
          </div>

          <div class="profile-actions mb-3">
            <button type="button" class="btn btn-outline-primary" id="btn_open_choose">Chọn mẫu thông tin</button>
            <button type="button" class="btn btn-outline-secondary" id="btn_open_edit">Thay đổi thông tin người mua</button>
          </div>

          <form method="post" novalidate>
            <input type="hidden" name="selected_profile_id" id="selected_profile_id" value="<?php echo h((string)$selected_profile_id); ?>" />
            <input type="hidden" name="selected_source" id="selected_source" value="<?php echo h($selected_source); ?>" />
            <div class="mb-3">
              <label class="form-label">Loại địa chỉ</label>
              <select name="address_type" class="form-select">
                <option value="home" <?php echo $addressType === 'home' ? 'selected' : ''; ?>>Nhà riêng</option>
                <option value="office" <?php echo $addressType === 'office' ? 'selected' : ''; ?>>Văn phòng</option>
                <option value="business" <?php echo $addressType === 'business' ? 'selected' : ''; ?>>Công ty</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Phương thức thanh toán</label>
              <select name="payment_method" class="form-select" id="payment_method">
                <option value="cash" <?php echo $payment_method === 'cash' ? 'selected' : ''; ?>>Thanh toán khi nhận hàng</option>
                <option value="bank" <?php echo $payment_method === 'bank' ? 'selected' : ''; ?>>Chuyển khoản ngân hàng</option>
                <option value="online" <?php echo $payment_method === 'online' ? 'selected' : ''; ?>>Thanh toán trực tuyến</option>
              </select>
            </div>
            <div id="bank_info" class="alert alert-info small mb-3" style="display:none;">
              Chuyển khoản tới: Ngân hàng ABC - STK 123456789 - Chủ TK: GENTLEMAN.
            </div>
            <button type="submit" name="place_order" value="1" class="btn btn-primary">Đặt hàng ngay</button>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="order-summary">
          <h4>Đơn hàng của bạn</h4>
          <?php foreach ($items as $item): ?>
            <div class="item">
              <div>
                <strong><?php echo h((string)$item['name']); ?></strong>
                <div class="text-muted small">Số lượng: <?php echo h((string)$item['cart_quantity']); ?></div>
              </div>
              <div class="text-end"><?php echo number_format((float)$item['line_total'], 0, ',', '.'); ?>₫</div>
            </div>
          <?php endforeach; ?>
          <hr />
          <div class="item">
            <div class="text-muted">Tổng thanh toán</div>
            <div class="fw-bold"><?php echo number_format((float)$total, 0, ',', '.'); ?>₫</div>
          </div>
          <p class="text-muted small mt-3">Bạn đang đặt hàng dưới tài khoản <strong><?php echo h((string)$user['email']); ?></strong>.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="custom-modal-backdrop" id="choose_modal">
    <div class="custom-modal" role="dialog" aria-modal="true">
      <div class="custom-modal-header">
        <h4>Chọn mẫu thông tin</h4>
        <p>Chọn mẫu bạn muốn dùng cho đơn hàng này</p>
        <button type="button" class="custom-modal-close" data-close-modal="choose_modal">×</button>
      </div>
      <div class="custom-modal-body">
        <div class="profile-template-item">
          <div class="profile-template-head">
            <strong>Thông tin tài khoản đăng nhập</strong>
            <button type="button" class="choose-account-btn">Chọn thông tin</button>
          </div>
          <div>Họ tên: <?php echo h((string)($user['full_name'] ?? '')); ?></div>
          <div>Email: <?php echo h((string)($user['email'] ?? '')); ?></div>
          <div>Phone: <?php echo h((string)($user['phone'] ?? '')); ?></div>
          <div>Địa chỉ: <?php echo h((string)$account_shipping_address); ?></div>
          <div>Ghi chú: Không có</div>
        </div>
        <?php for ($slot = 1; $slot <= 3; $slot++): ?>
          <?php $slotProfile = $slots[$slot] ?? null; ?>
          <div class="profile-template-item">
            <div class="profile-template-head">
              <strong>Thông tin <?php echo h((string)$slot); ?></strong>
              <?php if ($slotProfile): ?>
                <button type="button" class="choose-profile-btn" data-profile-id="<?php echo h((string)$slotProfile['id']); ?>" data-slot="<?php echo h((string)$slot); ?>">Chọn thông tin</button>
              <?php else: ?>
                <button type="button" class="is-disabled" disabled>Chưa có</button>
              <?php endif; ?>
            </div>
            <?php if ($slotProfile): ?>
              <div>Họ tên: <?php echo h((string)$slotProfile['full_name']); ?></div>
              <div>Email: <?php echo h((string)($slotProfile['email'] ?? '')); ?></div>
              <div>Phone: <?php echo h((string)$slotProfile['phone']); ?></div>
              <div>Địa chỉ: <?php echo h((string)$slotProfile['shipping_address']); ?></div>
              <div>Ghi chú: <?php echo h((string)($slotProfile['note'] !== '' ? $slotProfile['note'] : 'Không có')); ?></div>
            <?php else: ?>
              <div class="text-muted">Chưa có dữ liệu cho mẫu này.</div>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <div class="custom-modal-backdrop" id="edit_modal">
    <div class="custom-modal" role="dialog" aria-modal="true">
      <div class="custom-modal-header">
        <h4>Thay đổi thông tin người mua</h4>
        <p>Thông tin đã được điền sẵn, bạn có thể chỉnh lại mọi lúc</p>
        <button type="button" class="custom-modal-close" data-close-modal="edit_modal">×</button>
      </div>
      <div class="custom-modal-body">
        <form method="post" novalidate>
          <input type="hidden" name="profile_action" value="save_slot" />
          <input type="hidden" name="selected_profile_id" value="<?php echo h((string)$selected_profile_id); ?>" />
          <div class="mb-3">
            <label class="form-label">Lưu vào *</label>
            <select name="slot_number" id="slot_number" class="form-select" required>
              <option value="1">Thông tin 1</option>
              <option value="2">Thông tin 2</option>
              <option value="3">Thông tin 3</option>
            </select>
          </div>
          <div class="modal-form-grid">
            <div>
              <label class="form-label">Họ tên *</label>
              <input class="form-control" type="text" name="profile_full_name" id="profile_full_name" required />
            </div>
            <div>
              <label class="form-label">Địa chỉ *</label>
              <input class="form-control" type="text" name="profile_address" id="profile_address" required />
            </div>
            <div>
              <label class="form-label">Số điện thoại *</label>
              <input class="form-control" type="text" name="profile_phone" id="profile_phone" required />
            </div>
            <div>
              <label class="form-label">Ghi chú (tùy chọn)</label>
              <textarea class="form-control" name="profile_note" id="profile_note" rows="3" placeholder="Ghi chú về đơn hàng..."></textarea>
            </div>
            <div class="full">
              <label class="form-label">Email *</label>
              <input class="form-control" type="email" name="profile_email" id="profile_email" />
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-primary w-100">Lưu mẫu</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var payment = document.getElementById('payment_method');
      var bankInfo = document.getElementById('bank_info');
      var selectedProfileInput = document.getElementById('selected_profile_id');
      var selectedSourceInput = document.getElementById('selected_source');
      var chooseModal = document.getElementById('choose_modal');
      var editModal = document.getElementById('edit_modal');
      var btnOpenChoose = document.getElementById('btn_open_choose');
      var btnOpenEdit = document.getElementById('btn_open_edit');
      var slotSelect = document.getElementById('slot_number');
      var fullNameInput = document.getElementById('profile_full_name');
      var phoneInput = document.getElementById('profile_phone');
      var emailInput = document.getElementById('profile_email');
      var addressInput = document.getElementById('profile_address');
      var noteInput = document.getElementById('profile_note');
      var selectedPreview = document.getElementById('selected_profile_preview');
      var profileData = <?php echo json_encode($profilesForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || {};
      var accountData = <?php echo json_encode($accountProfileForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || {};

      function updatePayment() {
        if (payment && payment.value === 'bank') {
          bankInfo.style.display = 'block';
        } else {
          bankInfo.style.display = 'none';
        }
      }

      function openModal(modalEl) {
        if (modalEl) {
          modalEl.classList.add('is-open');
        }
      }

      function closeModal(modalEl) {
        if (modalEl) {
          modalEl.classList.remove('is-open');
        }
      }

      function setEditorBySlot(slotNumber) {
        var slot = String(slotNumber);
        var data = profileData[slot] || null;
        if (data) {
          fullNameInput.value = data.full_name || '';
          phoneInput.value = data.phone || '';
          emailInput.value = data.email || '';
          addressInput.value = data.shipping_address || '';
          noteInput.value = data.note || '';
          return;
        }

        fullNameInput.value = '';
        phoneInput.value = '';
        emailInput.value = <?php echo json_encode((string)($user['email'] ?? '')); ?>;
        addressInput.value = '';
        noteInput.value = '';
      }

      function renderSelectedPreviewById(profileId) {
        var chosen = null;
        Object.keys(profileData).forEach(function (slot) {
          var item = profileData[slot];
          if (item && String(item.id) === String(profileId)) {
            chosen = item;
          }
        });
        if (!chosen || !selectedPreview) {
          return;
        }

        selectedPreview.innerHTML = '' +
          '<div><strong>Mẫu thông tin ' + chosen.slot_number + '</strong></div>' +
          '<div>Họ tên: ' + (chosen.full_name || '') + '</div>' +
          '<div>SĐT: ' + (chosen.phone || '') + '</div>' +
          '<div>Email: ' + (chosen.email || '') + '</div>' +
          '<div>Địa chỉ: ' + (chosen.shipping_address || '') + '</div>' +
          '<div>Ghi chú: ' + (chosen.note ? chosen.note : 'Không có') + '</div>';
      }

      function renderSelectedAccountPreview() {
        if (!selectedPreview) {
          return;
        }
        selectedPreview.innerHTML = '' +
          '<div><strong>Thông tin tài khoản đang đăng nhập</strong></div>' +
          '<div>Họ tên: ' + (accountData.full_name || '') + '</div>' +
          '<div>SĐT: ' + (accountData.phone || '') + '</div>' +
          '<div>Email: ' + (accountData.email || '') + '</div>' +
          '<div>Địa chỉ: ' + (accountData.shipping_address || '') + '</div>' +
          '<div>Ghi chú: Không có</div>';
      }

      if (payment) {
        payment.addEventListener('change', updatePayment);
      }
      updatePayment();

      if (btnOpenChoose) {
        btnOpenChoose.addEventListener('click', function () {
          openModal(chooseModal);
        });
      }

      if (btnOpenEdit) {
        btnOpenEdit.addEventListener('click', function () {
          if (slotSelect) {
            var foundSlot = '1';
            Object.keys(profileData).forEach(function (slot) {
              var item = profileData[slot];
              if (item && String(item.id) === String(selectedProfileInput.value)) {
                foundSlot = slot;
              }
            });
            slotSelect.value = foundSlot;
            setEditorBySlot(foundSlot);
          }
          openModal(editModal);
        });
      }

      document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var targetId = btn.getAttribute('data-close-modal');
          var modalEl = document.getElementById(targetId);
          closeModal(modalEl);
        });
      });

      [chooseModal, editModal].forEach(function (modalEl) {
        if (!modalEl) {
          return;
        }
        modalEl.addEventListener('click', function (event) {
          if (event.target === modalEl) {
            closeModal(modalEl);
          }
        });
      });

      document.querySelectorAll('.choose-profile-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var pickedId = btn.getAttribute('data-profile-id');
          selectedProfileInput.value = pickedId;
          selectedSourceInput.value = 'profile';
          renderSelectedPreviewById(pickedId);
          closeModal(chooseModal);
        });
      });

      document.querySelectorAll('.choose-account-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          selectedSourceInput.value = 'account';
          selectedProfileInput.value = '0';
          renderSelectedAccountPreview();
          closeModal(chooseModal);
        });
      });

      if (slotSelect) {
        slotSelect.addEventListener('change', function () {
          setEditorBySlot(slotSelect.value);
        });
        setEditorBySlot(slotSelect.value);
      }
    })();
  </script>
</body>
</html>
