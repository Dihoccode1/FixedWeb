<?php
require_once __DIR__ . '/db.php';

function configure_session_storage() {
    $handler = trim($_ENV['SESSION_SAVE_HANDLER'] ?? '');
    $savePath = trim($_ENV['SESSION_SAVE_PATH'] ?? '');
    $cookieSecure = strtolower(trim($_ENV['SESSION_COOKIE_SECURE'] ?? '0'));
    $cookieHttpOnly = strtolower(trim($_ENV['SESSION_COOKIE_HTTPONLY'] ?? '1'));
    $cookieSameSite = trim($_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax');

    if ($handler !== '') {
        ini_set('session.save_handler', $handler);
    }
    if ($savePath !== '') {
        ini_set('session.save_path', $savePath);
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    $secureRequested = in_array($cookieSecure, ['1', 'true', 'yes'], true);

    ini_set('session.cookie_httponly', in_array($cookieHttpOnly, ['1', 'true', 'yes'], true) ? '1' : '0');
    ini_set('session.cookie_secure', ($secureRequested && $isHttps) ? '1' : '0');
    if ($cookieSameSite !== '') {
        ini_set('session.cookie_samesite', $cookieSameSite);
    }
}

configure_session_storage();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Đồng bộ trạng thái login server-side với client-side qua cookie
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['email'])) {
    setcookie('SV_AUTH_EMAIL', $_SESSION['user']['email'], 0, '/');
    setcookie('SV_AUTH_NAME', $_SESSION['user']['full_name'] ?? $_SESSION['user']['name'] ?? '', 0, '/');
} else {
    setcookie('SV_AUTH_EMAIL', '', time() - 3600, '/');
    setcookie('SV_AUTH_NAME', '', time() - 3600, '/');
}

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function normalizeAssetUrl($path) {
    if (!$path) {
        return '';
    }
    if (preg_match('#^(?:https?:)?//#', $path) || strpos($path, '/') === 0) {
        return $path;
    }
    return preg_replace('#^(?:\.{2}/)+#', '', $path);
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function app_base_path() {
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $incDir = realpath(__DIR__) ?: '';

    if ($docRoot !== '' && $incDir !== '' && stripos($incDir, $docRoot) === 0) {
        $relative = trim(str_replace('\\', '/', substr($incDir, strlen($docRoot))), '/');
        $parts = $relative === '' ? [] : explode('/', $relative);
        if (!empty($parts) && end($parts) === 'includes') {
            array_pop($parts);
        }
        $base = '/' . (!empty($parts) ? implode('/', $parts) . '/' : '');
        return $base;
    }

    $base = '/';
    return $base;
}

function app_url($path = '') {
    $url = app_base_path() . ltrim($path, '/');
    $url = preg_replace('#/+#', '/', $url);
    if ($url === '' || $url[0] !== '/') {
        $url = '/' . ltrim($url, '/');
    }
    return $url;
}

function app_relative_prefix() {
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = trim(dirname($scriptName), '/.');
    $scriptParts = $scriptDir === '' ? [] : array_values(array_filter(explode('/', $scriptDir), 'strlen'));

    $base = trim(app_base_path(), '/');
    $baseParts = $base === '' ? [] : array_values(array_filter(explode('/', $base), 'strlen'));

    $remain = $scriptParts;
    if (!empty($baseParts) && count($scriptParts) >= count($baseParts)) {
        $matchesBase = true;
        for ($i = 0; $i < count($baseParts); $i++) {
            if (($scriptParts[$i] ?? null) !== $baseParts[$i]) {
                $matchesBase = false;
                break;
            }
        }
        if ($matchesBase) {
            $remain = array_slice($scriptParts, count($baseParts));
        }
    }

    $depth = count($remain);
    return $depth > 0 ? str_repeat('../', $depth) : './';
}

function app_rel_url($path = '') {
    return app_relative_prefix() . ltrim($path, '/');
}

function app_origin() {
    static $origin = null;
    if ($origin !== null) {
        return $origin;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    $scheme = $isHttps ? 'https' : 'http';

    $host = trim((string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'));
    if (strpos($host, ',') !== false) {
        $parts = explode(',', $host);
        $host = trim($parts[0]);
    }
    if ($host === '') {
        $host = 'localhost';
    }

    $origin = $scheme . '://' . $host;
    return $origin;
}

function app_abs_url($path = '') {
    return rtrim(app_origin(), '/') . app_url($path);
}

function asset_versioned_url($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(?:https?:)?//#i', $path) || strpos($path, '//') === 0) {
        return $path;
    }

    $cleanPath = preg_replace('/[?#].*$/', '', $path);
    $filePath = null;

    if (strpos($cleanPath, '/') === 0) {
        $filePath = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
    } else {
        $scriptDir = dirname(str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? '')));
        $filePath = $scriptDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($cleanPath, './'));
    }

    $version = '';
    if ($filePath !== null && is_file($filePath)) {
        $version = (string)filemtime($filePath);
    }

    if ($version === '') {
        return $path;
    }

    return $path . (strpos($path, '?') !== false ? '&' : '?') . 'v=' . $version;
}

