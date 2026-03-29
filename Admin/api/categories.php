<?php
require_once __DIR__ . '/../../includes/common.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function slugify($value) {
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = preg_replace('/-+/', '-', $value);
    $value = trim($value, '-');
    return $value ?: 'category-' . time();
}

$action = $_REQUEST['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $rows = db_fetch_all("SELECT id, name, slug, status, COALESCE(description, '') AS description, created_at FROM categories ORDER BY created_at DESC");
        jsonResponse(['success' => true, 'categories' => $rows]);
    }
    jsonResponse(['success' => false, 'message' => 'Phương thức GET không hợp lệ cho hành động này.'], 400);
}

if ($action === 'toggle') {
    $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID danh mục không hợp lệ.'], 400);
    }

    $category = db_fetch_one("SELECT id, status FROM categories WHERE id = $id LIMIT 1");
    if (!$category) {
        jsonResponse(['success' => false, 'message' => 'Không tìm thấy danh mục.'], 404);
    }

    $newStatus = $category['status'] === 'active' ? 'hidden' : 'active';
    $stmt = db_prepare('UPDATE categories SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $newStatus, $id);
    $stmt->execute();

    jsonResponse(['success' => true, 'message' => 'Đã cập nhật trạng thái danh mục.', 'status' => $newStatus]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Phương thức không được hỗ trợ.'], 405);
}

$input = $_POST;

if ($action === 'save') {

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $name = trim($input['name'] ?? '');
    // Nếu không có description thì để rỗng
    $description = isset($input['description']) ? trim($input['description']) : '';

    if (!$name) {
        jsonResponse(['success' => false, 'message' => 'Tên danh mục không được để trống.'], 400);
    }

    $slug = slugify($name);

    if ($id) {
        $duplicate = db_fetch_one_prepared('SELECT id FROM categories WHERE (name = ? OR slug = ?) AND id <> ? LIMIT 1', 'ssi', [$name, $slug, $id]);
    } else {
        $duplicate = db_fetch_one_prepared('SELECT id FROM categories WHERE (name = ? OR slug = ?) LIMIT 1', 'ss', [$name, $slug]);
    }
    if ($duplicate) {
        jsonResponse(['success' => false, 'message' => 'Danh mục này đã tồn tại.'], 400);
    }

    if ($id) {
        $stmt = db_prepare('UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $slug, $description, $id);
        $stmt->execute();
        jsonResponse(['success' => true, 'message' => 'Đã cập nhật danh mục.']);
    }

    $stmt = db_prepare('INSERT INTO categories (name, slug, description, status) VALUES (?, ?, ?, ?)');
    $status = 'active';
    $stmt->bind_param('ssss', $name, $slug, $description, $status);
    $stmt->execute();
    jsonResponse(['success' => true, 'message' => 'Đã tạo danh mục mới.']);
}

jsonResponse(['success' => false, 'message' => 'Hành động không hợp lệ.'], 400);
