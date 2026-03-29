<?php
require_once __DIR__ . '/../../includes/common.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

function fetchUsers() {
    $rows = db_fetch_all('SELECT id, full_name AS name, email, phone, address, status, created_at FROM users ORDER BY created_at DESC');
    $rows = array_map(function ($row) {
        $row['role'] = 'customer';
        return $row;
    }, $rows);
    return $rows;
}

function validateEmail(string $email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        jsonResponse(['success' => true, 'users' => fetchUsers()]);
    }
    jsonResponse(['success' => false, 'message' => 'Phương thức GET không hợp lệ cho hành động này.'], 400);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Phương thức không được hỗ trợ.'], 405);
}

$input = $_POST;

if ($action === 'upsert') {
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $password = $input['password'] ?? '';
    $status = $input['status'] ?? 'active';

    if (!$name || !$email || !validateEmail($email)) {
        jsonResponse(['success' => false, 'message' => 'Tên và email hợp lệ là bắt buộc.'], 400);
    }
    if ($password && strlen($password) < 4) {
        jsonResponse(['success' => false, 'message' => 'Mật khẩu tối thiểu 4 ký tự.'], 400);
    }

    $existing = db_fetch_one_prepared('SELECT * FROM users WHERE email = ? LIMIT 1', 's', [$email]);
    if ($existing) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db_prepare('UPDATE users SET full_name = ?, phone = ?, address = ?, status = ?, password_hash = ? WHERE id = ?');
            $stmt->bind_param('sssssi', $name, $phone, $address, $status, $hash, $existing['id']);
        } else {
            $stmt = db_prepare('UPDATE users SET full_name = ?, phone = ?, address = ?, status = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $name, $phone, $address, $status, $existing['id']);
        }
        $stmt->execute();
    } else {
        if (!$password) {
            $password = bin2hex(random_bytes(4));
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db_prepare('INSERT INTO users (full_name, email, password_hash, phone, address, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssss', $name, $email, $hash, $phone, $address, $status);
        $stmt->execute();
    }

    jsonResponse(['success' => true, 'message' => 'Đã lưu tài khoản khách hàng.']);
}

if ($action === 'set_status') {
    $email = strtolower(trim($input['email'] ?? ''));
    $status = trim($input['status'] ?? 'active');
    if (!$email || !in_array($status, ['active', 'locked'], true)) {
        jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], 400);
    }

    $member = db_fetch_one_prepared('SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email]);
    if (empty($member)) {
        jsonResponse(['success' => false, 'message' => 'Không tìm thấy khách hàng.'], 404);
    }

    $stmt = db_prepare('UPDATE users SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $member['id']);
    $stmt->execute();

    jsonResponse(['success' => true, 'message' => 'Đã cập nhật trạng thái khách hàng.']);
}

if ($action === 'reset_password') {
    $email = strtolower(trim($input['email'] ?? ''));
    $password = trim($input['password'] ?? '');
    if (!$email || !$password || strlen($password) < 4) {
        jsonResponse(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 4 ký tự.'], 400);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $member = db_fetch_one_prepared('SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email]);
    if (empty($member)) {
        jsonResponse(['success' => false, 'message' => 'Không tìm thấy khách hàng.'], 404);
    }

    $stmt = db_prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->bind_param('si', $hash, $member['id']);
    $stmt->execute();

    jsonResponse(['success' => true, 'message' => 'Đã đặt lại mật khẩu khách hàng.']);
}

jsonResponse(['success' => false, 'message' => 'Hành động không hợp lệ.'], 400);