function flash_message($message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = $message;
}

function pop_flash_messages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return is_array($messages) ? $messages : [];
}

function current_user() {
    if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['email'])) {
        $email = trim(strtolower($_SESSION['user']['email']));
        $user = db_fetch_one_prepared('SELECT id, full_name, email, phone, street_address, ward, district, city_province, address, status FROM users WHERE email = ? LIMIT 1', 's', [$email]);
        if ($user && ($user['status'] ?? 'active') === 'active') {
            return [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? '',
                'street_address' => $user['street_address'] ?? '',
                'ward' => $user['ward'] ?? '',
                'district' => $user['district'] ?? '',
                'city_province' => $user['city_province'] ?? '',
                'address' => $user['address'] ?? '',
            ];
        }
        flash_message('Tài khoản của bạn đã bị khóa.');
        unset($_SESSION['user']);
        redirect(app_rel_url('account/login.php') . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    return null;
}

function admin_user() {
    return isset($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

function render_topbar() {
    $user = isset($GLOBALS['user']) ? $GLOBALS['user'] : current_user();
    $profileUrl = app_rel_url('account/profile.php');
    $logoutUrl = app_rel_url('account/logout.php');
    $registerUrl = app_rel_url('account/register.php');
    $loginUrl = app_rel_url('account/login.php');
    echo '<header class="header">';
    echo '<div class="topbar">';
    echo '<div class="container">';
    echo '<div class="topbar-right" data-auth-login-url="' . h($loginUrl) . '" data-auth-register-url="' . h($registerUrl) . '" data-auth-profile-url="' . h($profileUrl) . '" data-auth-logout-url="' . h($logoutUrl) . '">';
    if ($user) {
        echo '<a href="' . h($profileUrl) . '" class="welcome-user" style="text-decoration:none;"><span class="welcome-message">Xin chào</span><span class="user-name">' . h($user['full_name'] ?? $user['name'] ?? 'Khách') . '</span></a>';
        echo '<a href="' . h($logoutUrl) . '" class="btn btn-primary">Đăng xuất</a>';
    } else {
        echo '<a href="' . h($registerUrl) . '" class="btn btn-outline">Đăng ký</a>';
        echo '<a href="' . h($loginUrl) . '" class="btn btn-primary">Đăng nhập</a>';
    }
    echo '</div></div></div></header>';
}

function require_login() {
    if (!current_user()) {
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        redirect(app_rel_url('account/login.php') . '?redirect=' . $redirect);
    }
}

function require_admin() {
    if (empty($_SESSION['admin']) || $_SESSION['admin']['role'] !== 'admin') {
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        if (strpos($accept, 'application/json') !== false || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Yêu cầu đăng nhập quản trị.']);
            exit;
        }
        redirect(app_rel_url('Admin/login.php'));
    }
}

function old($key, $default = '') {
    return isset($_POST[$key]) ? h($_POST[$key]) : h($default);
}

function cart_cookie_name() {
    $email = '';
    if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['email'])) {
        $email = strtolower(trim((string)$_SESSION['user']['email']));
    }
    if ($email === '') {
        return 'SV_CART_DATA_GUEST';
    }
    return 'SV_CART_DATA_' . substr(sha1($email), 0, 16);
}

function decode_cart_payload($raw) {
    if (!is_string($raw) || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    $cart = [];
    foreach ($decoded as $pid => $qty) {
        $id = (int)$pid;
        $q = (int)$qty;
        if ($id > 0 && $q > 0) {
            $cart[$id] = $q;
        }
    }
    return $cart;
}

function persist_cart_cookie(array $cart) {
    $name = cart_cookie_name();
    if (empty($cart)) {
        setcookie($name, '', time() - 3600, '/');
        return;
    }
    $payload = json_encode($cart, JSON_UNESCAPED_UNICODE);
    if (!is_string($payload)) {
        return;
    }
    setcookie($name, $payload, 0, '/');
}

function get_cart() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return $_SESSION['cart'];
    }

    $fromCookie = decode_cart_payload($_COOKIE[cart_cookie_name()] ?? '');
    if (!empty($fromCookie)) {
        $_SESSION['cart'] = $fromCookie;
        return $fromCookie;
    }

    return [];
}

function save_cart(array $cart) {
    $_SESSION['cart'] = $cart;
    persist_cart_cookie($cart);
}

function cart_add_item($productId, $quantity = 1) {
    $productId = (int)$productId;
    $quantity = max(1, (int)$quantity);
    $cart = get_cart();
    $cart[$productId] = isset($cart[$productId]) ? $cart[$productId] + $quantity : $quantity;
    save_cart($cart);
}

function cart_update_item($productId, $quantity) {
    $productId = (int)$productId;
    $quantity = max(0, (int)$quantity);
    $cart = get_cart();
    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }
    save_cart($cart);
}

function cart_remove_item($productId) {
    $productId = (int)$productId;
    $cart = get_cart();
    if (isset($cart[$productId])) {
        unset($cart[$productId]);
        save_cart($cart);
    }
}

function cart_clear() {
    unset($_SESSION['cart']);
    persist_cart_cookie([]);
}

function cart_items() {
    $cart = get_cart();
    if (empty($cart)) {
        return [];
    }

    // Always cap cart quantity by current stock so header count/total stays consistent.
    $normalizedCart = [];
    $ids = array_keys($cart);
    $idList = implode(',', array_map('intval', $ids));
    $rows = db_fetch_all("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id IN ($idList) AND c.status = 'active' AND p.status = 'selling'");

    $rowMap = [];
    foreach ($rows as $row) {
        $rowMap[(int)$row['id']] = $row;
    }

    $cartChanged = false;
    foreach ($cart as $productId => $qty) {
        $productId = (int)$productId;
        $qty = (int)$qty;
        if ($qty <= 0 || !isset($rowMap[$productId])) {
            $cartChanged = true;
            continue;
        }

        $stock = max(0, (int)($rowMap[$productId]['quantity'] ?? 0));
        if ($stock <= 0) {
            $cartChanged = true;
            continue;
        }

        if ($qty > $stock) {
            $qty = $stock;
            $cartChanged = true;
        }
        $normalizedCart[$productId] = $qty;
    }

    if ($cartChanged) {
        save_cart($normalizedCart);
    }

    $items = [];
    foreach ($rows as $row) {
        $rowId = (int)$row['id'];
        if (!isset($normalizedCart[$rowId])) {
            continue;
        }
        $row['cart_quantity'] = (int)$normalizedCart[$rowId];
        $row['line_total'] = $row['sale_price'] * $row['cart_quantity'];
        $items[] = $row;
    }
    return $items;
}

function cart_count() {
    $items = cart_items();
    return array_sum(array_column($items, 'cart_quantity'));
}

function cart_total() {
    $items = cart_items();
    return array_sum(array_column($items, 'line_total'));
}

// Đồng bộ số lượng giỏ hàng server-side với client-side bằng cookie
setcookie('SV_CART_COUNT', cart_count(), 0, '/');
